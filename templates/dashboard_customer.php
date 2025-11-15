<?php
// FILE: templates/dashboard_customer.php (Final Version with 'Browse' Button)
?>
<div class="fade-in bg-slate-50 py-12">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Sidebar Navigation -->
            <?php include __DIR__ . '/_customer_sidebar.php'; ?>

            <!-- Main Content -->
            <div class="w-full lg:w-3/4">
                <!-- Welcome Banner -->
                <div class="bg-gradient-to-r from-teal-500 to-cyan-600 text-white p-8 rounded-xl shadow-lg mb-8">
                    <h1 class="text-3xl font-bold">Welcome back, <?= e(explode(' ', $_SESSION['user_name'])[0]); ?>!</h1>
                    <p class="mt-2 text-teal-100">Here's a summary of your health journey with QuickMed.</p>
                </div>

                <!-- Quick Stats & Main Action Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Stats Card 1 -->
                    <div class="bg-white p-6 rounded-lg shadow-md border flex items-center gap-4">
                        <i class="fas fa-receipt text-3xl text-purple-500"></i>
                        <div>
                            <p class="text-sm text-gray-500">Total Orders Placed</p>
                            <p class="text-2xl font-bold text-slate-800"><?= e($stats['total_orders'] ?? 0) ?></p>
                        </div>
                    </div>
                    <!-- Stats Card 2 -->
                    <div class="bg-white p-6 rounded-lg shadow-md border flex items-center gap-4">
                        <i class="fas fa-wallet text-3xl text-green-500"></i>
                        <div>
                            <p class="text-sm text-gray-500">Health Wallet (Points)</p>
                            <p class="text-2xl font-bold text-slate-800">1,250</p> <!-- Dummy Data -->
                        </div>
                    </div>
                    
                    <!-- **NEW & IMPROVED: Main Call to Action Button** -->
                    <a href="catalog.php" class="bg-amber-400 text-amber-900 p-6 rounded-lg shadow-md flex flex-col items-center justify-center hover:bg-amber-500 transition-colors transform hover:-translate-y-1">
                        <i class="fas fa-pills text-4xl mb-2"></i>
                        <span class="font-semibold text-lg">Browse & Buy Medicines</span>
                    </a>
                </div>
                
                <!-- Latest Order Status -->
                <?php if ($latest_order): ?>
                <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md border mb-8">
                    <h2 class="text-2xl font-bold text-slate-800 mb-6">Your Latest Order (#<?= e($latest_order['id']) ?>)</h2>
                    <?php 
                        $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered'];
                        $current_status_index = array_search($latest_order['order_status'], $statuses);
                        if($current_status_index === false && $latest_order['order_status'] === 'Cancelled') {
                            // Special case for Cancelled
                        } elseif ($current_status_index === false) {
                            $current_status_index = -1;
                        }
                    ?>
                    <?php if ($latest_order['order_status'] === 'Cancelled'): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-times-circle text-5xl text-red-500 mb-4"></i>
                            <p class="font-bold text-red-600">This order has been cancelled.</p>
                        </div>
                    <?php else: ?>
                        <div class="flex justify-between items-center">
                            <?php foreach($statuses as $index => $status): ?>
                                <div class="flex-1 text-center"><div class="relative mb-2"><div class="absolute w-full top-1/2 -mt-px h-0.5 <?= $index < $current_status_index ? 'bg-teal-500' : 'bg-gray-200' ?>"></div></div><div class="relative w-10 h-10 mx-auto rounded-full flex items-center justify-center <?= $index <= $current_status_index ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-500' ?>"><i class="fas <?= ['Pending' => 'fa-hourglass-start', 'Processing' => 'fa-cogs', 'Shipped' => 'fa-truck', 'Delivered' => 'fa-check-circle'][$status] ?>"></i></div><p class="mt-2 text-xs font-semibold <?= $index <= $current_status_index ? 'text-teal-600' : 'text-gray-500' ?>"><?= $status ?></p></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Quick Re-order Section -->
                <?php if (!empty($frequent_items)): ?>
                <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md border">
                    <h2 class="text-2xl font-bold text-slate-800 mb-6">Quick Re-order</h2>
                    <p class="text-gray-500 -mt-4 mb-6">Your most frequently purchased items.</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <?php foreach($frequent_items as $item): ?>
                        <div class="border rounded-lg p-4 text-center group hover:shadow-lg transition-shadow"><img src="<?= e($item['image_path'] ?? 'assets/images/default_med.png') ?>" alt="<?= e($item['name']) ?>" class="w-full h-24 object-contain mb-4"><h4 class="text-sm font-semibold truncate"><?= e($item['name']) ?></h4><p class="text-md font-bold text-teal-600 mt-1">৳<?= e(number_format($item['price'], 2)) ?></p><button class="add-to-cart-btn mt-3 w-full bg-slate-100 text-slate-700 hover:bg-teal-600 hover:text-white text-xs font-bold px-3 py-2 rounded-full transition-colors" data-id="<?= e($item['id']); ?>" data-name="<?= e($item['name']); ?>" data-price="<?= e($item['price']); ?>">Add to Cart</button></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>