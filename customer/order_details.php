<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get order ID from URL
$order_id = $_GET['id'] ?? 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, oi.quantity, m.name, m.price, m.image
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN medicines m ON oi.medicine_id = m.id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order_items = $stmt->fetchAll();

if (count($order_items) === 0) {
    die("অর্ডার খুঁজে পাওয়া যায়নি!");
}

$order = $order_items[0]; // First item contains order details
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">অর্ডার বিবরণ #<?php echo $order['id']; ?></h1>
        <a href="orders.php" class="text-blue-600 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i> অর্ডার তালিকায় ফিরে যান
        </a>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Summary -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">অর্ডার আইটেমসমূহ</h2>
                
                <div class="space-y-4">
                    <?php foreach ($order_items as $item): ?>
                    <div class="flex items-center border-b pb-4">
                        <div class="w-20 h-20 bg-gray-200 rounded-lg overflow-hidden mr-4">
                            <img src="../assets/images/medicines/<?php echo $item['image'] ?: 'default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="flex-grow">
                            <h3 class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-gray-600 text-sm">পরিমাণ: <?php echo $item['quantity']; ?> পিস</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">৳<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                            <p class="text-sm text-gray-600">৳<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-6 border-t pt-4">
                    <div class="flex justify-between py-2">
                        <span>সাবটোটাল:</span>
                        <span>৳<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span>ডেলিভারি চার্জ:</span>
                        <span>৳60.00</span>
                    </div>
                    <div class="flex justify-between py-2 font-bold text-lg">
                        <span>সর্বমোট:</span>
                        <span>৳<?php echo number_format($order['total_amount'] + 60, 2); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Order Status Timeline -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">অর্ডার স্ট্যাটাস</h2>
                
                <?php
                $statuses = [
                    'pending' => 'পেন্ডিং',
                    'processing' => 'প্রসেসিং',
                    'shipped' => 'শিপড',
                    'delivered' => 'ডেলিভার্ড'
                ];
                
                $current_status = strtolower($order['status']);
                $status_icons = [
                    'pending' => 'fas fa-clock',
                    'processing' => 'fas fa-cog',
                    'shipped' => 'fas fa-shipping-fast',
                    'delivered' => 'fas fa-check-circle'
                ];
                ?>
                
                <div class="relative">
                    <div class="absolute left-4 top-0 h-full w-0.5 bg-gray-200"></div>
                    
                    <?php $i = 0; foreach ($statuses as $status => $text): 
                        $is_completed = array_search($status, array_keys($statuses)) <= array_search($current_status, array_keys($statuses));
                        $is_current = $status === $current_status;
                    ?>
                    <div class="relative mb-8 pl-12">
                        <div class="absolute left-0 -ml-4 w-8 h-8 rounded-full 
                                    <?php echo $is_completed ? 'bg-green-500' : 'bg-gray-300'; ?> 
                                    flex items-center justify-center text-white">
                            <i class="<?php echo $status_icons[$status]; ?>"></i>
                        </div>
                        
                        <h3 class="font-semibold <?php echo $is_completed ? 'text-green-600' : 'text-gray-500'; ?>">
                            <?php echo $text; ?>
                        </h3>
                        
                        <?php if ($is_current): ?>
                            <p class="text-sm text-gray-500 mt-1">
                                বর্তমান স্ট্যাটাস
                                <?php if ($status === 'shipped'): ?>
                                    - ট্র্যাকিং নম্বর: <?php echo $order['tracking_number'] ?? 'N/A'; ?>
                                <?php endif; ?>
                            </p>
                        <?php elseif ($is_completed): ?>
                            <p class="text-sm text-gray-500 mt-1">
                                সম্পন্ন হয়েছে 
                                <?php 
                                $date_field = $status . '_date';
                                if (!empty($order[$date_field])) {
                                    echo date('d/m/Y H:i', strtotime($order[$date_field]));
                                }
                                ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Information -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">অর্ডার তথ্য</h2>
                
                <div class="space-y-3">
                    <div>
                        <h3 class="font-semibold text-gray-700">অর্ডার নম্বর</h3>
                        <p>#<?php echo $order['id']; ?></p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-700">অর্ডারের তারিখ</h3>
                        <p><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-700">পেমেন্ট মেথড</h3>
                        <p>ক্যাশ অন ডেলিভারি (COD)</p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-700">স্ট্যাটাস</h3>
                        <span class="px-3 py-1 rounded-full text-sm 
                              <?php 
                              $status_classes = [
                                  'pending' => 'bg-yellow-100 text-yellow-800',
                                  'processing' => 'bg-blue-100 text-blue-800',
                                  'shipped' => 'bg-purple-100 text-purple-800',
                                  'delivered' => 'bg-green-100 text-green-800',
                                  'cancelled' => 'bg-red-100 text-red-800'
                              ];
                              echo $status_classes[strtolower($order['status'])]; 
                              ?>">
                            <?php 
                            $status_text = [
                                'pending' => 'পেন্ডিং',
                                'processing' => 'প্রসেসিং',
                                'shipped' => 'শিপড',
                                'delivered' => 'ডেলিভার্ড',
                                'cancelled' => 'বাতিল'
                            ];
                            echo $status_text[strtolower($order['status'])]; 
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Delivery Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">ডেলিভারি তথ্য</h2>
                
                <div class="space-y-3">
                    <div>
                        <h3 class="font-semibold text-gray-700">গ্রাহকের নাম</h3>
                        <p><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-700">ফোন নম্বর</h3>
                        <p><?php echo htmlspecialchars($order['phone']); ?></p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-700">ডেলিভারি ঠিকানা</h3>
                        <p class="whitespace-pre-line"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    </div>
                    
                    <?php if (!empty($order['delivery_notes'])): ?>
                    <div>
                        <h3 class="font-semibold text-gray-700">ডেলিভারি নোট</h3>
                        <p class="whitespace-pre-line"><?php echo htmlspecialchars($order['delivery_notes']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Actions -->
            <?php if (strtolower($order['status']) === 'pending'): ?>
            <div class="mt-6">
                <button onclick="cancelOrder(<?php echo $order['id']; ?>)" 
                        class="w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 font-semibold">
                    <i class="fas fa-times-circle mr-2"></i>অর্ডার বাতিল করুন
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function cancelOrder(orderId) {
    if (confirm('আপনি কি নিশ্চিত যে আপনি এই অর্ডারটি বাতিল করতে চান?')) {
        fetch('cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('অর্ডারটি সফলভাবে বাতিল করা হয়েছে।');
                location.reload();
            } else {
                alert('অর্ডার বাতিল করতে সমস্যা হয়েছে: ' + (data.error || 'অজানা ত্রুটি'));
            }
        })
        .catch(error => {
            alert('একটি ত্রুটি ঘটেছে: ' + error.message);
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>