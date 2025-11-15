<?php
// FILE: orders.php (Final Professional Version for Customer & Admins)
require_once 'src/session.php';
require_once 'config/database.php';

if (!is_logged_in()) {
    redirect('login.php?redirect=orders.php');
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'] ?? null;

$pageTitle = "Order History";
if ($role !== ROLE_CUSTOMER) {
    $pageTitle = "Manage Orders";
}

try {
    $sql = "
        SELECT o.id, o.created_at, o.total_amount, o.order_status, 
               u.full_name as customer_name, s.name as shop_name
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        JOIN shops s ON o.shop_id = s.id
    ";
    $params = [];

    // Filter orders based on user role
    if ($role === ROLE_CUSTOMER) {
        $sql .= " WHERE o.customer_id = ?";
        $params[] = $user_id;
    } elseif ($role === ROLE_SHOP_ADMIN) {
        $sql .= " WHERE o.shop_id = ? AND o.order_source = 'web'"; // Shop admin sees online orders
        $params[] = $shop_id;
    }
    // Admin sees all orders

    $sql .= " ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Orders page error: " . $e->getMessage());
    $orders = [];
    $db_error = "Could not fetch order history.";
}

$statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

include 'templates/header.php';
?>

<div class="fade-in bg-slate-50 py-12">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="flex flex-col <?= has_role(ROLE_CUSTOMER) ? 'lg:flex-row' : '' ?> gap-8">
            
            <!-- Sidebar Navigation (only for customers) -->
            <?php if (has_role(ROLE_CUSTOMER)): ?>
                <?php include 'templates/_customer_sidebar.php'; ?>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="w-full <?= has_role(ROLE_CUSTOMER) ? 'lg:w-3/4' : '' ?>">
                <h1 class="text-3xl font-bold text-slate-800 mb-8"><?= e($pageTitle) ?></h1>

                <!-- Session Messages -->
                <?php if (isset($_SESSION['success_message'])): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($_SESSION['success_message']); ?></p></div><?php unset($_SESSION['success_message']); ?><?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= e($_SESSION['error_message']); ?></p></div><?php unset($_SESSION['error_message']); ?><?php endif; ?>
                
                <div class="bg-white p-6 rounded-lg shadow-md border">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <?php if ($role !== ROLE_CUSTOMER): ?><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th><?php endif; ?>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="6" class="text-center py-12 text-gray-500">No order history found.</td></tr>
                                <?php else: foreach($orders as $order): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= e($order['id']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d M, Y', strtotime($order['created_at'])) ?></td>
                                        <?php if ($role !== ROLE_CUSTOMER): ?><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= e($order['customer_name']) ?></td><?php endif; ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-800">৳<?= e(number_format($order['total_amount'], 2)) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php switch($order['order_status']) { case 'Delivered': echo 'bg-green-100 text-green-800'; break; case 'Shipped': echo 'bg-blue-100 text-blue-800'; break; case 'Processing': echo 'bg-yellow-100 text-yellow-800'; break; case 'Cancelled': echo 'bg-red-100 text-red-800'; break; default: echo 'bg-gray-100 text-gray-800'; } ?>">
                                                <?= e($order['order_status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <?php if (has_role(ROLE_CUSTOMER)): ?>
                                                <a href="order_details.php?id=<?= e($order['id']) ?>" class="font-medium text-teal-600 hover:underline">View Details</a>
                                            <?php else: ?>
                                                <div class="flex items-center justify-end gap-4">
                                                    <a href="order_details.php?id=<?= e($order['id']) ?>" class="font-medium text-teal-600 hover:underline" title="View full invoice">Details</a>
                                                    <form action="order_process.php" method="POST" class="inline"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="order_id" value="<?= e($order['id']) ?>"><select name="status" class="rounded-md border-gray-300 shadow-sm text-xs focus:ring-teal-500 focus:border-teal-500" onchange="this.form.submit()" title="Change order status"><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= ($order['order_status'] === $status) ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></form>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>