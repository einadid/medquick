<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Fetch orders for this customer
$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) as item_count, SUM(oi.quantity) as total_quantity
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.customer_id = ?
    GROUP BY o.id
    ORDER BY o.order_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">আমার অর্ডারসমূহ</h1>
    
    <?php if (count($orders) > 0): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="hidden md:grid grid-cols-12 gap-4 bg-gray-100 p-4 font-semibold">
                <div class="col-span-2">অর্ডার নং</div>
                <div class="col-span-2">তারিখ</div>
                <div class="col-span-2">আইটেম</div>
                <div class="col-span-2">পরিমাণ</div>
                <div class="col-span-2">মোট মূল্য</div>
                <div class="col-span-2">স্ট্যাটাস</div>
            </div>
            
            <?php foreach ($orders as $order): 
                $status_classes = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'processing' => 'bg-blue-100 text-blue-800',
                    'shipped' => 'bg-purple-100 text-purple-800',
                    'delivered' => 'bg-green-100 text-green-800',
                    'cancelled' => 'bg-red-100 text-red-800'
                ];
            ?>
            <a href="order_details.php?id=<?php echo $order['id']; ?>" 
               class="block hover:bg-gray-50 border-b">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 p-4">
                    <div class="md:col-span-2 font-semibold md:font-normal">
                        <span class="md:hidden">অর্ডার নং: </span>#<?php echo $order['id']; ?>
                    </div>
                    <div class="md:col-span-2">
                        <span class="md:hidden">তারিখ: </span>
                        <?php echo date('d/m/Y', strtotime($order['order_date'])); ?>
                    </div>
                    <div class="md:col-span-2">
                        <span class="md:hidden">আইটেম: </span>
                        <?php echo $order['item_count']; ?> টি আইটেম
                    </div>
                    <div class="md:col-span-2">
                        <span class="md:hidden">পরিমাণ: </span>
                        <?php echo $order['total_quantity']; ?> পিস
                    </div>
                    <div class="md:col-span-2 font-semibold">
                        <span class="md:hidden">মোট মূল্য: </span>
                        ৳<?php echo number_format($order['total_amount'] + 60, 2); ?>
                    </div>
                    <div class="md:col-span-2">
                        <span class="px-3 py-1 rounded-full text-sm <?php echo $status_classes[strtolower($order['status'])]; ?>">
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
            </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12 bg-white rounded-lg shadow-md">
            <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
            <h2 class="text-xl font-bold mb-2">কোন অর্ডার পাওয়া যায়নি</h2>
            <p class="text-gray-600 mb-6">আপনি এখনো কোন পণ্য অর্ডার করেননি</p>
            <a href="index.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                <i class="fas fa-shopping-cart mr-2"></i>এখনই শপিং করুন
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>