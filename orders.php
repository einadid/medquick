<?php
// FILE: orders.php (Final Professional Version - Mobile Responsive Table)
// PURPOSE: Displays a list of orders. Admins/ShopAdmins can manage them, Customers can view their own.

require_once 'src/session.php';
require_once 'config/database.php';

// Security: User must be logged in to access this page.
if (!is_logged_in()) {
    redirect('login.php?redirect=orders.php');
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'] ?? null;

$pageTitle = ($role === ROLE_CUSTOMER) ? "My Orders" : "Manage Orders";

// --- DATA FETCHING ---
try {
    $sql = "
        SELECT 
            o.id, o.created_at, o.total_amount, o.order_status, 
            u.full_name as customer_name, s.name as shop_name
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        JOIN shops s ON o.shop_id = s.id
    ";
    $params = [];
    $where_clauses = [];

    // Filter orders based on user role
    if ($role === ROLE_CUSTOMER) {
        $where_clauses[] = "o.customer_id = ?";
        $params[] = $user_id;
    } elseif ($role === ROLE_SHOP_ADMIN) {
        // NEW: Show only online orders for Shop Admin
        $where_clauses[] = "o.shop_id = ?";
        $params[] = $shop_id;
        $where_clauses[] = "o.order_source = 'web'";
    }
    // Admin sees all orders, so no WHERE clause is added for them.

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Orders page error: " . $e->getMessage());
    $orders = [];
    $db_error = "Could not fetch orders at this time. Please try again later.";
}

// Define the possible order statuses for the dropdown.
$statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

include 'templates/header.php';
?>

<div class="fade-in bg-slate-50 py-8 sm:py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
            
            <!-- Sidebar for Customer View -->
            <?php if ($role === ROLE_CUSTOMER): ?>
                <?php include 'templates/_customer_sidebar.php'; ?>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="w-full <?= $role === ROLE_CUSTOMER ? 'lg:w-3/4' : '' ?>">
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 mb-6 sm:mb-8"><?= e($pageTitle) ?></h1>

                <!-- Display success/error messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                        <p><?= e($_SESSION['success_message']); ?></p>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                        <p><?= e($_SESSION['error_message']); ?></p>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md border">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-8 sm:py-12 text-gray-500 text-sm">No orders found.</div>
                    <?php else: ?>
                        <div class="overflow-x-auto hidden md:block">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                                        <th class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <?php if ($role !== ROLE_CUSTOMER): ?><th class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th><?php endif; ?>
                                        <?php if ($role === ROLE_ADMIN): ?><th class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop</th><?php endif; ?>
                                        <th class="px-3 py-2 sm:px-6 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <th class="px-3 py-2 sm:px-6 sm:py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-3 py-2 sm:px-6 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach($orders as $order): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= e($order['id']) ?></td>
                                            <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-500"><?= date('d M, Y', strtotime($order['created_at'])) ?></td>
                                            <?php if ($role !== ROLE_CUSTOMER): ?><td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm text-gray-700"><?= e($order['customer_name']) ?></td><?php endif; ?>
                                            <?php if ($role === ROLE_ADMIN): ?><td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm text-gray-500"><?= e($order['shop_name']) ?></td><?php endif; ?>
                                            <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-800">৳<?= e(number_format($order['total_amount'], 2)) ?></td>
                                            <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-center text-sm">
                                                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 text-xs font-semibold rounded-full <?php switch($order['order_status']) { case 'Delivered': echo 'bg-green-100 text-green-800'; break; case 'Shipped': echo 'bg-blue-100 text-blue-800'; break; case 'Processing': echo 'bg-yellow-100 text-yellow-800'; break; case 'Cancelled': echo 'bg-red-100 text-red-800'; break; default: echo 'bg-gray-100 text-gray-800'; } ?>">
                                                    <?= e($order['order_status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <?php if ($role !== ROLE_CUSTOMER): // For Admin and Shop Admin ?>
                                                    <div class="flex flex-col sm:flex-row items-end sm:items-center justify-end gap-2 sm:gap-4">
                                                        <a href="order_details.php?id=<?= e($order['id']) ?>" class="font-medium text-teal-600 hover:underline text-sm" title="View full invoice">Details</a>
                                                        <form action="order_process.php" method="POST" class="inline-block">
                                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                                            <input type="hidden" name="order_id" value="<?= e($order['id']) ?>">
                                                            <select name="status" class="rounded-md border-gray-300 shadow-sm text-xs focus:ring-teal-500 focus:border-teal-500 min-w-[90px] sm:min-w-0" onchange="this.form.submit()" title="Change order status">
                                                                <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= ($order['order_status'] === $status) ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
                                                            </select>
                                                        </form>
                                                    </div>
                                                <?php else: // For Customer ?>
                                                    <a href="order_details.php?id=<?= e($order['id']) ?>" class="font-medium text-teal-600 hover:underline text-sm">View Details</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="md:hidden space-y-4">
                            <?php foreach($orders as $order): ?>
                                <div class="bg-white p-4 rounded-lg shadow-md border">
                                    <div class="flex justify-between items-center mb-2">
                                        <div class="font-bold text-lg text-gray-900">Order #<?= e($order['id']) ?></div>
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?php switch($order['order_status']) { case 'Delivered': echo 'bg-green-100 text-green-800'; break; case 'Shipped': echo 'bg-blue-100 text-blue-800'; break; case 'Processing': echo 'bg-yellow-100 text-yellow-800'; break; case 'Cancelled': echo 'bg-red-100 text-red-800'; break; default: echo 'bg-gray-100 text-gray-800'; } ?>">
                                            <?= e($order['order_status']) ?>
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-2">
                                        <span class="font-medium">Date:</span> <?= date('d M, Y', strtotime($order['created_at'])) ?>
                                    </div>
                                    <?php if ($role !== ROLE_CUSTOMER): ?>
                                        <div class="text-sm text-gray-600 mb-2">
                                            <span class="font-medium">Customer:</span> <?= e($order['customer_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($role === ROLE_ADMIN): ?>
                                        <div class="text-sm text-gray-600 mb-2">
                                            <span class="font-medium">Shop:</span> <?= e($order['shop_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100">
                                        <div class="text-base font-semibold text-gray-800">Total: ৳<?= e(number_format($order['total_amount'], 2)) ?></div>
                                        <?php if ($role !== ROLE_CUSTOMER): ?>
                                            <div class="flex flex-col items-end gap-2">
                                                <a href="order_details.php?id=<?= e($order['id']) ?>" class="font-medium text-teal-600 hover:underline text-sm" title="View full invoice">Details</a>
                                                <form action="order_process.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="order_id" value="<?= e($order['id']) ?>">
                                                    <select name="status" class="rounded-md border-gray-300 shadow-sm text-xs focus:ring-teal-500 focus:border-teal-500 min-w-[90px]" onchange="this.form.submit()" title="Change order status">
                                                        <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= ($order['order_status'] === $status) ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
                                                    </select>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <a href="order_details.php?id=<?= e($order['id']) ?>" class="font-medium text-teal-600 hover:underline text-sm">View Details</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>