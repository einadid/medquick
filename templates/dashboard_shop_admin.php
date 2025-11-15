<?php
// FILE: templates/dashboard_shop_admin.php (Final Live & Dynamic Version)
?>
<div class="fade-in p-4 sm:p-6 space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Dashboard</h1>
        <p class="text-gray-600">Overview of <span class="font-semibold text-teal-600"><?= e($shop_name ?? 'Your Shop'); ?></span></p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-md border"><p class="text-sm font-medium text-gray-500">Today's Sales</p><p class="text-3xl font-bold mt-1">৳<span class="counter" data-target="<?= (int)($stats['today_sales'] ?? 0); ?>">0</span></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md border"><p class="text-sm font-medium text-gray-500">Total Stock (Units)</p><p class="text-3xl font-bold mt-1 counter" data-target="<?= (int)($stats['total_stock'] ?? 0); ?>">0</p></div>
        <div class="bg-white p-6 rounded-lg shadow-md border"><p class="text-sm font-medium text-gray-500">Pending Orders</p><p class="text-3xl font-bold text-amber-500 mt-1"><?= e($order_counts['Pending'] ?? 0) ?></p></div>
        <a href="inventory_add.php" class="bg-teal-500 text-white p-6 rounded-lg shadow-md flex items-center justify-center gap-3 hover:bg-teal-600 transform hover:-translate-y-1 transition-all"><i class="fas fa-plus-circle text-2xl"></i><span class="font-semibold text-lg">Add Stock</span></a>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Order Management & Top Selling -->
        <div class="lg:col-span-2 space-y-8">
            <!-- **NEW: Order Management Panel** -->
            <div class="bg-white p-6 rounded-lg shadow-md border">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-slate-800">Order Management</h2>
                    <a href="orders.php" class="text-sm font-medium text-teal-600 hover:underline">View All Orders</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                    <a href="orders.php?status=Pending" class="block p-4 bg-yellow-50 border border-yellow-200 rounded-lg hover:shadow-lg">
                        <p class="text-3xl font-bold text-yellow-600"><?= e($order_counts['Pending'] ?? 0) ?></p>
                        <p class="text-sm font-medium text-yellow-800">Pending</p>
                    </a>
                    <a href="orders.php?status=Processing" class="block p-4 bg-blue-50 border border-blue-200 rounded-lg hover:shadow-lg">
                        <p class="text-3xl font-bold text-blue-600"><?= e($order_counts['Processing'] ?? 0) ?></p>
                        <p class="text-sm font-medium text-blue-800">Processing</p>
                    </a>
                    <a href="orders.php?status=Shipped" class="block p-4 bg-indigo-50 border border-indigo-200 rounded-lg hover:shadow-lg">
                        <p class="text-3xl font-bold text-indigo-600"><?= e($order_counts['Shipped'] ?? 0) ?></p>
                        <p class="text-sm font-medium text-indigo-800">Shipped</p>
                    </a>
                </div>
            </div>

            <!-- **NEW: Top Selling Products Panel** -->
            <div class="bg-white p-6 rounded-lg shadow-md border">
                 <h2 class="text-xl font-bold text-slate-800 mb-4">Top Selling Products (Last 30d)</h2>
                 <?php if (empty($top_selling_products)): ?>
                    <p class="text-center text-gray-500 py-8">Not enough sales data available.</p>
                 <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach($top_selling_products as $product): ?>
                        <li class="flex items-center justify-between text-sm border-b pb-2 last:border-0">
                            <span class="font-medium text-gray-800"><?= e($product['name']) ?></span>
                            <span class="font-bold bg-slate-100 px-2 py-1 rounded-md">Sold: <?= e($product['total_sold']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                 <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Inventory Health -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-md border h-full">
                <h2 class="text-xl font-bold text-slate-800 mb-6">Inventory Health</h2>
                <div class="flex justify-center items-center">
                    <div class="relative w-48 h-48">
                        <svg class="w-full h-full" viewBox="0 0 36 36">
                            <path class="text-gray-200" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke-width="3.8"></path>
                            <path class="text-teal-500" stroke-dasharray="<?= $healthy_percentage ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke-width="3.8" stroke-linecap="round"></path>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-4xl font-bold text-teal-600"><?= e($healthy_percentage) ?>%</span>
                            <span class="text-sm text-gray-500">Healthy</span>
                        </div>
                    </div>
                </div>
                <div class="mt-6 space-y-3 text-sm">
                    <div class="flex justify-between items-center"><div class="flex items-center"><span class="w-3 h-3 rounded-full bg-teal-500 mr-2"></span><span>Healthy Stock</span></div><span class="font-semibold"><?= e($healthy_stock_count) ?></span></div>
                    <div class="flex justify-between items-center"><div class="flex items-center"><span class="w-3 h-3 rounded-full bg-orange-400 mr-2"></span><span>Low Stock</span></div><span class="font-semibold"><?= e($low_stock_count) ?></span></div>
                    <div class="flex justify-between items-center"><div class="flex items-center"><span class="w-3 h-3 rounded-full bg-gray-200 mr-2"></span><span>Total Products</span></div><span class="font-semibold"><?= e($total_products) ?></span></div>
                </div>
                 <div class="mt-6 border-t pt-4">
                    <a href="manage_stock.php" class="w-full text-center block bg-slate-100 hover:bg-slate-200 font-semibold py-2 rounded-lg">Manage Full Inventory</a>
                </div>
            </div>
        </div>
    </div>
</div>