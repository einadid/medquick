<?php
// FILE: templates/dashboard_salesman.php (Final Professional Version)
// PURPOSE: A comprehensive and actionable dashboard for the Salesman, with improved mobile UX.
?>
<!-- This main content div is placed inside the Salesman layout by header.php -->
<div class="fade-in p-4 sm:p-6 space-y-8">

    <!-- Header Section with a dedicated mobile logout button -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Dashboard</h1>
            <p class="text-gray-600">Your daily sales and inventory hub.</p>
        </div>
        <!-- **NEW & IMPROVED: Mobile Logout Button** -->
        <div class="lg:hidden">
            <a href="logout.php" class="w-full flex items-center justify-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 font-semibold py-2 px-4 rounded-lg transition-colors border border-red-200">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left/Main Column: Actions, Stats, and Recent Sales -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- 1. Start New Sale Call-to-Action -->
            <a href="pos.php" class="block bg-gradient-to-r from-teal-500 to-cyan-600 text-white text-center p-10 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <i class="fas fa-cash-register text-5xl mb-4"></i>
                <h2 class="text-4xl font-extrabold">Start New Sale</h2>
                <p class="text-teal-100 mt-2">Go to the Point of Sale interface</p>
            </a>
            
            <!-- 2. Today's Sales Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md border">
                    <p class="text-sm font-medium text-gray-500">Your Shop's POS Sales Today</p>
                    <p class="text-3xl font-bold text-slate-800 mt-1">৳<span class="counter" data-target="<?= (int)($stats['today_sales'] ?? 0); ?>">0</span></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md border">
                    <p class="text-sm font-medium text-gray-500">POS Transactions Today</p>
                    <p class="text-3xl font-bold text-slate-800 mt-1 counter" data-target="<?= (int)($stats['sales_count'] ?? 0); ?>">0</p>
                </div>
            </div>

            <!-- 3. My Recent Sales Table -->
            <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md border">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-slate-800">Recent POS Sales</h2>
                    <a href="my_sales.php" class="text-sm font-medium text-teal-600 hover:underline">View All My Sales</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($recent_sales)): ?>
                                <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">You haven't made any sales yet today.</td></tr>
                            <?php else: foreach ($recent_sales as $sale): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">#<?= e($sale['id']) ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?= date('h:i A', strtotime($sale['created_at'])) ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold">৳<?= e(number_format($sale['total_amount'], 2)) ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="order_details.php?id=<?= e($sale['id']) ?>" class="text-teal-600 hover:text-teal-900">View/Manage</a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Inventory Alerts -->
        <div class="lg:col-span-1 space-y-8">
            <div class="bg-white p-6 rounded-lg shadow-md border">
                <h3 class="text-xl font-semibold text-orange-600 mb-4 flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i>Low Stock Alert</h3>
                <?php if (empty($low_stock_items)): ?>
                    <p class="text-sm text-gray-500">No items are low on stock. Great!</p>
                <?php else: ?>
                    <ul class="space-y-3 text-sm">
                        <?php foreach ($low_stock_items as $item): ?>
                            <li class="flex justify-between items-center border-b pb-2 last:border-b-0">
                                <span class="font-medium text-gray-800"><?= e($item['name']) ?></span>
                                <span class="font-bold text-orange-500">Qty: <?= e($item['current_stock']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md border">
                <h3 class="text-xl font-semibold text-red-600 mb-4 flex items-center gap-2"><i class="fas fa-hourglass-end"></i>Expiring Soon (30d)</h3>
                 <?php if (empty($expiring_soon_items)): ?>
                    <p class="text-sm text-gray-500">No items are expiring soon.</p>
                <?php else: ?>
                    <ul class="space-y-3 text-sm">
                        <?php foreach ($expiring_soon_items as $item): ?>
                            <li class="flex justify-between items-center border-b pb-2 last:border-b-0">
                                <div>
                                    <p class="font-medium text-gray-800"><?= e($item['name']) ?></p>
                                    <p class="text-xs text-gray-400">Batch: <?= e($item['batch_number']) ?></p>
                                </div>
                                <span class="font-bold text-red-500" title="Expires on <?= date('d M, Y', strtotime($item['expiry_date'])) ?>">
                                    <?= date('d M', strtotime($item['expiry_date'])) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>