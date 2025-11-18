<?php
$pageTitle = 'Admin Dashboard';
require_once '../includes/header.php';
requireRole('admin');

// Get statistics
$totalRevenue = Database::getInstance()->fetchOne("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'")['total'] ?? 0;
$todayOrders = Database::getInstance()->fetchOne("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")['count'];
$pendingParcels = Database::getInstance()->fetchOne("SELECT COUNT(*) as count FROM parcels WHERE status IN ('pending', 'confirmed')")['count'];
$lowStock = Database::getInstance()->fetchAll("SELECT COUNT(*) as count FROM shop_medicines WHERE stock < 20")['0']['count'] ?? 0;

// Get sales by shop
$salesByShop = Database::getInstance()->fetchAll("
    SELECT s.name, SUM(p.total_amount) as total
    FROM parcels p
    JOIN shops s ON p.shop_id = s.id
    GROUP BY s.id
");

// Get top medicines
$topMedicines = Database::getInstance()->fetchAll("
    SELECT m.name, SUM(oi.quantity) as total_sold
    FROM order_items oi
    JOIN medicines m ON oi.medicine_id = m.id
    GROUP BY m.id
    ORDER BY total_sold DESC
    LIMIT 10
");
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">Admin Dashboard</h2>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border-2 border-gray-300 p-4">
        <div class="text-gray-600">Total Revenue</div>
        <div class="text-2xl font-bold"><?php echo formatPrice($totalRevenue); ?></div>
    </div>
    <div class="bg-white border-2 border-gray-300 p-4">
        <div class="text-gray-600">Today's Orders</div>
        <div class="text-2xl font-bold"><?php echo $todayOrders; ?></div>
    </div>
    <div class="bg-white border-2 border-gray-300 p-4">
        <div class="text-gray-600">Pending Parcels</div>
        <div class="text-2xl font-bold"><?php echo $pendingParcels; ?></div>
    </div>
    <div class="bg-white border-2 border-gray-300 p-4">
        <div class="text-gray-600">Low Stock Items</div>
        <div class="text-2xl font-bold text-red-600"><?php echo $lowStock; ?></div>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <!-- Sales by Shop -->
    <div class="bg-white border-2 border-gray-300 p-4">
        <h3 class="font-bold mb-4">Sales by Shop</h3>
        <canvas id="shopSalesChart"></canvas>
    </div>
    
    <!-- Top Medicines -->
    <div class="bg-white border-2 border-gray-300 p-4">
        <h3 class="font-bold mb-4">Top 10 Medicines</h3>
        <canvas id="topMedicinesChart"></canvas>
    </div>
</div>

<script>
// Shop Sales Chart
const shopCtx = document.getElementById('shopSalesChart').getContext('2d');
new Chart(shopCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($salesByShop, 'name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($salesByShop, 'total')); ?>,
            backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444']
        }]
    }
});

// Top Medi  cines Chart
const medCtx = document.getElementById('topMedicinesChart').getContext('2d');
new Chart(medCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($topMedicines, 'name')); ?>,
        datasets: [{
            label: 'Units Sold',
            data: <?php echo json_encode(array_column($topMedicines, 'total_sold')); ?>,
            backgroundColor: '#3B82F6'
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

