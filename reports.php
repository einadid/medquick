<?php
require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

if (!is_logged_in() || (!has_role(ROLE_ADMIN) && !has_role(ROLE_SHOP_ADMIN))) {
    redirect('dashboard.php');
}

// Filters
$filter_shop_id = $_GET['shop_id'] ?? '';
$filter_start_date = $_GET['start_date'] ?? date('Y-m-01');
$filter_end_date = $_GET['end_date'] ?? date('Y-m-d');

// Shop Admin can only see their own shop's report
if (has_role(ROLE_SHOP_ADMIN)) {
    $filter_shop_id = $_SESSION['shop_id'];
}

// Fetch shops for the filter dropdown (for Admin)
$shops = [];
if (has_role(ROLE_ADMIN)) {
    $shops = $pdo->query("SELECT id, name FROM shops")->fetchAll();
}

// Build the query
$params = [];
$sql = "SELECT o.id, o.created_at, o.total_amount, o.order_status, o.order_source, s.name as shop_name, u.full_name as customer_name
        FROM orders o
        JOIN shops s ON o.shop_id = s.id
        JOIN users u ON o.customer_id = u.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?";
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

$pageTitle = "Sales Report";
include 'templates/header.php';
?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Sales Report</h1>

    <!-- Filter Form -->
    <form method="GET" action="reports.php" class="bg-white p-4 rounded-lg shadow-md mb-6 flex flex-wrap items-end gap-4">
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
            <select name="shop_id" id="shop_id" class="mt-1 p-2 border rounded-md">
                <option value="">All Shops</option>
                <?php foreach($shops as $shop): ?>
                <option value="<?= e($shop['id']) ?>" <?= $filter_shop_id == $shop['id'] ? 'selected' : '' ?>><?= e($shop['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Apply Filter</button>
        </div>
        <div class="ml-auto flex gap-2">
            <button disabled class="bg-gray-400 text-white px-4 py-2 rounded-md cursor-not-allowed" title="Coming Soon">Export CSV</button>
            <button disabled class="bg-gray-400 text-white px-4 py-2 rounded-md cursor-not-allowed" title="Coming Soon">Export PDF</button>
        </div>
    </form>

    <!-- Report Table -->
    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if(empty($orders)): ?>
                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No sales found for the selected criteria.</td></tr>
                <?php else: foreach($orders as $order): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= e($order['id']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= e($order['shop_name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= e($order['customer_name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="px-2 py-1 text-xs font-semibold rounded-full <?= $order['order_source'] == 'pos' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>"><?= e(strtoupper($order['order_source'])) ?></span></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 font-semibold text-right">৳<?= e(number_format($order['total_amount'], 2)) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>