<?php
// ... existing code ...

// Fetch analytics data
$today = date('Y-m-d');
$first_day_of_month = date('Y-m-01');
$first_day_of_year = date('Y-01-01');

// Today's sales
$stmt = $pdo->prepare("
    SELECT COUNT(*) as orders, 
           SUM(total_amount) as revenue, 
           AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE DATE(order_date) = ? AND status != 'cancelled'
");
$stmt->execute([$today]);
$today_stats = $stmt->fetch();

// Monthly sales
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as orders,
        SUM(total_amount) as revenue,
        AVG(total_amount) as avg_order_value,
        COUNT(DISTINCT customer_id) as customers
    FROM orders 
    WHERE order_date >= ? AND status != 'cancelled'
");
$stmt->execute([$first_day_of_month]);
$monthly_stats = $stmt->fetch();

// Yearly sales
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as orders,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE order_date >= ? AND status != 'cancelled'
");
$stmt->execute([$first_day_of_year]);
$yearly_stats = $stmt->fetch();

// Top selling products
$stmt = $pdo->query("
    SELECT m.name, SUM(oi.quantity) as total_sold, SUM(oi.total_price) as revenue
    FROM order_items oi
    JOIN medicines m ON oi.medicine_id = m.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'delivered'
    GROUP BY m.id
    ORDER BY total_sold DESC
    LIMIT 5
");
$top_products = $stmt->fetchAll();

// Sales by category
$stmt = $pdo->query("
    SELECT m.category, SUM(oi.quantity) as total_sold, SUM(oi.total_price) as revenue
    FROM order_items oi
    JOIN medicines m ON oi.medicine_id = m.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'delivered'
    GROUP BY m.category
    ORDER BY revenue DESC
");
$sales_by_category = $stmt->fetchAll();

// Recent orders
$stmt = $pdo->query("
    SELECT o.id, o.order_date, o.total_amount, o.status, u.username
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    ORDER BY o.order_date DESC
    LIMIT 5
");
$recent_orders = $stmt->fetchAll();

// Monthly sales data for chart
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(order_date, '%Y-%m') as month,
        COUNT(*) as orders,
        SUM(total_amount) as revenue
    FROM orders 
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
    ORDER BY month
");
$monthly_sales_data = $stmt->fetchAll();
?>

<!-- ... existing HTML ... -->

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Today's Stats -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-lg font-semibold mb-4">আজকের বিক্রয়</h3>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span>অর্ডার সংখ্যা:</span>
                <span class="font-bold"><?php echo $today_stats['orders'] ?? 0; ?></span>
            </div>
            <div class="flex justify-between">
                <span>আয়:</span>
                <span class="font-bold">৳<?php echo number_format($today_stats['revenue'] ?? 0, 2); ?></span>
            </div>
            <div class="flex justify-between">
                <span>গড় অর্ডার মূল্য:</span>
                <span class="font-bold">৳<?php echo number_format($today_stats['avg_order_value'] ?? 0, 2); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Monthly Stats -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-lg font-semibold mb-4">এই মাসের বিক্রয়</h3>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span>অর্ডার সংখ্যা:</span>
                <span class="font-bold"><?php echo $monthly_stats['orders'] ?? 0; ?></span>
            </div>
            <div class="flex justify-between">
                <span>আয়:</span>
                <span class="font-bold">৳<?php echo number_format($monthly_stats['revenue'] ?? 0, 2); ?></span>
            </div>
            <div class="flex justify-between">
                <span>গ্রাহক সংখ্যা:</span>
                <span class="font-bold"><?php echo $monthly_stats['customers'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    
    <!-- Yearly Stats -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-lg font-semibold mb-4">এই বছরের বিক্রয়</h3>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span>অর্ডার সংখ্যা:</span>
                <span class="font-bold"><?php echo $yearly_stats['orders'] ?? 0; ?></span>
            </div>
            <div class="flex justify-between">
                <span>আয়:</span>
                <span class="font-bold">৳<?php echo number_format($yearly_stats['revenue'] ?? 0, 2); ?></span>
            </div>
            <div class="flex justify-between">
                <span>গড় মাসিক আয়:</span>
                <span class="font-bold">৳<?php echo number_format(($yearly_stats['revenue'] ?? 0) / (date('n') ?: 1), 2); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Sales Chart -->
<div class="bg-white rounded-lg shadow-lg p-6 mb-8">
    <h2 class="text-xl font-bold mb-4">মাসিক বিক্রয়</h2>
    <canvas id="salesChart" height="300"></canvas>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Top Selling Products -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-bold mb-4">সর্বাধিক বিক্রিত পণ্য</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 text-left">পণ্যের নাম</th>
                        <th class="py-2 px-4 text-center">বিক্রিত পরিমাণ</th>
                        <th class="py-2 px-4 text-right">আয়</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_products as $product): ?>
                    <tr class="border-t">
                        <td class="py-2 px-4"><?php echo htmlspecialchars($product['name']); ?></td>
                        <td class="py-2 px-4 text-center"><?php echo $product['total_sold']; ?> পিস</td>
                        <td class="py-2 px-4 text-right">৳<?php echo number_format($product['revenue'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Sales by Category -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-bold mb-4">ক্যাটাগরি অনুযায়ী বিক্রয়</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 text-left">ক্যাটাগরি</th>
                        <th class="py-2 px-4 text-center">বিক্রিত পরিমাণ</th>
                        <th class="py-2 px-4 text-right">আয়</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales_by_category as $category): ?>
                    <tr class="border-t">
                        <td class="py-2 px-4"><?php echo htmlspecialchars($category['category']); ?></td>
                        <td class="py-2 px-4 text-center"><?php echo $category['total_sold']; ?> পিস</td>
                        <td class="py-2 px-4 text-right">৳<?php echo number_format($category['revenue'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="bg-white rounded-lg shadow-lg p-6 mt-6">
    <h2 class="text-xl font-bold mb-4">সাম্প্রতিক অর্ডারসমূহ</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-2 px-4 text-left">অর্ডার নম্বর</th>
                    <th class="py-2 px-4 text-left">গ্রাহক</th>
                    <th class="py-2 px-4 text-left">তারিখ</th>
                    <th class="py-2 px-4 text-right">মোট</th>
                    <th class="py-2 px-4 text-center">স্ট্যাটাস</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order): ?>
                <tr class="border-t hover:bg-gray-50">
                    <td class="py-2 px-4">
                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:underline">
                            #<?php echo $order['id']; ?>
                        </a>
                    </td>
                    <td class="py-2 px-4"><?php echo htmlspecialchars($order['username']); ?></td>
                    <td class="py-2 px-4"><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                    <td class="py-2 px-4 text-right">৳<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td class="py-2 px-4 text-center">
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?php 
                            $status_colors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'confirmed' => 'bg-blue-100 text-blue-800',
                                'shipped' => 'bg-purple-100 text-purple-800',
                                'delivered' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800'
                            ];
                            echo $status_colors[strtolower($order['status'])] ?? 'bg-gray-100 text-gray-800';
                            ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($monthly_sales_data, 'month')); ?>,
        datasets: [{
            label: 'আয় (৳)',
            data: <?php echo json_encode(array_column($monthly_sales_data, 'revenue')); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }, {
            label: 'অর্ডার সংখ্যা',
            data: <?php echo json_encode(array_column($monthly_sales_data, 'orders')); ?>,
            type: 'line',
            fill: false,
            borderColor: 'rgba(255, 99, 132, 1)',
            backgroundColor: 'rgba(255, 99, 132, 0.5)',
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'আয় (৳)'
                }
            },
            y1: {
                position: 'right',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'অর্ডার সংখ্যা'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
</script>

<!-- ... existing code ... -->