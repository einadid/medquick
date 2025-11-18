<?php
$pageTitle = 'POS System';
require_once '../includes/header.php';
requireRole('salesman');

$user = getCurrentUser();
$shopId = $user['shop_id'];

// Initialize POS session
if (!isset($_SESSION['pos_cart'])) {
    $_SESSION['pos_cart'] = [];
}
if (!isset($_SESSION['pos_customer'])) {
    $_SESSION['pos_customer'] = null;
}

// Handle customer lookup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lookup_customer'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $searchTerm = trim($_POST['customer_search']);
    
    // Search by member ID, email, or phone
    $customer = Database::getInstance()->fetchOne("
        SELECT u.*, r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE r.role_name = 'customer' 
        AND u.status = 'active'
        AND (u.member_id = ? OR u.email = ? OR u.phone = ?)
        LIMIT 1
    ", [$searchTerm, $searchTerm, $searchTerm]);
    
    if ($customer) {
        $_SESSION['pos_customer'] = $customer;
        setFlash('success', 'Customer found: ' . $customer['full_name']);
    } else {
        setFlash('error', 'Customer not found');
    }
    
    redirect('/salesman/pos.php');
}

// Handle remove customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_customer'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    $_SESSION['pos_customer'] = null;
    setFlash('success', 'Customer removed');
    redirect('/salesman/pos.php');
}

