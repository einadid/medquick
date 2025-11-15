<?php
// templates/dashboard_shop_admin.php

// Example data fetching (adjust according to your DB)
$total_medicines = 120; // Example value, fetch from DB
$total_stock = 350;     // Example value, fetch from DB
$total_users = 45;      // Example value, fetch from DB
$low_stock_items = 12;  // Example value, fetch from DB
?>
<div class="container mx-auto p-4 md:p-6">
    <h1 class="text-3xl font-bold mb-6">Admin Dashboard</h1>

    <!-- Visual Dashboard Banners with Animated Counters -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center hover:shadow-lg transition-shadow">
            <i class="fas fa-pills text-4xl text-blue-500 mr-4"></i>
            <div>
                <p class="text-gray-500 text-sm">Total Medicines</p>
                <p class="text-3xl font-bold counter" data-target="<?= (int)($stats['total_medicines'] ?? 0); ?>">0</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center hover:shadow-lg transition-shadow">
            <i class="fas fa-boxes-stacked text-4xl text-green-500 mr-4"></i>
            <div>
                <p class="text-gray-500 text-sm">Total Stock</p>
                <p class="text-3xl font-bold counter" data-target="<?= (int)($stats['total_stock'] ?? 0); ?>">0</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center hover:shadow-lg transition-shadow">
            <i class="fas fa-users text-4xl text-purple-500 mr-4"></i>
            <div>
                <p class="text-gray-500 text-sm">Total Users</p>
                <p class="text-3xl font-bold counter" data-target="<?= (int)($stats['total_users'] ?? 0); ?>">0</p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center hover:shadow-lg transition-shadow">
            <i class="fas fa-dollar-sign text-4xl text-yellow-500 mr-4"></i>
            <div>
                <p class="text-gray-500 text-sm">Today's Sales</p>
                <p class="text-3xl font-bold">৳<span class="counter" data-target="<?= (int)($stats['today_sales'] ?? 0); ?>">0</span></p>
            </div>
        </div>
    </div>
    
    <!-- Alerts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Expiring Soon -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4 text-red-600"><i class="fas fa-clock mr-2"></i>Expiring Soon (< 30 days)</h2>
            <div class="space-y-2">
                <?php if (empty($expiring_soon)): ?>
                    <p class="text-gray-500">No items are expiring soon. Good job!</p>
                <?php else: foreach ($expiring_soon as $item): ?>
                    <div class="p-2 bg-red-50 rounded-md text-sm flex justify-between">
                        <span><strong><?= e($item['name']); ?></strong> (<?= e($item['shop_name']); ?>) - Qty: <?= e($item['quantity']); ?></span>
                        <span class="font-mono"><?= date('d M, Y', strtotime($item['expiry_date'])); ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <!-- Low Stock -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4 text-orange-600"><i class="fas fa-exclamation-triangle mr-2"></i>Low Stock Items</h2>
             <div class="space-y-2">
                <?php if (empty($low_stock)): ?>
                    <p class="text-gray-500">No items are low on stock.</p>
                <?php else: foreach ($low_stock as $item): ?>
                    <div class="p-2 bg-orange-50 rounded-md text-sm flex justify-between">
                        <span><strong><?= e($item['name']); ?></strong> (<?= e($item['shop_name']); ?>)</span>
                        <span>Stock: <strong><?= e($item['current_stock']); ?></strong> / Alert: <?= e($item['reorder_level']); ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
    
<!-- FontAwesome CDN (if not included in header) -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
