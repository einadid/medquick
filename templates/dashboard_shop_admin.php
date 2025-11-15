<div class="container mx-auto p-4 md:p-6">
    <h1 class="text-3xl font-bold mb-2">Shop Admin Dashboard</h1>
    <p class="text-gray-600 mb-6">Managing: <span class="font-semibold text-blue-600"><?= e($shop_name); ?></span></p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <p class="text-gray-500">Stock in Your Shop</p>
            <p class="text-3xl font-bold counter" data-target="<?= (int)($stats['total_stock'] ?? 0); ?>">0</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <p class="text-gray-500">Today's Sales</p>
            <p class="text-3xl font-bold">৳<span class="counter" data-target="<?= (int)($stats['today_sales'] ?? 0); ?>">0</span></p>
        </div>
        <a href="inventory_add.php" class="bg-green-500 text-white p-6 rounded-lg shadow-md flex flex-col items-center justify-center hover:bg-green-600 transition-colors">
            <i class="fas fa-plus-circle text-4xl mb-2"></i>
            <span class="font-semibold">Add Stock to Inventory</span>
        </a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4 text-orange-600"><i class="fas fa-exclamation-triangle mr-2"></i>Low Stock in Your Shop</h2>
        <div class="space-y-2">
            <?php if (empty($shop_low_stock)): ?>
                <p class="text-gray-500">No items are currently low on stock.</p>
            <?php else: foreach ($shop_low_stock as $item): ?>
                <div class="p-2 bg-orange-50 rounded-md text-sm flex justify-between">
                    <span><strong><?= e($item['name']); ?></strong> (Batch: <?= e($item['batch_number']); ?>)</span>
                    <span>Stock: <strong><?= e($item['quantity']); ?></strong> / Alert Level: <?= e($item['reorder_level']); ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>