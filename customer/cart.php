<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get cart from localStorage
$cart = json_decode(file_get_contents('php://input'), true) ?? [];
$cart_items = [];
$total = 0;

// Process cart items
foreach ($cart as $item) {
    $stmt = $pdo->prepare("SELECT name, price, quantity FROM medicines WHERE id = ?");
    $stmt->execute([$item['id']]);
    $medicine = $stmt->fetch();
    
    if ($medicine) {
        $available_quantity = min($item['quantity'], $medicine['quantity']);
        $subtotal = $available_quantity * $medicine['price'];
        
        $cart_items[] = [
            'id' => $item['id'],
            'name' => $medicine['name'],
            'price' => $medicine['price'],
            'quantity' => $available_quantity,
            'max_quantity' => $medicine['quantity'],
            'subtotal' => $subtotal
        ];
        
        $total += $subtotal;
    }
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    if (count($cart_items) > 0) {
        try {
            $pdo->beginTransaction();
            
            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_id, total_amount, status, shipping_address, phone)
                VALUES (?, ?, 'pending', ?, ?)
            ");
            
            $shipping_address = $_POST['shipping_address'] ?? '';
            $phone = $_POST['phone'] ?? '';
            
            $stmt->execute([
                $_SESSION['user_id'],
                $total,
                $shipping_address,
                $phone
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Add order items
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, medicine_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $order_id,
                    $item['id'],
                    $item['quantity'],
                    $item['price']
                ]);
                
                // Update stock
                $stmt = $pdo->prepare("
                    UPDATE medicines 
                    SET quantity = quantity - ? 
                    WHERE id = ?
                ");
                
                $stmt->execute([$item['quantity'], $item['id']]);
            }
            
            $pdo->commit();
            
            // Clear cart
            echo json_encode(['success' => true, 'order_id' => $order_id]);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">আপনার শপিং কার্ট</h1>
    
    <div id="cartMessage" class="hidden mb-4 p-4 rounded-lg"></div>
    
    <?php if (count($cart_items) > 0): ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Cart Items -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="hidden md:grid grid-cols-12 gap-4 mb-4 font-semibold text-gray-600 border-b pb-2">
                    <div class="col-span-5">পণ্য</div>
                    <div class="col-span-2 text-center">দাম</div>
                    <div class="col-span-3 text-center">পরিমাণ</div>
                    <div class="col-span-2 text-right">মোট</div>
                </div>
                
                <div id="cartItems">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="grid grid-cols-12 gap-4 items-center py-4 border-b" data-id="<?php echo $item['id']; ?>">
                        <div class="col-span-12 md:col-span-5 flex items-center">
                            <button onclick="removeFromCart(<?php echo $item['id']; ?>)" 
                                    class="text-red-500 mr-3">
                                <i class="fas fa-times"></i>
                            </button>
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                        </div>
                        
                        <div class="col-span-4 md:col-span-2 text-gray-600 md:text-center">
                            ৳<?php echo number_format($item['price'], 2); ?>
                        </div>
                        
                        <div class="col-span-4 md:col-span-3">
                            <div class="flex items-center">
                                <button onclick="updateQuantity(<?php echo $item['id']; ?>, -1)" 
                                        class="bg-gray-200 px-3 py-1 rounded-l-lg">-</button>
                                
                                <input type="number" 
                                       id="qty_<?php echo $item['id']; ?>" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="<?php echo $item['max_quantity']; ?>"
                                       class="w-16 text-center border-t border-b border-gray-200 py-1"
                                       onchange="updateQuantityInput(<?php echo $item['id']; ?>)">
                                
                                <button onclick="updateQuantity(<?php echo $item['id']; ?>, 1)" 
                                        class="bg-gray-200 px-3 py-1 rounded-r-lg">+</button>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                সর্বোচ্চ: <?php echo $item['max_quantity']; ?> পিস
                            </div>
                        </div>
                        
                        <div class="col-span-4 md:col-span-2 text-right font-semibold">
                            ৳<span id="subtotal_<?php echo $item['id']; ?>">
                                <?php echo number_format($item['subtotal'], 2); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                <h2 class="text-xl font-bold mb-4">অর্ডার সারাংশ</h2>
                
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between">
                        <span>সাবটোটাল</span>
                        <span>৳<span id="subtotal"><?php echo number_format($total, 2); ?></span></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span>ডেলিভারি চার্জ</span>
                        <span>৳<span id="delivery">60.00</span></span>
                    </div>
                    
                    <div class="border-t pt-3 font-bold text-lg">
                        <div class="flex justify-between">
                            <span>মোট</span>
                            <span>৳<span id="total"><?php echo number_format($total + 60, 2); ?></span></span>
                        </div>
                    </div>
                </div>
                
                <!-- Checkout Form -->
                <form id="checkoutForm">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">ডেলিভারি ঠিকানা*</label>
                        <textarea name="shipping_address" class="w-full px-3 py-2 border rounded-lg" 
                                  rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">ফোন নম্বর*</label>
                        <input type="text" name="phone" class="w-full px-3 py-2 border rounded-lg" 
                               value="<?php echo $_SESSION['phone'] ?? ''; ?>" required>
                    </div>
                    
                    <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-bold">
                        <i class="fas fa-lock mr-2"></i>অর্ডার কনফার্ম করুন
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="text-center py-12">
        <i class="fas fa-shopping-cart text-5xl text-gray-300 mb-4"></i>
        <h2 class="text-xl font-bold mb-2">আপনার কার্ট খালি</h2>
        <p class="text-gray-600 mb-6">আপনার পছন্দের পণ্য কার্টে যোগ করুন</p>
        <a href="index.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
            <i class="fas fa-arrow-left mr-2"></i>শপিং চালিয়ে যান
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
// Cart management functions
function updateQuantity(medicineId, change) {
    const input = document.getElementById(`qty_${medicineId}`);
    let newQty = parseInt(input.value) + change;
    const maxQty = parseInt(input.max);
    
    if (newQty < 1) newQty = 1;
    if (newQty > maxQty) newQty = maxQty;
    
    input.value = newQty;
    updateCart(medicineId, newQty);
}

function updateQuantityInput(medicineId) {
    const input = document.getElementById(`qty_${medicineId}`);
    let newQty = parseInt(input.value) || 1;
    const maxQty = parseInt(input.max);
    
    if (newQty < 1) newQty = 1;
    if (newQty > maxQty) newQty = maxQty;
    
    input.value = newQty;
    updateCart(medicineId, newQty);
}

function updateCart(medicineId, quantity) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const item = cart.find(item => item.id === medicineId);
    
    if (item) {
        item.quantity = quantity;
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartDisplay();
    }
}

function removeFromCart(medicineId) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart = cart.filter(item => item.id !== medicineId);
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Remove item from DOM
    document.querySelector(`[data-id="${medicineId}"]`).remove();
    
    if (cart.length === 0) {
        location.reload(); // Reload to show empty cart message
    } else {
        updateCartDisplay();
    }
}

