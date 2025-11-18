<?php
$pageTitle = 'Salesman Dashboard';
require_once '../includes/header.php';
requireRole('salesman');

$user = getCurrentUser();
$shopId = $user['shop_id'];

if (!$shopId) {
    die('Error: No shop assigned to this salesman account');
}

// Today's stats - FIXED QUERIES
$todaySales = Database::getInstance()->fetchOne("
    SELECT COALESCE(SUM(p.total_amount), 0) as total
    FROM parcels p
    WHERE p.shop_id = ? AND DATE(p.created_at) = CURDATE()
", [$shopId])['total'] ?? 0;

$todayOrders = Database::getInstance()->fetchOne("
    SELECT COUNT(*) as count
    FROM parcels p
    WHERE p.shop_id = ? AND DATE(p.created_at) = CURDATE()
", [$shopId])['count'] ?? 0;

$pendingOrders = Database::getInstance()->fetchOne("
    SELECT COUNT(*) as count
    FROM parcels
    WHERE shop_id = ? AND status IN ('pending', 'confirmed')
", [$shopId])['count'] ?? 0;

// This week's sales
$weekSales = Database::getInstance()->fetchOne("
    SELECT COALESCE(SUM(p.total_amount), 0) as total
    FROM parcels p
    WHERE p.shop_id = ? AND YEARWEEK(p.created_at, 1) = YEARWEEK(CURDATE(), 1)
", [$shopId])['total'] ?? 0;

// This month's sales
$monthSales = Database::getInstance()->fetchOne("
    SELECT COALESCE(SUM(p.total_amount), 0) as total
    FROM parcels p
    WHERE p.shop_id = ? AND YEAR(p.created_at) = YEAR(CURDATE()) AND MONTH(p.created_at) = MONTH(CURDATE())
", [$shopId])['total'] ?? 0;

// Get shop info
$shop = Database::getInstance()->fetchOne("SELECT * FROM shops WHERE id = ?", [$shopId]);
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">Salesman Dashboard</h2>
    <div class="text-gray-600">Shop: <?php echo clean($shop['name'] ?? 'Unknown'); ?> - <?php echo clean($shop['city'] ?? ''); ?></div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="text-gray-600">Today's Sales</div>
        <div class="text-3xl font-bold text-green-600"><?php echo formatPrice($todaySales); ?></div>
        <div class="text-sm text-gray-500 mt-1"><?php echo $todayOrders; ?> orders</div>
    </div>
    
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="text-gray-600">This Week's Sales</div>
        <div class="text-3xl font-bold text-blue-600"><?php echo formatPrice($weekSales); ?></div>
    </div>
    
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="text-gray-600">This Month's Sales</div>
        <div class="text-3xl font-bold text-purple-600"><?php echo formatPrice($monthSales); ?></div>
    </div>
    
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="text-gray-600">Pending Orders</div>
        <div class="text-3xl font-bold text-orange-600"><?php echo $pendingOrders; ?></div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <a href="pos.php" class="bg-white border-2 border-gray-300 p-8 text-center hover:bg-gray-50">
        <div class="text-4xl mb-2">🛒</div>
        <div class="text-xl font-bold">POS System</div>
        <div class="text-gray-600">Walk-in sales</div>
    </a>
    <a href="pos-return.php" class="bg-white border-2 border-gray-300 p-8 text-center hover:bg-gray-50">
        <div class="text-gray-600">Process refunds</div>
        <div class="text-4xl mb-2">↩️</div>
        <div class="text-xl font-bold">POS Returns</div>
    </a>
    
    <a href="orders.php" class="bg-white border-2 border-gray-300 p-8 text-center hover:bg-gray-50">
        <div class="text-4xl mb-2">📦</div>
        <div class="text-xl font-bold">View Orders</div>
        <div class="text-gray-600">Online orders</div>
    </a>
    
    <a href="sales-report.php" class="bg-white border-2 border-gray-300 p-8 text-center hover:bg-gray-50">
        <div class="text-4xl mb-2">📊</div>
        <div class="text-xl font-bold">Sales Report</div>
        <div class="text-gray-600">View detailed reports</div>
    </a>
    
    <a href="returns.php" class="bg-white border-2 border-gray-300 p-8 text-center hover:bg-gray-50">
        <div class="text-4xl mb-2">↩️</div>
        <div class="text-xl font-bold">Returns</div>
        <div class="text-gray-600">Manage returns</div>
    </a>

    <!-- Add to the grid of quick action buttons -->
    <a href="register-customer.php" class="bg-white border-2 border-gray-300 p-8 text-center hover:bg-gray-50">
        <div class="text-4xl mb-2">👥</div>
        <div class="text-xl font-bold">Register Customer</div>
        <div class="text-gray-600">Create membership</div>
    </a>
</div>

<!-- Recent Activity -->
<div class="bg-white border-2 border-gray-300 p-6">
    <h3 class="text-xl font-bold mb-4">Recent Orders</h3>
    
    <?php
    $recentOrders = Database::getInstance()->fetchAll("
        SELECT p.*, o.delivery_type, u.full_name as customer_name
        FROM parcels p
        JOIN orders o ON p.order_id = o.id
        JOIN users u ON o.user_id = u.id
        WHERE p.shop_id = ?
        ORDER BY p.created_at DESC
        LIMIT 10
    ", [$shopId]);
    ?>
    
    <?php if (empty($recentOrders)): ?>
        <div class="text-center text-gray-600 py-4">No orders yet</div>
    <?php else: ?>
        <table class="w-full border-2 border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left border">Parcel ID</th>
                    <th class="p-2 text-left border">Customer</th>
                    <th class="p-2 text-left border">Amount</th>
                    <th class="p-2 text-left border">Status</th>
                    <th class="p-2 text-left border">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td class="p-2 border font-bold">#<?php echo $order['id']; ?></td>
                    <td class="p-2 border"><?php echo clean($order['customer_name']); ?></td>
                    <td class="p-2 border"><?php echo formatPrice($order['total_amount']); ?></td>
                    <td class="p-2 border">
                        <span class="px-2 py-1 text-xs bg-gray-100 border">
                            <?php echo strtoupper(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                    </td>
                    <td class="p-2 border text-sm">
                        <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>