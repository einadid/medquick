<?php
$pageTitle = 'Request Return';
require_once '../includes/header.php';
requireRole('customer');

$orderId = $_GET['order_id'] ?? 0;
$parcelId = $_GET['parcel_id'] ?? 0;

// Get order and parcel details
$parcel = Database::getInstance()->fetchOne("
    SELECT p.*, o.user_id, o.delivery_type, s.name as shop_name
    FROM parcels p
    JOIN orders o ON p.order_id = o.id
    JOIN shops s ON p.shop_id = s.id
    WHERE p.id = ? AND o.user_id = ?
", [$parcelId, $_SESSION['user_id']]);

if (!$parcel) {
    setFlash('error', 'Parcel not found');
    redirect('/customer/orders.php');
}

// Check if already returned
$existingReturn = Database::getInstance()->fetchOne("
    SELECT id FROM order_returns WHERE parcel_id = ?
", [$parcelId]);

if ($existingReturn) {
    setFlash('error', 'Return request already submitted for this parcel');
    redirect('/customer/orders.php');
}

// Check if delivered (can only return delivered orders)
if ($parcel['status'] !== 'delivered') {
    setFlash('error', 'Can only return delivered orders');
    redirect('/customer/orders.php');
}

// Check if within 7 days
$deliveredDate = strtotime($parcel['updated_at']);
$daysSinceDelivery = floor((time() - $deliveredDate) / 86400);

if ($daysSinceDelivery > 7) {
    setFlash('error', 'Return period expired (7 days from delivery)');
    redirect('/customer/orders.php');
}

// Get parcel items
$items = Database::getInstance()->fetchAll("
    SELECT oi.*, m.name as medicine_name, m.generic_name
    FROM order_items oi
    JOIN medicines m ON oi.medicine_id = m.id
    WHERE oi.parcel_id = ?
", [$parcelId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $reason = $_POST['reason'];
    $selectedItems = $_POST['items'] ?? [];
    
    if (empty($selectedItems)) {
        setFlash('error', 'Please select at least one item to return');
    } else {
        // Calculate return amount
        $returnAmount = 0;
        foreach ($selectedItems as $itemId) {
            $item = array_filter($items, fn($i) => $i['id'] == $itemId);
            if ($item) {
                $item = reset($item);
                $returnAmount += $item['quantity'] * $item['price'];
            }
        }
        
        // Create return request
        Database::getInstance()->getConnection()->beginTransaction();
        
        try {
            $returnId = Database::getInstance()->insert('order_returns', [
                'order_id' => $parcel['order_id'],
                'parcel_id' => $parcelId,
                'user_id' => $_SESSION['user_id'],
                'shop_id' => $parcel['shop_id'],
                'reason' => $reason,
                'return_amount' => $returnAmount,
                'status' => 'pending'
            ]);
            
            // Add return items
            foreach ($selectedItems as $itemId) {
                $item = array_filter($items, fn($i) => $i['id'] == $itemId);
                if ($item) {
                    $item = reset($item);
                    Database::getInstance()->insert('return_items', [
                        'return_id' => $returnId,
                        'order_item_id' => $itemId,
                        'medicine_id' => $item['medicine_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'reason' => $_POST['item_reason_' . $itemId] ?? null
                    ]);
                }
            }
            
            Database::getInstance()->getConnection()->commit();
            
            logAudit($_SESSION['user_id'], 'return_requested', "Return request #$returnId created");
            setFlash('success', 'Return request submitted successfully');
            redirect('/customer/orders.php');
            
        } catch (Exception $e) {
            Database::getInstance()->getConnection()->rollBack();
            setFlash('error', 'Failed to submit return request');
        }
    }
}
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold mb-4">Request Return</h2>
    <div class="text-sm text-gray-600">
        Parcel #<?php echo $parcelId; ?> from <?php echo clean($parcel['shop_name']); ?>
    </div>
</div>

<!-- Return Policy -->
<div class="bg-yellow-50 border-2 border-yellow-400 p-4 mb-4">
    <h3 class="font-bold mb-2">⚠️ Return Policy</h3>
    <ul class="text-sm space-y-1">
        <li>• Returns accepted within <strong>7 days</strong> of delivery (<?php echo 7 - $daysSinceDelivery; ?> days left)</li>
        <li>• Medicine must be <strong>unopened</strong> and in original packaging</li>
        <li>• Refund processed within 3-5 business days after approval</li>
        <li>• Shipping charges are non-refundable</li>
    </ul>
</div>

<!-- Return Form -->
<div class="bg-white border-2 border-gray-300 p-6">
    <form method="POST">
        <?php echo csrfField(); ?>
        
        <div class="mb-6">
            <h3 class="font-bold mb-3">Select Items to Return:</h3>
            
            <?php foreach ($items as $item): ?>
            <div class="border p-4 mb-3">
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="items[]" value="<?php echo $item['id']; ?>" class="mt-1">
                    <div class="flex-1">
                        <div class="font-bold"><?php echo clean($item['medicine_name']); ?></div>
                        <div class="text-sm text-gray-600"><?php echo clean($item['generic_name']); ?></div>
                        <div class="text-sm">Quantity: <?php echo $item['quantity']; ?> | Price: <?php echo formatPrice($item['price']); ?></div>
                        
                        <div class="mt-2">
                            <input type="text" 
                                   name="item_reason_<?php echo $item['id']; ?>" 
                                   placeholder="Specific reason for this item (optional)"
                                   class="w-full p-2 border text-sm">
                        </div>
                    </div>
                    <div class="font-bold text-green-600">
                        <?php echo formatPrice($item['quantity'] * $item['price']); ?>
                    </div>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mb-6">
            <label class="block mb-2 font-bold">Overall Return Reason *</label>
            <select name="reason" required class="w-full p-2 border-2 border-gray-400 mb-2">
                <option value="">Select reason...</option>
                <option value="Damaged/Defective product">Damaged/Defective product</option>
                <option value="Wrong medicine delivered">Wrong medicine delivered</option>
                <option value="Expired medicine">Expired medicine</option>
                <option value="No longer needed">No longer needed</option>
                <option value="Doctor changed prescription">Doctor changed prescription</option>
                <option value="Other">Other</option>
            </select>
        </div>
        
        <div class="flex gap-2">
            <button type="submit" class="flex-1 p-3 bg-red-600 text-white font-bold">
                SUBMIT RETURN REQUEST
            </button>
            <a href="../customer/orders.php" class="flex-1 p-3 bg-gray-400 text-white text-center font-bold">
                CANCEL
            </a>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>