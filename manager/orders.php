<?php
$pageTitle = 'Manage Orders';
require_once '../includes/header.php';
requireRole('shop_manager');

$user = getCurrentUser();
$shopId = $user['shop_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $parcelId = $_POST['parcel_id'];
    $newStatus = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    Database::getInstance()->query("UPDATE parcels SET status = ? WHERE id = ? AND shop_id = ?", 
                                   [$newStatus, $parcelId, $shopId]);
    Database::getInstance()->query("INSERT INTO parcel_status_log (parcel_id, status, notes) VALUES (?, ?, ?)", 
                                   [$parcelId, $newStatus, $notes]);
    
    logAudit($_SESSION['user_id'], 'parcel_status_updated', "Parcel #$parcelId updated to $newStatus");
    setFlash('success', 'Parcel status updated');
    redirect('/manager/orders.php');
}

// Filter
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT p.*, o.delivery_type, o.delivery_address, o.delivery_phone,
        u.full_name as customer_name, u.phone as customer_phone
        FROM parcels p
        JOIN orders o ON p.order_id = o.id
        JOIN users u ON o.user_id = u.id
        WHERE p.shop_id = ?";

$params = [$shopId];

if ($statusFilter) {
    $sql .= " AND p.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY p.created_at DESC";

$parcels = Database::getInstance()->fetchAll($sql, $params);
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold mb-4">Manage Orders</h2>
    
    <!-- Filter -->
    <form method="GET" class="flex gap-2">
        <select name="status" class="p-2 border-2 border-gray-400">
            <option value="">All Status</option>
            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
            <option value="packed" <?php echo $statusFilter === 'packed' ? 'selected' : ''; ?>>Packed</option>
            <option value="at_hub" <?php echo $statusFilter === 'at_hub' ? 'selected' : ''; ?>>At Hub</option>
            <option value="out_for_delivery" <?php echo $statusFilter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
            <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-bold">FILTER</button>
    </form>
</div>

<?php if (empty($parcels)): ?>
    <div class="bg-white border-2 border-gray-300 p-6 text-center text-gray-600">
        No parcels found
    </div>
<?php else: ?>
    <?php foreach ($parcels as $parcel): ?>
    <div class="bg-white border-2 border-gray-300 p-6 mb-4">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-xl font-bold">Parcel #<?php echo $parcel['id']; ?></h3>
                <div class="text-sm text-gray-600">Order #<?php echo $parcel['order_id']; ?></div>
                <div class="text-sm text-gray-600">
                    Date: <?php echo date('M d, Y h:i A', strtotime($parcel['created_at'])); ?>
                </div>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-green-600"><?php echo formatPrice($parcel['total_amount']); ?></div>
                <div class="text-sm">
                    <span class="px-2 py-1 bg-gray-100 border">
                        <?php echo strtoupper(str_replace('_', ' ', $parcel['status'])); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Customer Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-4 bg-gray-50 border">
            <div>
                <div class="font-bold mb-1">Customer:</div>
                <div><?php echo clean($parcel['customer_name']); ?></div>
                <div class="text-sm text-gray-600">Phone: <?php echo clean($parcel['customer_phone']); ?></div>
            </div>
            <div>
                <div class="font-bold mb-1">Delivery:</div>
                <div><?php echo ucfirst($parcel['delivery_type']); ?> Delivery</div>
                <?php if ($parcel['delivery_type'] === 'home'): ?>
                <div class="text-sm text-gray-600"><?php echo clean($parcel['delivery_address']); ?></div>
                <?php endif; ?>
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
        
        <table class="w-full border mb-4">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left border">Medicine</th>
                    <th class="p-2 text-center border">Quantity</th>
                    <th class="p-2 text-right border">Price</th>
                    <th class="p-2 text-right border">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="p-2 border">
                        <div class="font-semibold"><?php echo clean($item['medicine_name']); ?></div>
                        <div class="text-sm text-gray-600"><?php echo clean($item['generic_name']); ?></div>
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
        
        <!-- Update Status Form -->
        <form method="POST" class="border-t pt-4">
            <?php echo csrfField(); ?>
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="parcel_id" value="<?php echo $parcel['id']; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block mb-2 font-bold">Update Status</label>
                    <select name="status" required class="w-full p-2 border-2 border-gray-400">
                        <option value="pending" <?php echo $parcel['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $parcel['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="packed" <?php echo $parcel['status'] === 'packed' ? 'selected' : ''; ?>>Packed</option>
                        <option value="at_hub" <?php echo $parcel['status'] === 'at_hub' ? 'selected' : ''; ?>>At Hub</option>
                        <option value="out_for_delivery" <?php echo $parcel['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                        <option value="delivered" <?php echo $parcel['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $parcel['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 font-bold">Notes (Optional)</label>
                    <input type="text" name="notes" placeholder="Add notes..." class="w-full p-2 border-2 border-gray-400">
                </div>
                <div>
                    <label class="block mb-2">&nbsp;</label>
                    <button type="submit" class="w-full p-2 bg-green-600 text-white font-bold">UPDATE STATUS</button>
                </div>
            </div>
        </form>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>