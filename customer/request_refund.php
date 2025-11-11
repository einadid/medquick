<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get order ID
$order_id = $_GET['order_id'] ?? 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, a.* 
    FROM orders o
    JOIN addresses a ON o.shipping_address_id = a.id
    WHERE o.id = ? AND o.customer_id = ? AND o.status = 'delivered'
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    die("অর্ডার খুঁজে পাওয়া যায়নি বা রিফান্ডের জন্য উপযুক্ত নয়");
}

// Check if refund already requested
$stmt = $pdo->prepare("SELECT * FROM refunds WHERE order_id = ?");
$stmt->execute([$order_id]);
$existing_refund = $stmt->fetch();

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, m.name, m.image 
    FROM order_items oi
    JOIN medicines m ON oi.medicine_id = m.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Process refund request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_refund'])) {
    $reason = $_POST['reason'];
    $items = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    
    if (empty($items)) {
        $error = "অন্তত একটি আইটেম সিলেক্ট করুন";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Calculate total refund amount
            $total_refund = 0;
            $refund_items = [];
            
            foreach ($items as $item_id) {
                $quantity = min($quantities[$item_id] ?? 1, $order_items[array_search($item_id, array_column($order_items, 'id'))]['quantity']);
                
                if ($quantity > 0) {
                    $item = $order_items[array_search($item_id, array_column($order_items, 'id'))];
                    $amount = $item['unit_price'] * $quantity;
                    $total_refund += $amount;
                    
                    $refund_items[] = [
                        'order_item_id' => $item_id,
                        'quantity' => $quantity,
                        'amount' => $amount
                    ];
                }
            }
            
            if ($total_refund <= 0) {
                throw new Exception("বৈধ রিফান্ড অংক নয়");
            }
            
            // Create refund request
            $stmt = $pdo->prepare("
                INSERT INTO refunds (order_id, user_id, amount, reason, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $order_id,
                $_SESSION['user_id'],
                $total_refund,
                $reason
            ]);
            
            $refund_id = $pdo->lastInsertId();
            
            // Add refund items
            foreach ($refund_items as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO refund_items (refund_id, order_item_id, quantity, amount)
                    VALUES (?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $refund_id,
                    $item['order_item_id'],
                    $item['quantity'],
                    $item['amount']
                ]);
            }
            
            // Update order status
            $pdo->prepare("UPDATE orders SET status = 'refund_requested' WHERE id = ?")
                ->execute([$order_id]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "রিফান্ড রিকোয়েস্ট সফলভাবে জমা দেওয়া হয়েছে";
            header("Location: refunds.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "রিফান্ড রিকোয়েস্ট করতে সমস্যা: " . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">রিফান্ড রিকোয়েস্ট</h1>
    
    <?php if ($existing_refund): ?>
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <i class="fas fa-info-circle text-4xl text-blue-500 mb-4"></i>
            <h2 class="text-xl font-bold mb-2">রিফান্ড রিকোয়েস্ট করা হয়েছে</h2>
            <p class="text-gray-600 mb-4">আপনার রিফান্ড রিকোয়েস্ট বর্তমানে প্রক্রিয়াধীন আছে</p>
            <p class="text-sm text-gray-500">
                স্ট্যাটাস: 
                <span class="font-semibold <?php 
                    echo $existing_refund['status'] == 'approved' ? 'text-green-600' : 
                         ($existing_refund['status'] == 'rejected' ? 'text-red-600' : 'text-yellow-600');
                ?>">
                    <?php echo ucfirst($existing_refund['status']); ?>
                </span>
            </p>
            <a href="refunds.php" class="inline-block mt-4 text-blue-600 hover:underline">
                <i class="fas fa-arrow-left mr-1"></i> আমার রিফান্ডসমূহ দেখুন
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Order Details -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-bold mb-4">অর্ডার #<?php echo $order['id']; ?></h2>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="py-2 px-4 text-left">পণ্য</th>
                                    <th class="py-2 px-4 text-center">দাম</th>
                                    <th class="py-2 px-4 text-center">পরিমাণ</th>
                                    <th class="py-2 px-4 text-center">রিফান্ড</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr class="border-t">
                                    <td class="py-2 px-4">
                                        <div class="flex items-center">
                                            <img src="../assets/images/medicines/<?php echo $item['image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="w-10 h-10 object-cover rounded-lg mr-3">
                                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4 text-center">৳<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="py-2 px-4 text-center"><?php echo $item['quantity']; ?></td>
                                    <td class="py-2 px-4 text-center">
                                        <input type="checkbox" name="items[]" value="<?php echo $item['id']; ?>" 
                                               class="refund-item" data-max="<?php echo $item['quantity']; ?>"
                                               onchange="toggleQuantityInput(this)">
                                        <input type="number" name="quantities[<?php echo $item['id']; ?>]" 
                                               min="1" max="<?php echo $item['quantity']; ?>" 
                                               value="<?php echo $item['quantity']; ?>"
                                               class="w-16 px-2 py-1 border rounded quantity-input hidden">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Refund Reason -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">রিফান্ডের কারণ</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">কারণ*</label>
                            <select name="reason" class="w-full px-3 py-2 border rounded-lg" required>
                                <option value="">কারণ নির্বাচন করুন</option>
                                <option value="wrong_item">ভুল পণ্য পাঠানো হয়েছে</option>
                                <option value="damaged">পণ্য ক্ষতিগ্রস্ত</option>
                                <option value="expired">পণ্যের মেয়াদ উত্তীর্ণ</option>
                                <option value="not_required">আর প্রয়োজন নেই</option>
                                <option value="other">অন্যান্য</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">বিস্তারিত বিবরণ*</label>
                            <textarea name="details" class="w-full px-3 py-2 border rounded-lg" rows="4" required></textarea>
                        </div>
                        
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <p class="text-yellow-700 text-sm">
                                <i class="fas fa-info-circle mr-1"></i> 
                                রিফান্ড রিকোয়েস্ট জমা দেওয়ার পর আমাদের টিম ৩-৫ কার্যদিবসের মধ্যে আপনার রিকোয়েস্ট প্রসেস করবে।
                                রিফান্ডের টাকা আপনার অরিজিনাল পেমেন্ট মেথডে ফেরত দেওয়া হবে।
                            </p>
                        </div>
                        
                        <button type="submit" name="request_refund" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 font-bold">
                            রিফান্ড রিকোয়েস্ট জমা দিন
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Refund Summary -->
            <div>
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold mb-4">রিফান্ড সারাংশ</h2>
                    
                    <div class="mb-4">
                        <p class="text-gray-600">অর্ডার নম্বর: <span class="font-semibold">#<?php echo $order['id']; ?></span></p>
                        <p class="text-gray-600">অর্ডার তারিখ: <span class="font-semibold"><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></span></p>
                    </div>
                    
                    <div class="border-t pt-4">
                        <h3 class="font-semibold mb-2">নির্বাচিত আইটেমসমূহ:</h3>
                        <ul id="selectedItems" class="text-sm text-gray-600 mb-4">
                            <li>কোন আইটেম সিলেক্ট করা হয়নি</li>
                        </ul>
                        
                        <div class="border-t pt-4">
                            <div class="flex justify-between mb-2">
                                <span>মোট রিফান্ড যোগ্য:</span>
                                <span id="refundableAmount">৳0.00</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg">
                                <span>প্রত্যাশিত রিফান্ড:</span>
                                <span id="expectedRefund">৳0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleQuantityInput(checkbox) {
    const quantityInput = checkbox.nextElementSibling;
    if (checkbox.checked) {
        quantityInput.classList.remove('hidden');
        quantityInput.value = quantityInput.getAttribute('max');
    } else {
        quantityInput.classList.add('hidden');
    }
    updateRefundSummary();
}

function updateRefundSummary() {
    const selectedItems = document.querySelectorAll('.refund-item:checked');
    const selectedList = document.getElementById('selectedItems');
    let refundableAmount = 0;
    
    selectedList.innerHTML = '';
    
    if (selectedItems.length === 0) {
        selectedList.innerHTML = '<li>কোন আইটেম সিলেক্ট করা হয়নি</li>';
    } else {
        selectedItems.forEach(item => {
            const row = item.closest('tr');
            const itemName = row.querySelector('span').textContent;
            const price = parseFloat(row.querySelector('td:nth-child(2)').textContent.replace('৳', ''));
            const maxQty = parseInt(item.getAttribute('data-max'));
            const qtyInput = item.nextElementSibling;
            const qty = parseInt(qtyInput.value) || 0;
            const amount = price * qty;
            
            refundableAmount += amount;
            
            const li = document.createElement('li');
            li.className = 'flex justify-between mb-1';
            li.innerHTML = `
                <span>${itemName} x${qty}</span>
                <span>৳${amount.toFixed(2)}</span>
            `;
            selectedList.appendChild(li);
        });
    }
    
    document.getElementById('refundableAmount').textContent = '৳' + refundableAmount.toFixed(2);
    document.getElementById('expectedRefund').textContent = '৳' + refundableAmount.toFixed(2);
}

// Add event listeners
document.querySelectorAll('.refund-item').forEach(item => {
    item.addEventListener('change', updateRefundSummary);
});

document.querySelectorAll('.quantity-input').forEach(input => {
    input.addEventListener('change', function() {
        const max = parseInt(this.getAttribute('max'));
        const min = parseInt(this.getAttribute('min'));
        let value = parseInt(this.value) || 0;
        
        if (value > max) value = max;
        if (value < min) value = min;
        
        this.value = value;
        updateRefundSummary();
    });
});
</script>

<?php include '../includes/footer.php'; ?>