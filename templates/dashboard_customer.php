<?php
// FILE: templates/dashboard_customer.php (Final Live & Dynamic Version)

// Determine time-based greeting for a personal touch
$hour = date('G');
if ($hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour < 17) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}
?>

<div class="fade-in bg-slate-50 py-12">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Sidebar Navigation -->
            <?php include __DIR__ . '/_customer_sidebar.php'; ?>

            <!-- Main Content -->
            <div class="w-full lg:w-3/4 space-y-8">
                
                <!-- 1. Personalized Welcome Banner -->
                <div class="bg-gradient-to-r from-teal-500 to-cyan-600 text-white p-8 rounded-xl shadow-lg">
                    <h1 class="text-3xl font-bold"><?= e($greeting) ?>, <?= e(explode(' ', $_SESSION['user_name'])[0]); ?>!</h1>
                    <p class="mt-2 text-teal-100">Welcome to your personal health hub on QuickMed.</p>
                </div>

                <!-- 2. Main Stats Grid with Live Data -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow-md border flex items-center gap-4">
                        <i class="fas fa-receipt text-4xl text-purple-500"></i>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Orders Placed</p>
                            <p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_orders'] ?? 0) ?>">0</p>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md border flex items-center gap-4">
                        <i class="fas fa-wallet text-4xl text-green-500"></i>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Health Wallet Balance</p>
                            <p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['health_wallet_points'] ?? 0) ?>">0<span class="text-xl font-medium ml-1">Points</span></p>
                        </div>
                    </div>
                </div>
                
                <!-- 3. Latest Order Status Tracker -->
                <?php if ($latest_order): ?>
                <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md border">
                    <a href="order_details.php?id=<?= e($latest_order['id']) ?>" class="block group">
                        <div class="flex justify-between items-center mb-6">
                             <h2 class="text-2xl font-bold text-slate-800 group-hover:text-teal-600 transition-colors">Latest Order Status (#<?= e($latest_order['id']) ?>)</h2>
                             <i class="fas fa-chevron-right text-gray-400 group-hover:text-teal-600"></i>
                        </div>
                        <?php 
                            $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered'];
                            $current_status_index = array_search($latest_order['order_status'], $statuses);
                            if($current_status_index === false && $latest_order['order_status'] === 'Cancelled') {
                                // Special case for Cancelled status
                            } elseif ($current_status_index === false) {
                                $current_status_index = -1; // If status is not in the array
                            }
                        ?>
                        <?php if ($latest_order['order_status'] === 'Cancelled'): ?>
                            <div class="text-center py-4 flex items-center justify-center gap-4"><i class="fas fa-times-circle text-5xl text-red-500"></i><div><p class="text-xl font-bold text-red-600">Order Cancelled</p><p class="text-sm text-gray-500">This order was cancelled.</p></div></div>
                        <?php else: ?>
                            <div class="flex justify-between items-center pt-2">
                                <?php foreach($statuses as $index => $status): ?>
                                    <div class="flex-1 text-center"><div class="relative mb-2"><div class="absolute w-full top-1/2 -mt-px h-1 <?= $index < $current_status_index ? 'bg-teal-500' : 'bg-gray-200' ?>"></div></div><div class="relative w-12 h-12 mx-auto rounded-full flex items-center justify-center text-xl <?= $index <= $current_status_index ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-400' ?> transition-all duration-300"><i class="fas <?= ['Pending' => 'fa-hourglass-start', 'Processing' => 'fa-cogs', 'Shipped' => 'fa-truck', 'Delivered' => 'fa-check-circle'][$status] ?>"></i></div><p class="mt-2 text-xs font-semibold <?= $index <= $current_status_index ? 'text-teal-600' : 'text-gray-500' ?>"><?= $status ?></p></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
                <?php else: ?>
                <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md border text-center">
                    <p class="text-gray-500">You haven't placed any orders yet.</p>
                    <a href="catalog.php" class="mt-4 btn-primary">Start Your First Order</a>
                </div>
                <?php endif; ?>

                <!-- 4. Quick Re-order Section -->
                <?php if (!empty($frequent_items)): ?>
                <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md border">
                    <h2 class="text-2xl font-bold text-slate-800 mb-6">Quick Re-order</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <?php foreach($frequent_items as $item): ?>
                        <div class="border rounded-lg p-4 text-center group hover:shadow-lg transition-shadow flex flex-col">
                            <a href="medicine_details.php?id=<?= e($item['id']) ?>" class="flex-grow"><img src="<?= e($item['image_path'] ?? 'assets/images/default_med.png') ?>" alt="<?= e($item['name']) ?>" class="w-full h-24 object-contain mb-4"></a>
                            <h4 class="text-sm font-semibold truncate" title="<?= e($item['name']) ?>"><?= e($item['name']) ?></h4>
                            <?php if($item['total_stock'] > 0): ?>
                                <p class="text-md font-bold text-teal-600 mt-1">৳<?= e(number_format($item['price'], 2)) ?></p>
                                <button class="add-to-cart-btn mt-3 w-full bg-slate-100 text-slate-700 hover:bg-teal-600 hover:text-white text-xs font-bold px-3 py-2 rounded-full transition-colors" data-id="<?= e($item['id']); ?>" data-name="<?= e($item['name']); ?>" data-price="<?= e($item['price']); ?>">Add to Cart</button>
                            <?php else: ?>
                                <p class="text-sm font-semibold text-red-500 mt-2">Out of Stock</p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>