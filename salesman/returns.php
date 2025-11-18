<?php
$pageTitle = 'Returns Management';
require_once '../includes/header.php';
requireRole('salesman');

$user = getCurrentUser();
$shopId = $user['shop_id'];

if (!$shopId) {
    die('Error: No shop assigned');
}

// Handle return actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    if (isset($_POST['update_return'])) {
        $returnId = $_POST['return_id'];
        $status = $_POST['status'];
        $notes = $_POST['notes'];
        
        Database::getInstance()->query("
            UPDATE order_returns 
            SET status = ?, notes = ?, handled_by = ?, updated_at = NOW()
            WHERE id = ? AND shop_id = ?
        ", [$status, $notes, $_SESSION['user_id'], $returnId, $shopId]);
        
        // If approved, restore stock
        if ($status === 'approved') {
            $returnItems = Database::getInstance()->fetchAll("
                SELECT ri.*, oi.shop_id
                FROM return_items ri
                JOIN order_items oi ON ri.order_item_id = oi.id
                WHERE ri.return_id = ?
            ", [$returnId]);
            
            foreach ($returnItems as $item) {
                // Find the shop_medicine entry and restore stock
                Database::getInstance()->query("
                    UPDATE shop_medicines 
                    SET stock = stock + ?
                    WHERE shop_id = ? AND medicine_id = ?
                    LIMIT 1
                ", [$item['quantity'], $item['shop_id'], $item['medicine_id']]);
            }
        }
        
        logAudit($_SESSION['user_id'], 'return_updated', "Return #$returnId status: $status");
        setFlash('success', 'Return request updated');
        redirect('/salesman/returns.php');
    }
}

// Get all returns for this shop
$returns = Database::getInstance()->fetchAll("
    SELECT 
        r.*,
        u.full_name as customer_name,
        u.phone as customer_phone,
        o.id as order_number,
        p.id as parcel_number
    FROM order_returns r
    JOIN users u ON r.user_id = u.id
    JOIN orders o ON r.order_id = o.id
    JOIN parcels p ON r.parcel_id = p.id
    WHERE r.shop_id = ?
    ORDER BY r.created_at DESC
", [$shopId]);
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold mb-4">Returns Management</h2>
    <p class="text-gray-600">Manage customer return requests</p>
</div>

<!-- Return Policy Info -->
<div class="bg-blue-50 border-2 border-blue-300 p-4 mb-4">
    <h3 class="font-bold mb-2">📋 Return Policy</h3>
    <ul class="text-sm space-y-1">
        <li>• Returns accepted within 7 days of delivery</li>
        <li>• Medicine must be unopened and in original packaging</li>
        <li>• Prescription medicines require doctor's approval for return</li>
        <li>• Refund processed within 3-5 business days after approval</li>
    </ul>
</div>

<!-- Returns List -->
<?php if (empty($returns)): ?>
    <div class="bg-white border-2 border-gray-300 p-6 text-center text-gray-600">
        No return requests yet
    </div>
<?php else: ?>
    <?php foreach ($returns as $return): ?>
    <div class="bg-white border-2 border-gray-300 p-6 mb-4">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-xl font-bold">Return Request #<?php echo $return['id']; ?></h3>
                <div class="text-sm text-gray-600">
                    Order #<?php echo $return['order_number']; ?> | Parcel #<?php echo $return['parcel_number']; ?>
                </div>
                <div class="text-sm text-gray-600">
                    Date: <?php echo date('M d, Y h:i A', strtotime($return['created_at'])); ?>
                </div>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-red-600"><?php echo formatPrice($return['return_amount']); ?></div>
                <span class="px-2 py-1 text-xs <?php 
                    echo $return['status'] === 'approved' ? 'bg-green-100 border-green-400' : 
                        ($return['status'] === 'rejected' ? 'bg-red-100 border-red-400' : 
                        ($return['status'] === 'completed' ? 'bg-blue-100 border-blue-400' : 'bg-yellow-100 border-yellow-400')); 
                ?>">
                    <?php echo strtoupper($return['status']); ?>
                </span>
            </div>
        </div>
        
        <!-- Customer Info -->
        <div class="mb-4 p-3 bg-gray-50 border">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-600">Customer</div>
                    <div class="font-bold"><?php echo clean($return['customer_name']); ?></div>
                    <div class="text-sm"><?php echo clean($return['customer_phone']); ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Return Reason</div>
                    <div><?php echo clean($return['reason']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Return Items -->
        <?php
        $returnItems = Database::getInstance()->fetchAll("
            SELECT ri.*, m.name as medicine_name, m.generic_name
            FROM return_items ri
            JOIN medicines m ON ri.medicine_id = m.id
            WHERE ri.return_id = ?
        ", [$return['id']]);
        ?>
        
        <?php if (!empty($returnItems)): ?>
        <div class="mb-4">
            <h4 class="font-bold mb-2">Items to Return:</h4>
            <table class="w-full border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-left border">Medicine</th>
                        <th class="p-2 text-center border">Quantity</th>
                        <th class="p-2 text-right border">Price</th>
                        <th class="p-2 text-right border">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($returnItems as $item): ?>
                    <tr>
                        <td class="p-2 border">
                            <div class="font-semibold"><?php echo clean($item['medicine_name']); ?></div>
                            <div class="text-sm text-gray-600"><?php echo clean($item['generic_name']); ?></div>
                            <?php if ($item['reason']): ?>
                            <div class="text-xs text-red-600">Reason: <?php echo clean($item['reason']); ?></div>
                            <?php endif; ?>
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
        </div>
        <?php endif; ?>
        
        <!-- Notes -->
        <?php if ($return['notes']): ?>
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-300">
            <strong>Notes:</strong> <?php echo clean($return['notes']); ?>
        </div>
        <?php endif; ?>
        
        <!-- Update Form (only if pending) -->
        <?php if ($return['status'] === 'pending'): ?>
        <form method="POST" class="border-t pt-4">
            <?php echo csrfField(); ?>
            <input type="hidden" name="update_return" value="1">
            <input type="hidden" name="return_id" value="<?php echo $return['id']; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block mb-2 font-bold">Decision *</label>
                    <select name="status" required class="w-full p-2 border-2 border-gray-400">
                        <option value="pending">Pending</option>
                        <option value="approved">Approve Return</option>
                        <option value="rejected">Reject Return</option>
                        <option value="completed">Mark as Completed</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 font-bold">Notes (Optional)</label>
                    <input type="text" name="notes" placeholder="Add notes..." class="w-full p-2 border-2 border-gray-400">
                </div>
                <div>
                    <label class="block mb-2">&nbsp;</label>
                    <button type="submit" class="w-full p-2 bg-blue-600 text-white font-bold">UPDATE RETURN</button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>