<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get cart from localStorage
$cart = json_decode($_COOKIE['cart'] ?? '[]', true) ?? [];

if (empty($cart)) {
    header('Location: cart.php');
    exit();
}

// Fetch cart items with details
$cart_items = [];
$subtotal = 0;

foreach ($cart as $item) {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ? AND quantity >= ?");
    $stmt->execute([$item['id'], $item['quantity']]);
    $medicine = $stmt->fetch();
    
    if ($medicine) {
        $item_total = $medicine['price'] * $item['quantity'];
        $subtotal += $item_total;
        
        $cart_items[] = [
            'id' => $medicine['id'],
            'name' => $medicine['name'],
            'price' => $medicine['price'],
            'quantity' => $item['quantity'],
            'total' => $item_total,
            'image' => $medicine['image'] ?? 'default.jpg'
        ];
    }
}

// If cart is empty after validation, redirect to cart
if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

// Calculate totals
$delivery_charge = 60; // Flat delivery charge
$discount = 0; // Can be calculated based on coupons
$total = $subtotal + $delivery_charge - $discount;

// Fetch customer addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE customer_id = ? ORDER BY is_default DESC");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll();

// Process checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $address_id = $_POST['address_id'];
    $payment_method = $_POST['payment_method'];
    $notes = $_POST['notes'] ?? '';
    
    // Validate address
    $valid_address = false;
    foreach ($addresses as $addr) {
        if ($addr['id'] == $address_id) {
            $valid_address = true;
            break;
        }
    }
    
    if (!$valid_address) {
        $error = "অবৈধ ঠিকানা নির্বাচন করা হয়েছে";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_id, total_amount, delivery_charge, discount, final_amount, 
                                  shipping_address_id, payment_method, order_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $subtotal,
                $delivery_charge,
                $discount,
                $total,
                $address_id,
                $payment_method,
                $notes
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Add order items
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, medicine_id, quantity, unit_price, total_price)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $order_id,
                    $item['id'],
                    $item['quantity'],
                    $item['price'],
                    $item['total']
                ]);
                
                // Update medicine stock
                $stmt = $pdo->prepare("
                    UPDATE medicines 
                    SET quantity = quantity - ? 
                    WHERE id = ?
                ");
                
                $stmt->execute([$item['quantity'], $item['id']]);
            }
            
            $pdo->commit();
            
            // Clear cart
            setcookie('cart', '[]', time() + (86400 * 30), '/');
            
            // Redirect to success page
            header("Location: order_success.php?order_id=$order_id");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "অর্ডার সম্পন্ন করতে সমস্যা হয়েছে: " . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">চেকআউট</h1>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Summary -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">আপনার অর্ডার</h2>
                
                <div class="border-b pb-4 mb-4">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="flex items-center py-2">
                        <img src="../assets/images/medicines/<?php echo $item['image']; ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="w-16 h-16 object-cover rounded-lg mr-4">
                        <div class="flex-grow">
                            <h3 class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-gray-600 text-sm">পরিমাণ: <?php echo $item['quantity']; ?> পিস</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">৳<?php echo number_format($item['total'], 2); ?></p>
                            <p class="text-sm text-gray-600">৳<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span>সাবটোটাল:</span>
                        <span>৳<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>ডেলিভারি চার্জ:</span>
                        <span>৳<?php echo number_format($delivery_charge, 2); ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                    <div class="flex justify-between text-green-600">
                        <span>ডিসকাউন্ট:</span>
                        <span>-৳<?php echo number_format($discount, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between font-bold text-lg border-t pt-2 mt-2">
                        <span>সর্বমোট:</span>
                        <span>৳<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Delivery Address -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">ডেলিভারি ঠিকানা</h2>
                
                <?php if (empty($addresses)): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <p class="text-yellow-700">আপনার কোন ঠিকানা সংরক্ষিত নেই। দয়া করে একটি ঠিকানা যোগ করুন।</p>
                    </div>
                    <a href="addresses.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg inline-block">
                        <i class="fas fa-plus mr-2"></i>নতুন ঠিকানা যোগ করুন
                    </a>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($addresses as $address): ?>
                        <div class="border rounded-lg p-4 cursor-pointer address-option 
                                    <?php echo $address['is_default'] ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>"
                             onclick="selectAddress(<?php echo $address['id']; ?>)">
                            <div class="flex items-start">
                                <input type="radio" name="address_id" value="<?php echo $address['id']; ?>" 
                                       id="address_<?php echo $address['id']; ?>"
                                       class="mt-1" 
                                       <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                <label for="address_<?php echo $address['id']; ?>" class="ml-2 block">
                                    <h3 class="font-semibold"><?php echo htmlspecialchars($address['name']); ?></h3>
                                    <p class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($address['address']); ?>, 
                                        <?php echo htmlspecialchars($address['area']); ?>, 
                                        <?php echo htmlspecialchars($address['city']); ?>
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <i class="fas fa-phone-alt mr-1"></i> <?php echo htmlspecialchars($address['phone']); ?>
                                    </p>
                                    <?php if ($address['is_default']): ?>
                                        <span class="inline-block mt-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                            ডিফল্ট ঠিকানা
                                        </span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4">
                        <a href="addresses.php" class="text-blue-600 hover:underline">
                            <i class="fas fa-plus mr-1"></i> নতুন ঠিকানা যোগ করুন
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Payment Method -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">পেমেন্ট মেথড</h2>
                
                <div class="space-y-4">
                    <div class="border rounded-lg p-4 cursor-pointer payment-option" onclick="selectPayment('cod')">
                        <div class="flex items-center">
                            <input type="radio" name="payment_method" value="cod" id="cod" class="mr-3" checked>
                            <label for="cod" class="flex-grow cursor-pointer">
                                <h3 class="font-semibold">ক্যাশ অন ডেলিভারি (COD)</h3>
                                <p class="text-sm text-gray-600">আপনার অর্ডার ডেলিভারির সময় নগদ অর্থ প্রদান করুন</p>
                            </label>
                            <div class="flex space-x-2">
                                <img src="../assets/images/payment/cod.png" alt="Cash on Delivery" class="h-8">
                            </div>
                        </div>
                    </div>
                    
                    <div class="border rounded-lg p-4 cursor-pointer payment-option" onclick="selectPayment('bkash')">
                        <div class="flex items-center">
                            <input type="radio" name="payment_method" value="bkash" id="bkash" class="mr-3">
                            <label for="bkash" class="flex-grow cursor-pointer">
                                <h3 class="font-semibold">bKash</h3>
                                <p class="text-sm text-gray-600">bKash মোবাইল পেমেন্ট সিস্টেম</p>
                            </label>
                            <img src="../assets/images/payment/bkash.png" alt="bKash" class="h-8">
                        </div>
                    </div>
                    
                    <div class="border rounded-lg p-4 cursor-pointer payment-option" onclick="selectPayment('nagad')">
                        <div class="flex items-center">
                            <input type="radio" name="payment_method" value="nagad" id="nagad" class="mr-3">
                            <label for="nagad" class="flex-grow cursor-pointer">
                                <h3 class="font-semibold">নগদ</h3>
                                <p class="text-sm text-gray-600">নগদ মোবাইল পেমেন্ট সিস্টেম</p>
                            </label>
                            <img src="../assets/images/payment/nagad.png" alt="Nagad" class="h-8">
                        </div>
                    </div>
                    
                    <div class="border rounded-lg p-4 cursor-pointer payment-option" onclick="selectPayment('card')">
                        <div class="flex items-center">
                            <input type="radio" name="payment_method" value="card" id="card" class="mr-3">
                            <label for="card" class="flex-grow cursor-pointer">
                                <h3 class="font-semibold">ক্রেডিট/ডেবিট কার্ড</h3>
                                <p class="text-sm text-gray-600">Visa, MasterCard, American Express</p>
                            </label>
                            <div class="flex space-x-2">
                                <img src="../assets/images/payment/visa.png" alt="Visa" class="h-8">
                                <img src="../assets/images/payment/mastercard.png" alt="MasterCard" class="h-8">
                                <img src="../assets/images/payment/amex.png" alt="American Express" class="h-8">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Notes -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h2 class="text-xl font-bold mb-4">অর্ডার নোট (ঐচ্ছিক)</h2>
                <textarea name="notes" class="w-full px-3 py-2 border rounded-lg" rows="3" placeholder="আপনার অর্ডার সম্পর্কে বিশেষ নির্দেশনা থাকলে এখানে লিখুন"></textarea>
            </div>
        </div>
        <!-- Add this after the order summary section -->
<div class="mt-4">
    <div class="flex">
        <input type="text" id="coupon_code" placeholder="কুপন কোড লিখুন" 
               class="flex-grow px-3 py-2 border rounded-l-lg focus:outline-none">
        <button type="button" onclick="applyCoupon()" 
                class="bg-gray-200 px-4 py-2 rounded-r-lg hover:bg-gray-300">
            প্রয়োগ করুন
        </button>
    </div>
    <div id="coupon_message" class="mt-2 text-sm"></div>
</div>

<script>
// Apply coupon
function applyCoupon() {
    const code = document.getElementById('coupon_code').value.trim();
    const subtotal = <?php echo $subtotal; ?>;
    
    if (!code) {
        showCouponMessage('কুপন কোড লিখুন', 'error');
        return;
    }
    
    fetch('apply_coupon.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            code: code,
            subtotal: subtotal
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            showCouponMessage(`কুপন প্রয়োগ করা হয়েছে: ${data.discount_display} ছাড়`, 'success');
            updateOrderSummary(data.discount);
        } else {
            showCouponMessage(data.message, 'error');
        }
    })
    .catch(error => {
        showCouponMessage('একটি ত্রুটি ঘটেছে', 'error');
    });
}

