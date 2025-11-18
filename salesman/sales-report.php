<?php
$pageTitle = 'Sales Report';
require_once '../includes/header.php';
requireRole('salesman');

$user = getCurrentUser();
$shopId = $user['shop_id'];

if (!$shopId) {
    die('Error: No shop assigned');
}

// Date filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get shop info
$shop = Database::getInstance()->fetchOne("SELECT * FROM shops WHERE id = ?", [$shopId]);

// Summary Statistics
$summary = Database::getInstance()->fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        AVG(total_amount) as avg_order_value
    FROM parcels
    WHERE shop_id = ? AND DATE(created_at) BETWEEN ? AND ?
", [$shopId, $startDate, $endDate]);

// Daily Sales
$dailySales = Database::getInstance()->fetchAll("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders,
        SUM(total_amount) as sales
    FROM parcels
    WHERE shop_id = ? AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date DESC
", [$shopId, $startDate, $endDate]);

// Top Medicines
$topMedicines = Database::getInstance()->fetchAll("
    SELECT 
        m.name,
        m.generic_name,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN medicines m ON oi.medicine_id = m.id
    JOIN parcels p ON oi.parcel_id = p.id
    WHERE oi.shop_id = ? AND DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY m.id
    ORDER BY total_sold DESC
    LIMIT 10
", [$shopId, $startDate, $endDate]);

// Sales by Status
$salesByStatus = Database::getInstance()->fetchAll("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(total_amount) as amount
    FROM parcels
    WHERE shop_id = ? AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY status
", [$shopId, $startDate, $endDate]);

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Summary
    fputcsv($output, ['Sales Report - ' . $shop['name']]);
    fputcsv($output, ['Period', $startDate . ' to ' . $endDate]);
    fputcsv($output, ['']);
    
    // Summary stats
    fputcsv($output, ['Summary Statistics']);
    fputcsv($output, ['Total Orders', $summary['total_orders']]);
    fputcsv($output, ['Total Sales', $summary['total_sales']]);
    fputcsv($output, ['Average Order Value', $summary['avg_order_value']]);
    fputcsv($output, ['']);
    
    // Daily sales
    fputcsv($output, ['Daily Sales']);
    fputcsv($output, ['Date', 'Orders', 'Sales']);
    foreach ($dailySales as $row) {
        fputcsv($output, [$row['date'], $row['orders'], $row['sales']]);
    }
    
    fclose($output);
    exit;
}
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold mb-4">Sales Report</h2>
    <p class="text-gray-600">Shop: <?php echo clean($shop['name']); ?></p>
    
    <!-- Date Filter -->
    <form method="GET" class="flex gap-2 mt-4">
        <div>
            <label class="block mb-1 text-sm">Start Date</label>
            <input type="date" name="start_date" value="<?php echo $startDate; ?>" class="p-2 border-2 border-gray-400">
        </div>
        <div>
            <label class="block mb-1 text-sm">End Date</label>
            <input type="date" name="end_date" value="<?php echo $endDate; ?>" class="p-2 border-2 border-gray-400">
        </div>
        <div>
            <label class="block mb-1 text-sm">&nbsp;</label>
            <button type="submit" class="p-2 bg-blue-600 text-white font-bold">FILTER</button>
        </div>
        <div>
            <label class="block mb-1 text-sm">&nbsp;</label>
            <a href="?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&export=csv" 
               class="inline-block p-2 bg-green-600 text-white font-bold">
                EXPORT CSV
            </a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="text-gray-600">Total Orders</div>
        <div class="text-3xl font-bold text-blue-600"><?php echo $summary['total_orders'] ?? 0; ?></div>
    </div>
    
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="text-gray-600">Total Sales</div>
        <div class="text-3xl font-bold text-green-600"><?php echo formatPrice($summary['total_sales'] ?? 0); ?></div>
    </div>
    
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="text-gray-600">Avg Order Value</div>
        <div class="text-3xl font-bold text-purple-600"><?php echo formatPrice($summary['avg_order_value'] ?? 0); ?></div>
    </div>
</div>

<!-- Daily Sales Table -->
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h3 class="text-xl font-bold mb-4">Daily Sales Breakdown</h3>
    
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left border">Date</th>
                <th class="p-2 text-right border">Orders</th>
                <th class="p-2 text-right border">Sales</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dailySales as $row): ?>
            <tr>
                <td class="p-2 border"><?php echo date('M d, Y (D)', strtotime($row['date'])); ?></td>
                <td class="p-2 border text-right"><?php echo $row['orders']; ?></td>
                <td class="p-2 border text-right font-bold"><?php echo formatPrice($row['sales']); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="bg-gray-100 font-bold">
                <td class="p-2 border">TOTAL</td>
                <td class="p-2 border text-right"><?php echo array_sum(array_column($dailySales, 'orders')); ?></td>
                <td class="p-2 border text-right"><?php echo formatPrice(array_sum(array_column($dailySales, 'sales'))); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Top Selling Medicines -->
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h3 class="text-xl font-bold mb-4">Top 10 Selling Medicines</h3>
    
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left border">Medicine</th>
                <th class="p-2 text-left border">Generic Name</th>
                <th class="p-2 text-right border">Quantity Sold</th>
                <th class="p-2 text-right border">Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topMedicines as $med): ?>
            <tr>
                <td class="p-2 border font-bold"><?php echo clean($med['name']); ?></td>
                <td class="p-2 border"><?php echo clean($med['generic_name']); ?></td>
                <td class="p-2 border text-right"><?php echo $med['total_sold']; ?> units</td>
                <td class="p-2 border text-right font-bold"><?php echo formatPrice($med['revenue']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Sales by Status -->
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h3 class="text-xl font-bold mb-4">Sales by Order Status</h3>
    
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left border">Status</th>
                <th class="p-2 text-right border">Count</th>
                <th class="p-2 text-right border">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($salesByStatus as $status): ?>
            <tr>
                <td class="p-2 border">
                    <span class="px-2 py-1 text-xs bg-gray-100 border">
                        <?php echo strtoupper(str_replace('_', ' ', $status['status'])); ?>
                    </span>
                </td>
                <td class="p-2 border text-right"><?php echo $status['count']; ?></td>
                <td class="p-2 border text-right font-bold"><?php echo formatPrice($status['amount']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>