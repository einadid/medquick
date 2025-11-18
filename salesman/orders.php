<?php
$pageTitle = 'Shop Orders';
require_once '../includes/header.php';
requireRole('salesman');

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
    
    Database::getInstance()->query("UPDATE parcels SET status = ? WHERE id = ?", [$newStatus, $parcelId]);
    Database::getInstance()->query("INSERT INTO parcel_status_log (parcel_id, status, notes) VALUES (?, ?, ?)", 
                                   [$parcelId, $newStatus, $notes]);
    
    logAudit($_SESSION['user_id'], 'parcel_status_updated', "Parcel #$parcelId status changed to $newStatus");
    setFlash('success', 'Parcel status updated');
    redirect('/salesman/orders.php');
}

// Get shop parcels
$parcels = Database::getInstance()->fetchAll("
    SELECT p.*, o.delivery_type, o.delivery_address, o.delivery_phone,
           u.full_name as customer_name
    FROM parcels p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.user_id = u.id
    WHERE p.shop_id = ?
    ORDER BY p.created_at DESC
", [$shopId]);
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">Shop Orders</h2>
</div>

<?php if (empty($parcels)): ?>
    <div class="bg-white border-2 border-gray-300 p-6 text-center text-gray-600">
        No orders yet
    </div>
<?php else: ?>
    <div class="bg-white border-2 border-gray-300 p-6">
        <table class="w-full border-2 border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left border">Parcel ID</th>
                    <th class="p-2 text-left border">Order ID</th>
                    <th class="p-2 text-left border">Customer</th>
                    <th class="p-2 text-left border">Amount</th>
                    <th class="p-2 text-left border">Status</th>
                    <th class="p-2 text-left border">Date</th>
                    <th class="p-2 text-center border">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parcels as $parcel): ?>
                <tr>
                    <td class="p-2 border font-bold">#<?php echo $parcel['id']; ?></td>
                    <td class="p-2 border">#<?php echo $parcel['order_id']; ?></td>
                    <td class="p-2 border"><?php echo clean($parcel['customer_name']); ?></td>
                    <td class="p-2 border"><?php echo formatPrice($parcel['total_amount']); ?></td>
                    <td class="p-2 border">
                        <span class="px-2 py-1 text-xs bg-gray-100 border">
                            <?php echo strtoupper(str_replace('_', ' ', $parcel['status'])); ?>
                        </span>
                    </td>
                    <td class="p-2 border text-sm">
                        <?php echo date('M d, Y', strtotime($parcel['created_at'])); ?>
                    </td>
                    <td class="p-2 border text-center">
                        <button onclick="showUpdateModal(<?php echo htmlspecialchars(json_encode($parcel)); ?>)" 
                                class="px-3 py-1 bg-blue-600 text-white text-sm">
                            UPDATE
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Update Status Modal -->
<div id="updateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border-4 border-gray-300 p-6 max-w-md w-full m-4">
        <h3 class="text-xl font-bold mb-4">Update Parcel Status</h3>
        
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="parcel_id" id="modalParcelId">
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">New Status</label>
                <select name="status" id="modalStatus" required class="w-full p-2 border-2 border-gray-400">
                    <option value="confirmed">Confirmed</option>
                    <option value="packed">Packed</option>
                    <option value="at_hub">At Hub</option>
                    <option value="out_for_delivery">Out for Delivery</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Notes (Optional)</label>
                <textarea name="notes" rows="3" class="w-full p-2 border-2 border-gray-400"></textarea>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="flex-1 p-2 bg-green-600 text-white font-bold">UPDATE</button>
                <button type="button" onclick="closeModal()" class="flex-1 p-2 bg-gray-400 text-white font-bold">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<script>
function showUpdateModal(parcel) {
    document.getElementById('modalParcelId').value = parcel.id;
    document.getElementById('modalStatus').value = parcel.status;
    document.getElementById('updateModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('updateModal').classList.add('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?>