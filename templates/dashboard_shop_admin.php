<?php
// FILE: templates/dashboard_shop_admin.php (Final Professional Version with Toggleable Layout)
// PURPOSE: A comprehensive and actionable dashboard for the Shop Admin.
?>
<!-- This content is rendered inside the main layout provided by header.php -->
<div class="fade-in p-4 sm:p-6 space-y-8">
    
    <!-- Header Section -->
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Shop Admin Dashboard</h1>
        <p class="text-gray-600">Managing Shop: <span class="font-semibold text-teal-600"><?= e($shop_name ?? 'Error: Shop Not Found'); ?></span></p>
    </div>

    <!-- Session Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
            <p><?= e($_SESSION['success_message']); ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
            <p><?= e($_SESSION['error_message']); ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- KPI Stats Cards & Main Action -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-lg border flex items-center gap-5">
            <div class="bg-blue-100 text-blue-600 w-12 h-12 rounded-lg flex items-center justify-center"><i class="fas fa-dollar-sign text-xl"></i></div>
            <div><p class="text-sm font-medium text-gray-500">Today's Sales</p><p class="text-2xl font-bold text-slate-800 mt-1">৳<span class="counter" data-target="<?= (int)($stats['today_sales'] ?? 0); ?>">0</span></p></div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border flex items-center gap-5">
            <div class="bg-green-100 text-green-600 w-12 h-12 rounded-lg flex items-center justify-center"><i class="fas fa-boxes-stacked text-xl"></i></div>
            <div><p class="text-sm font-medium text-gray-500">Stock (Units)</p><p class="text-2xl font-bold text-slate-800 mt-1 counter" data-target="<?= (int)($stats['total_stock'] ?? 0); ?>">0</p></div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border flex items-center gap-5">
             <div class="bg-yellow-100 text-yellow-600 w-12 h-12 rounded-lg flex items-center justify-center"><i class="fas fa-hourglass-half text-xl"></i></div>
            <div><p class="text-sm font-medium text-gray-500">Pending Orders</p><p class="text-2xl font-bold text-slate-800 mt-1"><?= e($order_counts['Pending'] ?? 0) ?></p></div>
        </div>
        <a href="inventory_add.php" class="bg-teal-500 text-white p-6 rounded-xl shadow-lg flex items-center justify-center gap-4 hover:bg-teal-600 transform hover:-translate-y-1 transition-all">
            <i class="fas fa-plus-circle text-3xl"></i>
            <div class="text-left"><p class="font-bold text-lg">Add New Stock</p><p class="text-sm text-teal-100">to your inventory</p></div>
        </a>
    </div>

    <!-- Main Dashboard Grid: Chart, Orders, etc. -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Sales Chart -->
        <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-lg shadow-md border">
            <h2 class="text-2xl font-bold text-slate-800 mb-6">Sales Trend (Last 7 Days)</h2>
            <div class="h-80">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        
        <!-- Right Column: Recent Pending Orders -->
        <div class="lg:col-span-1 bg-white p-6 sm:p-8 rounded-lg shadow-md border">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-slate-800">Pending Orders</h2>
                <a href="orders.php" class="text-sm font-medium text-teal-600 hover:underline">View All</a>
            </div>
            <div class="space-y-4">
                <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-10"><i class="fas fa-check-circle text-4xl text-green-400"></i><p class="mt-4 text-gray-500">No pending orders right now.</p></div>
                <?php else: foreach ($recent_orders as $order): ?>
                    <a href="order_details.php?id=<?= e($order['id']) ?>" class="block p-3 rounded-lg hover:bg-slate-50 border">
                        <div class="flex justify-between items-center">
                            <div><p class="font-bold text-slate-800">Order #<?= e($order['id']) ?></p><p class="text-sm text-gray-500"><?= e($order['customer_name']) ?></p></div>
                            <div class="text-right"><p class="font-semibold text-teal-600">৳<?= e(number_format($order['total_amount'], 2)) ?></p></div>
                        </div>
                    </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>