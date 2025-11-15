<?php
// FILE: reports.php (Final Professional Version)
// PURPOSE: Displays a filterable sales report for Admins and Shop Admins.

require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

// Security: Allow access only to Admins and Shop Admins.
if (!is_logged_in() || (!has_role(ROLE_ADMIN) && !has_role(ROLE_SHOP_ADMIN))) {
    redirect('dashboard.php');
}

// Get filter values from the URL, with defaults for the current month.
$filter_shop_id = $_GET['shop_id'] ?? '';
$filter_start_date = $_GET['start_date'] ?? date('Y-m-01');
$filter_end_date = $_GET['end_date'] ?? date('Y-m-d');

// Security override: A Shop Admin can ONLY see their own shop's report.
if (has_role(ROLE_SHOP_ADMIN)) {
    $filter_shop_id = $_SESSION['shop_id'];
}

// Fetch all shop names for the filter dropdown (only for Admin).
$shops = [];
if (has_role(ROLE_ADMIN)) {
    try {
        $shops = $pdo->query("SELECT id, name FROM shops ORDER BY name")->fetchAll();
    } catch (PDOException $e) {
        error_log("Reports page: could not fetch shops. " . $e->getMessage());
    }
}

// Build the main SQL query based on the filters.
try {
    $params = [];
    $sql = "
        SELECT o.id, o.created_at, o.total_amount, o.order_status, o.order_source, 
               s.name as shop_name, u.full_name as customer_name
        FROM orders o
        JOIN shops s ON o.shop_id = s.id
        JOIN users u ON o.customer_id = u.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
    ";
    $params[] = $filter_start_date;
    $params[] = $filter_end_date;

    if (!empty($filter_shop_id)) {
        $sql .= " AND o.shop_id = ?";
        $params[] = $filter_shop_id;
    }

    $sql .= " ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Calculate totals for the summary display.
    $total_sales_amount = array_sum(array_column($orders, 'total_amount'));
    $total_transactions = count($orders);

} catch (PDOException $e) {
    $orders = [];
    $db_error = "Could not fetch sales report. Please try again later.";
    error_log("Reports page: main query failed. " . $e->getMessage());
}

$pageTitle = "Sales Report";
include 'templates/header.php';
?>

<div class="fade-in p-4 sm:p-6">
    <h1 class="text-3xl font-bold text-slate-800 mb-6">Sales Report</h1>
    
    <!-- Filter Form with Summary -->
    <form method="GET" action="reports.php" class="bg-white p-4 rounded-lg shadow-md mb-8">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="<?= e($filter_start_date) ?>" class="mt-1 p-2 border rounded-md">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="date" name="end_date" id="end_date" value="<?= e($filter_end_date) ?>" class="mt-1 p-2 border rounded-md">
            </div>

            <?php if (has_role(ROLE_ADMIN)): ?>
            <div>
                <label for="shop_id" class="block text-sm font-medium text-gray-700">Shop</label>
                <select name="shop_id" id="shop_id" class="mt-1 p-2 border rounded-md w-48">
                    <option value="">All Shops</option>
                    <?php foreach($shops as $shop): ?>
                    <option value="<?= e($shop['id']) ?>" <?= ($filter_shop_id == $shop['id']) ? 'selected' : '' ?>><?= e($shop['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <button type="submit" class="btn-primary py-2 px-6">Filter</button>
            </div>
            <div class="ml-auto flex flex-col sm:flex-row gap-6 text-right">
                <div>
                    <p class="text-gray-500">Transactions</p>
                    <p class="text-2xl font-bold text-slate-700"><?= e($total_transactions ?? 0) ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Total Sales</p>
                    <p class="text-2xl font-bold text-teal-600">৳<?= e(number_format($total_sales_amount ?? 0, 2)) ?></p>
                </div>
            </div>
        </div>
    </form>

    <?php if (isset($db_error)): ?>
        <div class="bg-red-100 p-4 rounded-md text-red-700 text-center"><?= e($db_error); ?></div>
    <?php else: ?>
    <!-- Report Table -->
    <div class="bg-white p-6 rounded-lg shadow-md border">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if(empty($orders)): ?>
                        <tr><td colspan="7" class="text-center py-10 text-gray-500">No sales records found for the selected criteria.</td></tr>
                    <?php else: foreach($orders as $order): ?>
                    <tr class="hover:bg-gray-50 <?= str_contains($order['order_status'], 'Returned') || $order['order_status'] === 'Cancelled' ? 'bg-red-50 opacity-80' : '' ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= e($order['id']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= e($order['shop_name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= e($order['customer_name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="px-2 py-1 text-xs font-semibold rounded-full <?= $order['order_source'] == 'pos' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>"><?= e(strtoupper($order['order_source'])) ?></span></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                             <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php switch($order['order_status']) { case 'Delivered': echo 'bg-green-100 text-green-800'; break; case 'Shipped': echo 'bg-blue-100 text-blue-800'; break; case 'Processing': echo 'bg-yellow-100 text-yellow-800'; break; case 'Cancelled': case 'Returned': echo 'bg-red-100 text-red-800'; break; default: echo 'bg-gray-100 text-gray-800'; } ?>">
                                <?= e($order['order_status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-semibold text-right">৳<?= e(number_format($order['total_amount'], 2)) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>