<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Get order ID from URL
$order_id = $_GET['order_id'] ?? 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, a.* 
    FROM orders o
    JOIN addresses a ON o.shipping_address_id = a.id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    die("অর্ডার খুঁজে পাওয়া যায়নি!");
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, m.name, m.image 
    FROM order_items oi
    JOIN medicines m ON oi.medicine_id = m.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="text-green-500 text-6xl mb-4">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1 class="text-3xl font-bold text-green-600 mb-4">অর্ডার সফলভাবে সম্পন্ন হয়েছে!</h1>
        <p class="text-gray-600 mb-6">আপনার অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে। নিচে আপনার অর্ডারের বিবরণ দেখানো হলো:</p>
        
        <div class="bg-gray-50 rounded-lg p-6 mb-6 text-left">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">অর্ডার তথ্য</h3>
                    <p><span class="font-medium">অর্ডার নম্বর:</span> #<?php echo $order['id']; ?></p>
                    <p><span class="font-medium">অর্ডারের তারিখ:</span> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
                    <p><span class="font-medium">পেমেন্ট মেথড:</span> 
                        <?php 
                        $payment_methods = [
                            'cod' => 'ক্যাশ অন ডেলিভারি',
                            'bkash' => 'bKash',
                            'nagad' => 'নগদ',
                            'card' => 'ক্রেডিট/ডেবিট কার্ড'
                        ];
                        echo $payment_methods[$order['payment_method']] ?? $order['payment_method']; 
                        ?>
                    </p>
                    <p><span class="font-medium">স্ট্যাটাস:</span> 
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-sm rounded-full">
                            পেন্ডিং
                        </span>
                    </p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">বিলিং ঠিকানা</h3>
                    <p><?php echo htmlspecialchars($order['name']); ?></p>
                    <p><?php echo htmlspecialchars($order['address']); ?>, <?php echo htmlspecialchars($order['area']); ?></p>
                    <p><?php echo htmlspecialchars($order['city']); ?></p>
                    <p>ফোন: <?php echo htmlspecialchars($order['phone']); ?></p>
                </div>
            </div>
            
            <h3 class="font-semibold text-gray-700 mb-2">অর্ডারকৃত আইটেম</h3>
            <div class="border rounded-lg overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 text-left">পণ্য</th>
                            <th class="py-2 px-4 text-center">পরিমাণ</th>
                            <th class="py-2 px-4 text-right">দাম</th>
                            <th class="py-2 px-4 text-right">মোট</th>
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
                            <td class="py-2 px-4 text-center"><?php echo $item['quantity']; ?></td>
                            <td class="py-2 px-4 text-right">৳<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="py-2 px-4 text-right">৳<?php echo number_format($item['total_price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <tr class="border-t font-semibold">
                            <td colspan="3" class="py-2 px-4 text-right">সাবটোটাল:</td>
                            <td class="py-2 px-4 text-right">৳<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="py-2 px-4 text-right">ডেলিভারি চার্জ:</td>
                            <td class="py-2 px-4 text-right">৳<?php echo number_format($order['delivery_charge'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="py-2 px-4 text-right">ডিসকাউন্ট:</td>
                            <td class="py-2 px-4 text-right">-৳<?php echo number_format($order['discount'], 2); ?></td>
                        </tr>
                        <tr class="border-t font-bold text-lg">
                            <td colspan="3" class="py-2 px-4 text-right">সর্বমোট:</td>
                            <td class="py-2 px-4 text-right">৳<?php echo number_format($order['final_amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="index.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                <i class="fas fa-home mr-2"></i>হোমপেজে ফিরে যান
            </a>
            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
                <i class="fas fa-list mr-2"></i>অর্ডার বিস্তারিত দেখুন
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>