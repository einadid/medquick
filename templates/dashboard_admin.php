<?php
// FILE: templates/dashboard_admin.php (Final Professional Version)
// This view is now rendered inside the main layout provided by header.php
?>
<div class="fade-in p-4 sm:p-6 space-y-8">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Admin Dashboard</h1>
            <p class="text-gray-600">Complete system overview and management panel.</p>
        </div>
        <!-- Quick action button for desktop -->
        <a href="users.php" class="hidden sm:inline-block btn-primary">
            <i class="fas fa-users-cog mr-2"></i> Manage Users
        </a>
    </div>

    <!-- KPI Cards with Profit -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-lg border flex items-center gap-5">
            <div class="bg-blue-100 text-blue-600 w-12 h-12 rounded-lg flex items-center justify-center"><i class="fas fa-dollar-sign text-xl"></i></div>
            <div><p class="text-sm font-medium text-gray-500">Today's Sales</p><p class="text-2xl font-bold mt-1">৳<span class="counter" data-target="<?= (int)($stats['today_sales'] ?? 0); ?>">0</span></p></div>
        </div>
        <div class="bg-green-50 border-green-200 p-6 rounded-xl shadow-lg border flex items-center gap-5">
            <div class="bg-green-100 text-green-600 w-12 h-12 rounded-lg flex items-center justify-center"><i class="fas fa-hand-holding-usd text-xl"></i></div>
            <div><p class="text-sm font-medium text-green-800">Today's Profit</p><p class="text-2xl font-bold text-green-700 mt-1">৳<span class="counter" data-target="<?= (int)($stats['today_profit'] ?? 0); ?>">0</span></p></div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border flex items-center gap-5">
            <div class="bg-yellow-100 text-yellow-600 w-12 h-12 rounded-lg flex items-center justify-center"><i class="fas fa-hourglass-half text-xl"></i></div>
            <div><p class="text-sm font-medium text-gray-500">Pending Orders</p><p class="text-2xl font-bold mt-1 counter" data-target="<?= (int)($stats['pending_orders'] ?? 0); ?>">0</p></div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border flex items-center gap-5">
            <div class="bg-purple-100 text-purple-600 w-12 h-12 rounded-lg flex items-center justify-center"><i class="fas fa-user-plus text-xl"></i></div>
            <div><p class="text-sm font-medium text-gray-500">New Users Today</p><p class="text-2xl font-bold mt-1 counter" data-target="<?= (int)($stats['new_users_today'] ?? 0); ?>">0</p></div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border flex items-center gap-5">
            <div class="bg-indigo-100 text-indigo-600 w-12 h-12 rounded-lg flex items-center justify-center"><i class="fas fa-users text-xl"></i></div>
            <div><p class="text-sm font-medium text-gray-500">Total Users</p><p class="text-2xl font-bold mt-1 counter" data-target="<?= (int)($stats['total_users'] ?? 0); ?>">0</p></div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md border">
            <h2 class="text-xl font-bold text-slate-800 mb-4">Sales Trend (Last 30 Days)</h2>
            <div class="h-80"><canvas id="salesOverTimeChart"></canvas></div>
        </div>
        <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md border">
            <h2 class="text-xl font-bold text-slate-800 mb-4">Sales by Shop</h2>
            <div class="h-80 flex items-center justify-center"><canvas id="salesByShopChart"></canvas></div>
        </div>
    </div>
    
    <!-- "At a Glance" Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white p-6 rounded-lg shadow-md border">
            <div class="flex justify-between items-center mb-4"><h2 class="text-xl font-bold text-slate-800">Recent Online Orders</h2><a href="orders.php" class="text-sm font-medium text-teal-600 hover:underline">View All</a></div>
            <div class="space-y-3">
                <?php if(empty($recent_orders)): ?><p class="text-sm text-center text-gray-500 py-5">No recent orders found.</p>
                <?php else: foreach($recent_orders as $order): ?>
                <a href="order_details.php?id=<?=e($order['id'])?>" class="flex justify-between items-center text-sm p-2 rounded-md hover:bg-slate-50">
                    <div><span class="font-semibold">#<?= e($order['id']) ?></span> <span class="text-gray-500">by <?= e($order['full_name']) ?></span></div>
                    <span class="font-bold text-teal-600">৳<?= e(number_format($order['total_amount'])) ?></span>
                </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md border">
            <div class="flex justify-between items-center mb-4"><h2 class="text-xl font-bold text-slate-800">Stock Alerts</h2><a href="#" class="text-sm font-medium text-teal-600 hover:underline">Manage Stock</a></div>
            <div class="space-y-3">
                 <?php if(empty($low_stock_items)): ?><p class="text-sm text-center text-gray-500 py-5">Inventory is healthy. No low stock items.</p>
                 <?php else: foreach($low_stock_items as $item): ?>
                 <div class="flex justify-between items-center text-sm p-2 rounded-md bg-orange-50 border-l-4 border-orange-400">
                    <span class="font-medium"><?= e($item['name']) ?></span>
                    <span class="font-bold text-orange-600">Only <?= e($item['current_stock']) ?> left</span>
                 </div>
                 <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js scripts will be included by dashboard.php -->