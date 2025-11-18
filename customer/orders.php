<?php
$pageTitle = 'My Orders';
require_once '../includes/header.php';
requireRole('customer');

// Get user's orders
$orders = Database::getInstance()->fetchAll("
    SELECT * FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
", [$_SESSION['user_id']]);
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">My Orders</h2>
</div>

<?php if (empty($orders)): ?>
    <div class="bg-white border-2 border-gray-300 p-6 text-center">
        <p class="text-gray-600 mb-4">You haven't placed any orders yet</p>
        <a href="medicines.php" class="inline-block px-6 py-3 bg-blue-600 text-white font-bold">
            START SHOPPING
        </a>
    </div>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
    <div class="bg-white border-2 border-gray-300 p-6 mb-4">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-xl font-bold">Order #<?php echo $order['id']; ?></h3>
                <div class="text-sm text-gray-600">
                    Placed on <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                </div>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-green-600"><?php echo formatPrice($order['total_amount']); ?></div>
                <div class="text-sm">
                    <span class="px-2 py-1 bg-<?php 
                        echo $order['status'] === 'completed' ? 'green' : 
                            ($order['status'] === 'cancelled' ? 'red' : 'yellow'); 
                    ?>-100 border border-<?php 
                        echo $order['status'] === 'completed' ? 'green' : 
                            ($order['status'] === 'cancelled' ? 'red' : 'yellow'); 
                    ?>-400">
                        <?php echo strtoupper($order['status']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <div class="text-sm font-bold mb-1">Delivery:</div>
                <div class="text-sm"><?php echo ucfirst($order['delivery_type']); ?> Delivery</div>
                <?php if ($order['delivery_type'] === 'home'): ?>
                <div class="text-sm text-gray-600"><?php echo clean($order['delivery_address']); ?></div>
                <?php endif; ?>
                <div class="text-sm text-gray-600">Phone: <?php echo clean($order['delivery_phone']); ?></div>
            </div>
            
            <?php if ($order['points_used'] > 0): ?>
            <div>
                <div class="text-sm font-bold mb-1">Points Used:</div>
                <div class="text-sm text-green-600"><?php echo $order['points_used']; ?> points (-<?php echo formatPrice($order['points_used']); ?>)</div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Parcels -->
        <?php
        $parcels = Database::getInstance()->fetchAll("
            SELECT p.*, s.name as shop_name
            FROM parcels p
            JOIN shops s ON p.shop_id = s.id
            WHERE p.order_id = ?
        ", [$order['id']]);
        ?>
        
        <div class="border-t pt-4">
            <h4 class="font-bold mb-3">Parcels (<?php echo count($parcels); ?>):</h4>
            
            <?php foreach ($parcels as $parcel): ?>
            <div class="border-2 border-gray-300 p-4 mb-3">
                <div class="flex justify-between mb-2">
                    <div>
                        <div class="font-bold">📦 Parcel #<?php echo $parcel['id']; ?></div>
                        <div class="text-sm text-gray-600">From: <?php echo clean($parcel['shop_name']); ?></div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold"><?php echo formatPrice($parcel['total_amount']); ?></div>
                        <div class="text-sm">
                            <span class="px-2 py-1 bg-gray-100 border">
                                <?php echo strtoupper(str_replace('_', ' ', $parcel['status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Parcel Items -->
                <?php
                $items = Database::getInstance()->fetchAll("
                    SELECT oi.*, m.name as medicine_name, m.generic_name
                    FROM order_items oi
                    JOIN medicines m ON oi.medicine_id = m.id
                    WHERE oi.parcel_id = ?
                ", [$parcel['id']]);
                ?>
                
                <table class="w-full text-sm border mt-2">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 text-left border">Medicine</th>
                            <th class="p-2 text-center border">Qty</th>
                            <th class="p-2 text-right border">Price</th>
                            <th class="p-2 text-right border">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="p-2 border">
                                <div class="font-semibold"><?php echo clean($item['medicine_name']); ?></div>
                                <div class="text-xs text-gray-600"><?php echo clean($item['generic_name']); ?></div>
                            </td>
                            <td class="p-2 border text-center"><?php echo $item['quantity']; ?></td>
                            <td class="p-2 border text-right"><?php echo formatPrice($item['price']); ?></td>
                            <td class="p-2 border text-right font-bold">
                                <?php echo formatPrice($item['quantity'] * $item['price']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Tracking -->
                <?php
                $tracking = Database::getInstance()->fetchAll("
                    SELECT * FROM parcel_status_log
                    WHERE parcel_id = ?
                    ORDER BY created_at ASC
                ", [$parcel['id']]);
                ?>
                
                <div class="mt-3 bg-gray-50 p-3 border">
                    <div class="font-bold mb-2">Tracking History:</div>
                    <div class="space-y-1">
                        <?php foreach ($tracking as $log): ?>
                        <div class="flex justify-between text-sm">
                            <div>
                                <span class="font-semibold">
                                    <?php echo strtoupper(str_replace('_', ' ', $log['status'])); ?>
                                </span>
                                <?php if ($log['notes']): ?>
                                    - <?php echo clean($log['notes']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-gray-600">
                                <?php echo date('M d, h:i A', strtotime($log['created_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Return Request Button -->
                <?php if ($parcel['status'] === 'delivered'): ?>
                    <?php
                    $returnRequested = Database::getInstance()->fetchOne("
                        SELECT id, status FROM order_returns WHERE parcel_id = ?
                    ", [$parcel['id']]);
                    
                    $deliveredDate = strtotime($parcel['updated_at']);
                    $daysSince = floor((time() - $deliveredDate) / 86400);
                    ?>
                    
                    <div class="mt-3 p-3 bg-gray-50 border">
                        <?php if ($returnRequested): ?>
                            <div class="text-sm">
                                Return Status: 
                                <span class="px-2 py-1 bg-yellow-100 border">
                                    <?php echo strtoupper($returnRequested['status']); ?>
                                </span>
                            </div>
                        <?php elseif ($daysSince <= 7): ?>
                            <a href="<?php echo SITE_URL; ?>/customer/request-return.php?order_id=<?php echo $order['id']; ?>&parcel_id=<?php echo $parcel['id']; ?>" 
                               class="inline-block px-4 py-2 bg-red-600 text-white font-bold text-sm">
                                REQUEST RETURN (<?php echo 7 - $daysSince; ?> days left)
                            </a>
                        <?php else: ?>
                            <div class="text-sm text-gray-500">Return period expired</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
