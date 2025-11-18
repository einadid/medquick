<?php
$pageTitle = 'Reports';
require_once '../includes/header.php';
requireRole('admin');

// Date filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Sales Report
$salesReport = Database::getInstance()->fetchAll("
    SELECT DATE(o.created_at) as date, 
           COUNT(o.id) as total_orders,
           SUM(o.total_amount) as total_revenue
    FROM orders o
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY DATE(o.created_at)
    ORDER BY date DESC
", [$startDate, $endDate]);

// Shop Performance
$shopPerformance = Database::getInstance()->fetchAll("
    SELECT s.name, s.city,
           COUNT(p.id) as total_parcels,
           SUM(p.total_amount) as total_revenue,
           AVG(p.total_amount) as avg_order_value
    FROM shops s
    LEFT JOIN parcels p ON s.id = p.shop_id
    WHERE DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY s.id
    ORDER BY total_revenue DESC
", [$startDate, $endDate]);

// Top Selling Medicines
$topMedicines = Database::getInstance()->fetchAll("
    SELECT m.name, m.generic_name,
           SUM(oi.quantity) as total_sold,
           SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    JOIN medicines m ON oi.medicine_id = m.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY m.id
    ORDER BY total_sold DESC
    LIMIT 20
", [$startDate, $endDate]);

// Low Stock Alert
$lowStock = Database::getInstance()->fetchAll("
    SELECT m.name, s.name as shop_name, sm.stock, sm.batch_number
    FROM shop_medicines sm
    JOIN medicines m ON sm.medicine_id = m.id
    JOIN shops s ON sm.shop_id = s.id
    WHERE sm.stock < 20
    ORDER BY sm.stock ASC
");

// Expiring Medicines
$expiringMedicines = Database::getInstance()->fetchAll("
    SELECT m.name, s.name as shop_name, sm.stock, sm.expiry_date, sm.batch_number,
           DATEDIFF(sm.expiry_date, CURDATE()) as days_left
    FROM shop_medicines sm
    JOIN medicines m ON sm.medicine_id = m.id
    JOIN shops s ON sm.shop_id = s.id
    WHERE sm.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
    AND sm.expiry_date >= CURDATE()
    ORDER BY sm.expiry_date ASC
");

// Export to CSV
if (isset($_GET['export'])) {
    $reportType = $_GET['export'];
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    switch ($reportType) {
        case 'sales':
            fputcsv($output, ['Date', 'Total Orders', 'Total Revenue']);
            foreach ($salesReport as $row) {
                fputcsv($output, [$row['date'], $row['total_orders'], $row['total_revenue']]);
            }
            break;
        case 'shop_performance':
            fputcsv($output, ['Shop Name', 'City', 'Total Parcels', 'Total Revenue', 'Avg Order Value']);
            foreach ($shopPerformance as $row) {
                fputcsv($output, [$row['name'], $row['city'], $row['total_parcels'], $row['total_revenue'], $row['avg_order_value']]);
            }
            break;
        case 'top_medicines':
            fputcsv($output, ['Medicine', 'Generic Name', 'Total Sold', 'Total Revenue']);
            foreach ($topMedicines as $row) {
                fputcsv($output, [$row['name'], $row['generic_name'], $row['total_sold'], $row['total_revenue']]);
            }
            break;
    }
    
    fclose($output);
    exit;
}
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold mb-4">Reports & Analytics</h2>
    
    <!-- Date Filter -->
    <form method="GET" class="flex gap-2">
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
    </form>
</div>

<!-- Sales Report -->
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold">Daily Sales Report</h3>
        <a href="?export=sales&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="px-4 py-2 bg-green-600 text-white text-sm font-bold">
            EXPORT CSV
        </a>
    </div>
    
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left border">Date</th>
                <th class="p-2 text-right border">Total Orders</th>
                <th class="p-2 text-right border">Total Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($salesReport as $row): ?>
            <tr>
                <td class="p-2 border"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                <td class="p-2 border text-right"><?php echo $row['total_orders']; ?></td>
                <td class="p-2 border text-right font-bold"><?php echo formatPrice($row['total_revenue']); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="bg-gray-100 font-bold">
                <td class="p-2 border">TOTAL</td>
                <td class="p-2 border text-right"><?php echo array_sum(array_column($salesReport, 'total_orders')); ?></td>
                <td class="p-2 border text-right"><?php echo formatPrice(array_sum(array_column($salesReport, 'total_revenue'))); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Shop Performance -->
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold">Shop Performance</h3>
        <a href="?export=shop_performance&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="px-4 py-2 bg-green-600 text-white text-sm font-bold">
            EXPORT CSV
        </a>
    </div>
    
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left border">Shop</th>
                <th class="p-2 text-left border">City</th>
                <th class="p-2 text-right border">Total Parcels</th>
                <th class="p-2 text-right border">Total Revenue</th>
                <th class="p-2 text-right border">Avg Order Value</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($shopPerformance as $row): ?>
            <tr>
                <td class="p-2 border font-bold"><?php echo clean($row['name']); ?></td>
                <td class="p-2 border"><?php echo clean($row['city']); ?></td>
                <td class="p-2 border text-right"><?php echo $row['total_parcels']; ?></td>
                <td class="p-2 border text-right font-bold"><?php echo formatPrice($row['total_revenue']); ?></td>
                <td class="p-2 border text-right"><?php echo formatPrice($row['avg_order_value']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Top Medicines -->
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold">Top 20 Selling Medicines</h3>
        <a href="?export=top_medicines&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
           class="px-4 py-2 bg-green-600 text-white text-sm font-bold">
            EXPORT CSV
        </a>
    </div>
    
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left border">Medicine</th>
                <th class="p-2 text-left border">Generic Name</th>
                <th class="p-2 text-right border">Total Sold</th>
                <th class="p-2 text-right border">Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topMedicines as $row): ?>
            <tr>
                <td class="p-2 border font-bold"><?php echo clean($row['name']); ?></td>
                <td class="p-2 border"><?php echo clean($row['generic_name']); ?></td>
                <td class="p-2 border text-right"><?php echo $row['total_sold']; ?> units</td>
                <td class="p-2 border text-right font-bold"><?php echo formatPrice($row['total_revenue']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Low Stock Alert -->
<?php if (!empty($lowStock)): ?>
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h3 class="text-xl font-bold mb-4 text-red-600">⚠️ Low Stock Alert (<?php echo count($lowStock); ?> items)</h3>
    
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-red-100">
            <tr>
                <th class="p-2 text-left border">Medicine</th>
                <th class="p-2 text-left border">Shop</th>
                <th class="p-2 text-left border">Batch</th>
                <th class="p-2 text-right border">Stock</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lowStock as $row): ?>
            <tr>
                <td class="p-2 border"><?php echo clean($row['name']); ?></td>
                <td class="p-2 border"><?php echo clean($row['shop_name']); ?></td>
                <td class="p-2 border text-sm"><?php echo clean($row['batch_number']); ?></td>
                <td class="p-2 border text-right font-bold text-red-600"><?php echo $row['stock']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Expiring Medicines -->
<?php if (!empty($expiringMedicines)): ?>
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h3 class="text-xl font-bold mb-4 text-yellow-600">⏰ Expiring Soon (<?php echo count($expiringMedicines); ?> items)</h3>
    
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-yellow-100">
            <tr>
                <th class="p-2 text-left border">Medicine</th>
                <th class="p-2 text-left border">Shop</th>
                <th class="p-2 text-left border">Batch</th>
                <th class="p-2 text-right border">Stock</th>
                <th class="p-2 text-left border">Expiry Date</th>
                <th class="p-2 text-right border">Days Left</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expiringMedicines as $row): ?>
            <tr>
                <td class="p-2 border"><?php echo clean($row['name']); ?></td>
                <td class="p-2 border"><?php echo clean($row['shop_name']); ?></td>
                <td class="p-2 border text-sm"><?php echo clean($row['batch_number']); ?></td>
                <td class="p-2 border text-right"><?php echo $row['stock']; ?></td>
                <td class="p-2 border"><?php echo date('M d, Y', strtotime($row['expiry_date'])); ?></td>
                <td class="p-2 border text-right font-bold text-yellow-600"><?php echo $row['days_left']; ?> days</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>