function showCouponMessage(message, type) {
    const messageDiv = document.getElementById('coupon_message');
    messageDiv.textContent = message;
    messageDiv.className = 'mt-2 text-sm ' + (type === 'success' ? 'text-green-600' : 'text-red-600');
}

function updateOrderSummary(discount) {
    // Update the order summary with the new discount
    const subtotal = <?php echo $subtotal; ?>;
    const deliveryCharge = <?php echo $delivery_charge; ?>;
    const total = subtotal + deliveryCharge - discount;
    
    document.getElementById('discount_amount').textContent = '৳' + discount.toFixed(2);
    document.getElementById('total_amount').textContent = '৳' + total.toFixed(2);
    
    // Add hidden input to form
    let discountInput = document.getElementById('coupon_discount');
    if (!discountInput) {
        discountInput = document.createElement('input');
        discountInput.type = 'hidden';
        discountInput.name = 'coupon_discount';
        discountInput.id = 'coupon_discount';
        document.getElementById('checkoutForm').appendChild(discountInput);
    }
    discountInput.value = discount;
}
</script>
        
        <!-- Order Summary & Checkout -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                <h2 class="text-xl font-bold mb-4">অর্ডার সারাংশ</h2>
                
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between">
                        <span>সাবটোটাল:</span>
                        <span>৳<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>ডেলিভারি চার্জ:</span>
                        <span>৳<?php echo number_format($delivery_charge, 2); ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                    <div class="flex justify-between text-green-600">
                        <span>ডিসকাউন্ট:</span>
                        <span>-৳<?php echo number_format($discount, 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between font-bold text-lg border-t pt-3">
                        <span>সর্বমোট:</span>
                        <span>৳<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
                
                <form method="POST" action="" id="checkoutForm">
                    <input type="hidden" name="place_order" value="1">
                    <input type="hidden" name="address_id" id="selected_address" value="<?php echo $addresses[0]['id'] ?? ''; ?>">
                    <input type="hidden" name="payment_method" value="cod" id="selected_payment">
                    
                    <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-bold">
                        <i class="fas fa-lock mr-2"></i>অর্ডার কনফার্ম করুন
                    </button>
                </form>
                
                <p class="text-xs text-gray-500 mt-4 text-center">
                    অর্ডার কনফার্ম করার মাধ্যমে আপনি আমাদের <a href="#" class="text-blue-600 hover:underline">শর্তাবলী</a> এবং <a href="#" class="text-blue-600 hover:underline">প্রাইভেসি পলিসি</a> মেনে নিচ্ছেন
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Select address
function selectAddress(addressId) {
    document.getElementById('selected_address').value = addressId;
    document.querySelectorAll('.address-option').forEach(el => {
        el.classList.remove('border-blue-500', 'bg-blue-50');
    });
    event.currentTarget.classList.add('border-blue-500', 'bg-blue-50');
}

// Select payment method
function selectPayment(method) {
    document.getElementById('selected_payment').value = method;
    document.querySelectorAll('.payment-option').forEach(el => {
        el.classList.remove('border-blue-500', 'bg-blue-50');
    });
    event.currentTarget.classList.add('border-blue-500', 'bg-blue-50');
}

// Form validation
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const addressId = document.getElementById('selected_address').value;
    
    if (!addressId) {
        e.preventDefault();
        alert('দয়া করে একটি ডেলিভারি ঠিকানা নির্বাচন করুন');
        return false;
    }
    
    // Additional validation can be added here
});

// Initialize selected address and payment
document.addEventListener('DOMContentLoaded', function() {
    const defaultAddress = document.querySelector('.address-option input[checked]');
    if (defaultAddress) {
        defaultAddress.closest('.address-option').classList.add('border-blue-500', 'bg-blue-50');
    }
    
    const defaultPayment = document.querySelector('.payment-option input[checked]');
    if (defaultPayment) {
        defaultPayment.closest('.payment-option').classList.add('border-blue-500', 'bg-blue-50');
    }
});
</script>

<?php include '../includes/footer.php'; ?>