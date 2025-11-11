<?php
session_start();
require '../includes/db_connect.php';

// Check if user is salesman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'salesman') {
    header('Location: ../login.php');
    exit();
}

// Get salesman's shop
$shop_id = $_SESSION['shop_id'];

// Fetch today's sales
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_sales, 
           SUM(total_amount) as total_amount,
           SUM(quantity) as total_quantity
    FROM sales 
    WHERE shop_id = ? 
    AND DATE(sale_date) = ?
");
$stmt->execute([$shop_id, $today]);
$today_sales = $stmt->fetch();

// Fetch low stock medicines
$stmt = $pdo->prepare("
    SELECT m.name, i.quantity, m.reorder_level 
    FROM inventory i
    JOIN medicines m ON i.medicine_id = m.id
    WHERE i.shop_id = ? 
    AND i.quantity <= m.reorder_level
    ORDER BY i.quantity ASC
    LIMIT 5
");
$stmt->execute([$shop_id]);
$low_stock = $stmt->fetchAll();

// Fetch recent sales
$stmt = $pdo->prepare("
    SELECT s.*, m.name as medicine_name 
    FROM sales s
    JOIN medicines m ON s.medicine_id = m.id
    WHERE s.shop_id = ?
    ORDER BY s.sale_date DESC
    LIMIT 5
");
$stmt->execute([$shop_id]);
$recent_sales = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">সেলসম্যান ড্যাশবোর্ড</h1>
    
    <!-- Sales Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full mr-4">
                    <i class="fas fa-shopping-cart text-blue-500 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">আজকের বিক্রয়</p>
                    <p class="text-2xl font-bold"><?php echo $today_sales['total_sales'] ?? 0; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full mr-4">
                    <i class="fas fa-money-bill-wave text-green-500 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">আজকের আয়</p>
                    <p class="text-2xl font-bold">৳<?php echo number_format($today_sales['total_amount'] ?? 0, 2); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-full mr-4">
                    <i class="fas fa-boxes text-purple-500 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">মোট বিক্রিত পণ্য</p>
                    <p class="text-2xl font-bold"><?php echo $today_sales['total_quantity'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Low Stock Warning -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4">কম স্টক সতর্কতা</h2>
            
            <?php if (count($low_stock) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-2 px-4 text-left">মেডিসিন</th>
                                <th class="py-2 px-4 text-center">বর্তমান স্টক</th>
                                <th class="py-2 px-4 text-center">রিওর্ডার লেভেল</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock as $item): ?>
                            <tr class="border-t">
                                <td class="py-2 px-4"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="py-2 px-4 text-center text-red-600"><?php echo $item['quantity']; ?></td>
                                <td class="py-2 px-4 text-center"><?php echo $item['reorder_level']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-green-600">কোন কম স্টক সতর্কতা নেই।</p>
            <?php endif; ?>
        </div>
        
        <!-- Recent Sales -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4">সাম্প্রতিক বিক্রয়</h2>
            
            <?php if (count($recent_sales) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-2 px-4 text-left">তারিখ</th>
                                <th class="py-2 px-4 text-left">মেডিসিন</th>
                                <th class="py-2 px-4 text-center">পরিমাণ</th>
                                <th class="py-2 px-4 text-right">মোট</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_sales as $sale): ?>
                            <tr class="border-t">
                                <td class="py-2 px-4"><?php echo date('H:i', strtotime($sale['sale_date'])); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($sale['medicine_name']); ?></td>
                                <td class="py-2 px-4 text-center"><?php echo $sale['quantity']; ?></td>
                                <td class="py-2 px-4 text-right">৳<?php echo number_format($sale['total_amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>কোন বিক্রয় রেকর্ড পাওয়া যায়নি।</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="new_sale.php" class="bg-blue-600 text-white p-4 rounded-lg text-center hover:bg-blue-700">
            <i class="fas fa-cash-register text-2xl mb-2"></i>
            <h3 class="font-semibold">নতুন বিক্রয়</h3>
        </a>
        
        <a href="inventory.php" class="bg-green-600 text-white p-4 rounded-lg text-center hover:bg-green-700">
            <i class="fas fa-boxes text-2xl mb-2"></i>
            <h3 class="font-semibold">স্টক দেখুন</h3>
        </a>
        
        <a href="sales_report.php" class="bg-purple-600 text-white p-4 rounded-lg text-center hover:bg-purple-700">
            <i class="fas fa-chart-bar text-2xl mb-2"></i>
            <h3 class="font-semibold">বিক্রয় রিপোর্ট</h3>
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>