// Handle POS actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['lookup_customer']) && !isset($_POST['remove_customer'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $shopMedicineId = $_POST['shop_medicine_id'];
            $quantity = (int)$_POST['quantity'];
            
            if (isset($_SESSION['pos_cart'][$shopMedicineId])) {
                $_SESSION['pos_cart'][$shopMedicineId] += $quantity;
            } else {
                $_SESSION['pos_cart'][$shopMedicineId] = $quantity;
            }
            setFlash('success', 'Added to cart');
            break;
            
        case 'remove':
            $shopMedicineId = $_POST['shop_medicine_id'];
            unset($_SESSION['pos_cart'][$shopMedicineId]);
            setFlash('success', 'Removed from cart');
            break;
            
        case 'clear':
            $_SESSION['pos_cart'] = [];
            setFlash('success', 'Cart cleared');
            break;
            
        case 'complete':
            // Complete sale and award points
            if (empty($_SESSION['pos_cart'])) {
                setFlash('error', 'Cart is empty');
                redirect('/salesman/pos.php');
            }
            
            // Calculate total
            $posTotal = 0;
            foreach ($_SESSION['pos_cart'] as $smId => $qty) {
                $item = Database::getInstance()->fetchOne("
                    SELECT selling_price FROM shop_medicines WHERE id = ?
                ", [$smId]);
                if ($item) {
                    $posTotal += $qty * $item['selling_price'];
                }
            }
            
            // Begin transaction
            Database::getInstance()->getConnection()->beginTransaction();
            
            try {
                // Calculate points
                $pointsEarned = 0;
                $customerId = null;
                
                if ($_SESSION['pos_customer']) {
                    $customerId = $_SESSION['pos_customer']['id'];
                    $pointsEarned = calculatePointsEarned($posTotal);
                }
                
                // Create POS sale record
                $saleId = Database::getInstance()->insert('pos_sales', [
                    'shop_id' => $shopId,
                    'customer_id' => $customerId,
                    'salesman_id' => $_SESSION['user_id'],
                    'total_amount' => $posTotal,
                    'payment_method' => 'cash',
                    'points_earned' => $pointsEarned
                ]);
                
                // Add sale items and reduce stock
                foreach ($_SESSION['pos_cart'] as $smId => $qty) {
                    $item = Database::getInstance()->fetchOne("
                        SELECT sm.*, m.id as medicine_id
                        FROM shop_medicines sm
                        JOIN medicines m ON sm.medicine_id = m.id
                        WHERE sm.id = ?
                    ", [$smId]);
                    
                    if ($item) {
                        // Add to sale items
                        Database::getInstance()->insert('pos_sale_items', [
                            'pos_sale_id' => $saleId,
                            'medicine_id' => $item['medicine_id'],
                            'shop_medicine_id' => $smId,
                            'quantity' => $qty,
                            'price' => $item['selling_price']
                        ]);
                        
                        // Reduce stock
                        Database::getInstance()->query("
                            UPDATE shop_medicines SET stock = stock - ? WHERE id = ?
                        ", [$qty, $smId]);
                    }
                }
                
                // Award loyalty points if customer is referenced
                if ($customerId && $pointsEarned > 0) {
                    require_once '../classes/Loyalty.php';
                    $loyalty = new Loyalty();
                    $loyalty->awardBonusPoints(
                        $customerId, 
                        $pointsEarned, 
                        "POS Purchase - Sale #$saleId"
                    );
                }
                
                Database::getInstance()->getConnection()->commit();
                
                // Store sale ID for receipt
                $_SESSION['last_pos_sale_id'] = $saleId;
                
                // Clear cart and customer
                $_SESSION['pos_cart'] = [];
                $_SESSION['pos_customer'] = null;
                
                logAudit($_SESSION['user_id'], 'pos_sale_completed', "POS Sale #$saleId completed");
                
                // Redirect to receipt
                redirect('/salesman/pos-receipt.php?sale_id=' . $saleId);
                
            } catch (Exception $e) {
                Database::getInstance()->getConnection()->rollBack();
                setFlash('error', 'Sale failed: ' . $e->getMessage());
            }
            break;
    }
    
    redirect('/salesman/pos.php');
}

// Get shop medicines
$medicines = Database::getInstance()->fetchAll("
    SELECT sm.*, m.name, m.generic_name, m.dosage_form, m.strength
    FROM shop_medicines sm
    JOIN medicines m ON sm.medicine_id = m.id
    WHERE sm.shop_id = ? AND sm.stock > 0
    ORDER BY m.name
", [$shopId]);

// Calculate POS cart total
$posTotal = 0;
$cartItems = [];
if (!empty($_SESSION['pos_cart'])) {
    foreach ($_SESSION['pos_cart'] as $smId => $qty) {
        $item = Database::getInstance()->fetchOne("
            SELECT sm.*, m.name, m.generic_name
            FROM shop_medicines sm
            JOIN medicines m ON sm.medicine_id = m.id
            WHERE sm.id = ?
        ", [$smId]);
        
        if ($item) {
            $item['cart_quantity'] = $qty;
            $item['item_total'] = $qty * $item['selling_price'];
            $posTotal += $item['item_total'];
            $cartItems[] = $item;
        }
    }
}

// Get customer points if linked
$customerPoints = 0;
$pointsToEarn = 0;
if ($_SESSION['pos_customer']) {
    require_once '../classes/Loyalty.php';
    $loyalty = new Loyalty();
    $customerPoints = $loyalty->getUserPoints($_SESSION['pos_customer']['id']);
    $pointsToEarn = calculatePointsEarned($posTotal);
}
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">POS System - Walk-in Sales</h2>
</div>

<!-- Customer Lookup Section -->
<div class="bg-gradient-to-r from-blue-50 to-blue-100 border-2 border-blue-300 p-6 mb-4">
    <?php if ($_SESSION['pos_customer']): ?>
        <!-- Customer Linked -->
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-blue-900">👤 Customer Linked</h3>
                <div class="mt-2">
                    <div class="font-bold text-xl"><?php echo clean($_SESSION['pos_customer']['full_name']); ?></div>
                    <div class="text-sm text-gray-700">
                        <strong>Member ID:</strong> <?php echo clean($_SESSION['pos_customer']['member_id']); ?>
                    </div>
                    <div class="text-sm text-gray-700">
                        <strong>Email:</strong> <?php echo clean($_SESSION['pos_customer']['email']); ?>
                    </div>
                    <div class="text-sm text-gray-700">
                        <strong>Phone:</strong> <?php echo clean($_SESSION['pos_customer']['phone']); ?>
                    </div>
                    <div class="mt-2 p-2 bg-yellow-100 border border-yellow-400 inline-block">
                        <strong>💎 Current Points:</strong> <?php echo number_format($customerPoints); ?> points
                    </div>
                    <?php if ($pointsToEarn > 0): ?>
                    <div class="mt-1 p-2 bg-green-100 border border-green-400 inline-block">
                        <strong>🎉 Will Earn:</strong> +<?php echo number_format($pointsToEarn); ?> points from this purchase
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="remove_customer" value="1">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white font-bold">
                        REMOVE CUSTOMER
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Customer Lookup Form -->
        <h3 class="text-lg font-bold text-blue-900 mb-3">🔍 Link Customer for Loyalty Points</h3>

        <div class="bg-white border p-3 mb-3">
            <div class="text-sm font-bold mb-2">Member ID Examples:</div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div>📧 john.doe@gmail.com</div>
                <div>➡️ <code class="bg-gray-100 px-2 py-1">john.doe</code></div>
                <div>📧 customer1@example.com</div>
                <div>➡️ <code class="bg-gray-100 px-2 py-1">customer1</code></div>
            </div>
        </div>

        <form method="POST" class="flex gap-2">
            <?php echo csrfField(); ?>
            <input type="hidden" name="lookup_customer" value="1">
            <input type="text" 
                   name="customer_search" 
                   placeholder="Enter Member ID, Email, or Phone..." 
                   required
                   class="flex-1 p-3 border-2 border-blue-400 text-lg">
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-bold">
                FIND
            </button>
        </form>
        <div class="text-sm text-gray-600 mt-2">
            💡 Member ID = Email username (part before @)
        </div>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Medicine Selection -->
    <div class="bg-white border-2 border-gray-300 p-6">
        <h3 class="text-xl font-bold mb-4">Select Medicines</h3>
        
        <!-- Search -->
        <input type="text" id="medicineSearch" placeholder="Search medicine..." 
               class="w-full p-2 border-2 border-gray-400 mb-4">
        
        <div id="medicineList" class="space-y-2 max-h-96 overflow-y-auto">
            <?php foreach ($medicines as $med): ?>
            <div class="border p-3 medicine-item" data-name="<?php echo strtolower($med['name'] . ' ' . $med['generic_name']); ?>">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="font-bold"><?php echo clean($med['name']); ?></div>
                        <div class="text-sm text-gray-600"><?php echo clean($med['generic_name']); ?></div>
                        <div class="text-xs text-gray-500">
                            <?php echo clean($med['dosage_form']); ?> - <?php echo clean($med['strength']); ?>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-green-600"><?php echo formatPrice($med['selling_price']); ?></div>
                        <div class="text-xs text-gray-500">Stock: <?php echo $med['stock']; ?></div>
                    </div>
                </div>
                
                <form method="POST" class="flex gap-2">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="shop_medicine_id" value="<?php echo $med['id']; ?>">
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $med['stock']; ?>" 
                           class="w-20 p-1 border text-center">
                    <button type="submit" class="flex-1 p-1 bg-blue-600 text-white text-sm font-bold">
                        ADD
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- POS Cart -->
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Current Sale</h3>
            <?php if (!empty($cartItems)): ?>
            <form method="POST" onsubmit="return confirm('Clear cart?')">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="px-3 py-1 bg-red-600 text-white text-sm">CLEAR</button>
            </form>
            <?php endif; ?>
        </div>
        
        <?php if (empty($cartItems)): ?>
            <div class="text-center text-gray-600 py-8">
                Cart is empty. Add medicines to start a sale.
            </div>
        <?php else: ?>
            <div class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                <?php foreach ($cartItems as $item): ?>
                <div class="border p-2 flex justify-between items-center">
                    <div class="flex-1">
                        <div class="font-bold text-sm"><?php echo clean($item['name']); ?></div>
                        <div class="text-xs text-gray-600">
                            <?php echo $item['cart_quantity']; ?> × <?php echo formatPrice($item['selling_price']); ?>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold"><?php echo formatPrice($item['item_total']); ?></div>
                        <form method="POST" class="inline">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="shop_medicine_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="text-red-600 text-xs">Remove</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Summary -->
            <div class="border-t-2 pt-4 mb-4">
                <div class="flex justify-between text-2xl font-bold">
                    <div>TOTAL:</div>
                    <div class="text-green-600"><?php echo formatPrice($posTotal); ?></div>
                </div>
                
                <?php if ($_SESSION['pos_customer'] && $pointsToEarn > 0): ?>
                <div class="mt-2 p-3 bg-yellow-50 border border-yellow-400">
                    <div class="flex justify-between items-center">
                        <div class="text-sm font-bold">Points to be awarded:</div>
                        <div class="text-lg font-bold text-yellow-700">+<?php echo number_format($pointsToEarn); ?> points</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <form method="POST" onsubmit="return confirm('Complete this sale?')">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="complete">
                <button type="submit" class="w-full p-4 bg-green-600 text-white font-bold text-lg">
                    💰 COMPLETE SALE & PRINT RECEIPT
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Real-time search
document.getElementById('medicineSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const items = document.querySelectorAll('.medicine-item');
    
    items.forEach(item => {
        const name = item.dataset.name;
        if (name.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>