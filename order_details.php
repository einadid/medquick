<?php
// FILE: order_details.php (Upgraded with Address & Status Management)
require_once 'src/session.php';
require_once 'config/database.php';

if (!is_logged_in()) { redirect('login.php'); }

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) { redirect('orders.php'); }

try {
    $sql = "SELECT o.*, u.full_name as customer_name, u.email as customer_email, s.name as shop_name, s.address as shop_address FROM orders o JOIN users u ON o.customer_id = u.id JOIN shops s ON o.shop_id = s.id WHERE o.id = ?";
    $params = [$order_id];
    if (has_role(ROLE_CUSTOMER)) { $sql .= " AND o.customer_id = ?"; $params[] = $_SESSION['user_id']; }
    elseif (has_role(ROLE_SHOP_ADMIN)) { $sql .= " AND o.shop_id = ?"; $params[] = $_SESSION['shop_id']; }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $order = $stmt->fetch();

    if (!$order) { $_SESSION['error_message'] = "Order not found or access denied."; redirect('orders.php'); }

    $items_stmt = $pdo->prepare("SELECT oi.*, m.name as medicine_name FROM order_items oi JOIN medicines m ON oi.medicine_id = m.id WHERE oi.order_id = ?");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll();
} catch (PDOException $e) { $_SESSION['error_message'] = "Database error."; redirect('orders.php'); }

$pageTitle = "Order Details #" . e($order['id']);
$statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

include 'templates/header.php';
?>
<div class="fade-in bg-slate-50 py-12">
    <div class="container mx-auto px-4 sm:px-6 max-w-5xl">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Order Details</h1>
                <p class="text-gray-500">Order #<?= e($order['id']) ?> &bull; <span class="font-semibold"><?= date('d F, Y', strtotime($order['created_at'])) ?></span></p>
            </div>
            <div class="flex items-center gap-2">
                <a href="orders.php" class="text-sm font-medium text-teal-600 hover:underline">&larr; Back to All Orders</a>
                <button onclick="window.print()" class="btn-primary py-2 px-4"><i class="fas fa-print mr-2"></i> Print</button>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Invoice/Details Panel -->
            <div class="lg:col-span-2 bg-white p-8 rounded-lg shadow-md border" id="invoice">
                <div class="grid grid-cols-2 gap-8 mb-8 pb-8 border-b">
                    <div><h3 class="text-sm font-semibold text-gray-500 uppercase">BILLED TO</h3><p class="font-bold text-lg"><?= e($order['customer_name']) ?></p><p><?= e($order['customer_email']) ?></p></div>
                    <div class="text-right"><h3 class="text-sm font-semibold text-gray-500 uppercase">FULFILLED BY</h3><p class="font-bold text-lg"><?= e($order['shop_name']) ?></p><p class="text-sm"><?= e($order['shop_address']) ?></p></div>
                </div>

                <!-- **NEW: Delivery Information Section** -->
                <div class="mb-8 pb-8 border-b">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">SHIPPING & DELIVERY DETAILS</h3>
                    <p class="font-semibold text-gray-800">Contact: <span class="font-normal"><?= e($order['customer_phone']) ?></span></p>
                    <p class="font-semibold text-gray-800 mt-1">Address: <span class="font-normal"><?= nl2br(e($order['delivery_address'])) ?></span></p>
                </div>
                
                <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">ORDER ITEMS</h3>
                <table class="w-full mb-8">
                    <thead><tr class="border-b"><th class="py-2 text-left font-semibold text-gray-600">Item</th><th class="py-2 text-center font-semibold text-gray-600">Qty</th><th class="py-2 text-right font-semibold text-gray-600">Unit Price</th><th class="py-2 text-right font-semibold text-gray-600">Total</th></tr></thead>
                    <tbody>
                        <?php $subtotal = 0; foreach ($order_items as $item): $item_total = $item['price_per_unit'] * $item['quantity']; $subtotal += $item_total; ?>
                        <tr class="border-b"><td class="py-3"><?= e($item['medicine_name']) ?></td><td class="py-3 text-center"><?= e($item['quantity']) ?></td><td class="py-3 text-right">৳<?= e(number_format($item['price_per_unit'], 2)) ?></td><td class="py-3 text-right">৳<?= e(number_format($item_total, 2)) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="flex justify-end"><div class="w-full md:w-1/2 space-y-2"><div class="flex justify-between"><span class="text-gray-600">Subtotal:</span><span>৳<?= e(number_format($subtotal, 2)) ?></span></div><div class="flex justify-between"><span class="text-gray-600">Discount:</span><span>- ৳<?= e(number_format($subtotal - $order['total_amount'], 2)) ?></span></div><div class="flex justify-between font-bold text-xl border-t pt-2 mt-2"><span class="text-slate-800">Grand Total:</span><span class="text-teal-600">৳<?= e(number_format($order['total_amount'], 2)) ?></span></div></div></div>
            </div>

            <!-- Right Sidebar: Status Management -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-md border">
                    <h2 class="text-xl font-bold text-slate-800 mb-4">Order Status</h2>
                    <div class="text-center mb-6">
                        <span class="px-4 py-2 text-lg font-bold rounded-full <?php switch($order['order_status']) { case 'Delivered': echo 'bg-green-100 text-green-800'; break; case 'Shipped': echo 'bg-blue-100 text-blue-800'; break; case 'Processing': echo 'bg-yellow-100 text-yellow-800'; break; case 'Cancelled': echo 'bg-red-100 text-red-800'; break; default: echo 'bg-gray-100 text-gray-800'; } ?>">
                            <?= e($order['order_status']) ?>
                        </span>
                    </div>

                    <?php if (has_role(ROLE_ADMIN) || has_role(ROLE_SHOP_ADMIN)): ?>
                    <div class="space-y-4">
                        <h3 class="text-md font-semibold text-gray-700">Update Status</h3>
                        <p class="text-xs text-gray-500">Select a new status to update the order. The customer will be able to see the change.</p>
                        <form action="order_process.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="order_id" value="<?= e($order['id']) ?>">
                            <input type="hidden" name="redirect_to" value="order_details.php?id=<?= e($order['id']) ?>"> <!-- **NEW: Redirect back to this page** -->

                            <select name="status" class="w-full p-3 border rounded-md bg-white mb-4">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= e($status) ?>" <?= ($order['order_status'] === $status) ? 'selected' : '' ?>><?= e($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="w-full btn-primary">Update Status</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>