function updateCartDisplay() {
    // Get updated cart from localStorage
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Update cart count in header
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('cartCount').textContent = totalItems;
    
    // Update cart page via AJAX
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(cart)
    })
    .then(response => response.text())
    .then(html => {
        // This is a simplified version - in a real app you might want to update specific elements
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Update cart items
        const newCartItems = doc.getElementById('cartItems');
        if (newCartItems) {
            document.getElementById('cartItems').innerHTML = newCartItems.innerHTML;
        }
        
        // Update totals
        const newSubtotal = doc.getElementById('subtotal');
        const newTotal = doc.getElementById('total');
        
        if (newSubtotal) document.getElementById('subtotal').textContent = newSubtotal.textContent;
        if (newTotal) document.getElementById('total').textContent = newTotal.textContent;
    });
}

// Handle checkout form submission
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            checkout: true,
            cart: cart,
            shipping_address: formData.get('shipping_address'),
            phone: formData.get('phone')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear cart
            localStorage.removeItem('cart');
            updateCartCount();
            
            // Redirect to success page
            window.location.href = `order_success.php?order_id=${data.order_id}`;
        } else {
            showMessage('error', data.error || 'অর্ডার সম্পন্ন করতে সমস্যা হয়েছে');
        }
    })
    .catch(error => {
        showMessage('error', 'একটি ত্রুটি ঘটেছে: ' + error.message);
    });
});

function showMessage(type, message) {
    const messageDiv = document.getElementById('cartMessage');
    messageDiv.textContent = message;
    messageDiv.className = `p-4 rounded-lg mb-4 ${
        type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
    }`;
    messageDiv.classList.remove('hidden');
    
    setTimeout(() => {
        messageDiv.classList.add('hidden');
    }, 5000);
}

// Initialize cart display
updateCartDisplay();
</script>

<?php include '../includes/footer.php'; ?>