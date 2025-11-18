<?php
$pageTitle = 'Manager Dashboard';
require_once '../includes/header.php';
requireRole('shop_manager');

$user = getCurrentUser();
$shopId = $user['shop_id'];

// Get shop info
$shop = Database::getInstance()->fetchOne("SELECT * FROM shops WHERE id = ?", [$shopId]);

// Statistics
$totalRevenue = Database::getInstance()->fetchOne("
    SELECT COALESCE(SUM(total_amount), 0) as total
    FROM parcels
    WHERE shop_id = ? AND status = 'delivered'
", [$shopId])['total'];

$pendingParcels = Database::getInstance()->fetchOne("
    SELECT COUNT(*) as count
    FROM parcels
    WHERE shop_id = ? AND status IN ('pending', 'confirmed', 'packed')
", [$shopId])['count'];

$lowStockCount = Database::getInstance()->fetchOne("
    SELECT COUNT(*) as count
    FROM shop_medicines
    WHERE shop_id = ? AND stock < 20
", [$shopId])['count'];

$expiringCount = Database::getInstance()->fetchOne("
    SELECT COUNT(*) as count
    FROM shop_medicines
    WHERE shop_id = ? AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
", [$shopId])['count'];

// Recent orders
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

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">Manager Dashboard</h2>
    <div class="text-gray-600">Shop: <?php echo clean($shop['name']); ?> - <?php echo clean($shop['city']); ?></div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="text-gray-600">Total Revenue</div>
        <div class="text-3xl font-bold text-green-600"><?php echo formatPrice($totalRevenue); ?></div>
    </div>
    
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="text-gray-600">Pending Parcels</div>
        <div class="text-3xl font-bold text-orange-600"><?php echo $pendingParcels; ?></div>
    </div>
    
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="text-gray-600">Low Stock Items</div>
        <div class="text-3xl font-bold text-red-600"><?php echo $lowStockCount; ?></div>
    </div>
    
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="text-gray-600">Expiring Soon</div>
        <div class="text-3xl font-bold text-yellow-600"><?php echo $expiringCount; ?></div>
    </div>
</div>

<!-- Recent Orders -->
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h3 class="text-xl font-bold mb-4">Recent Orders</h3>
    
    <?php if (empty($recentOrders)): ?>
        <div class="text-center text-gray-600 py-4">No orders yet</div>
    <?php else: ?>
        <table class="w-full border-2 border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left border">Parcel ID</th>
                    <th class="p-2 text-left border">Customer</th>
                    <th class="p-2 text-left border">Amount</th>
                    <th class="p-2 text-left border">Type</th>
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
                    <td class="p-2 border"><?php echo ucfirst($order['delivery_type']); ?></td>
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