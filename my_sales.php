<?php
// FILE: my_sales.php (Final Version with Returns Information)
// PURPOSE: Allows a salesman to view their own sales and returns report.

require_once 'src/session.php';
require_once 'config/database.php';

// Security check
if (!has_role(ROLE_SALESMAN)) {
    redirect('dashboard.php');
}

$salesman_id = $_SESSION['user_id'];
$pageTitle = "My Sales & Returns Report";

// Filters: Default to the current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    // --- 1. Fetch Sales Data ---
    $sales_sql = "
        SELECT o.id, o.created_at, o.total_amount, u.full_name as customer_name
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        WHERE o.salesman_id = ? AND o.order_source = 'pos' AND DATE(o.created_at) BETWEEN ? AND ?
        ORDER BY o.created_at DESC
    ";
    $sales_params = [$salesman_id, $start_date, $end_date];
    $sales_stmt = $pdo->prepare($sales_sql);
    $sales_stmt->execute($sales_params);
    $sales = $sales_stmt->fetchAll();
    
    $total_sales_amount = array_sum(array_column($sales, 'total_amount'));
    $total_transactions = count($sales);

    // --- 2. **NEW: Fetch Returns Data** ---
    $returns_sql = "
        SELECT r.id, r.created_at, r.returned_amount, r.original_order_id, u.full_name as processed_by
        FROM returns r
        JOIN users u ON r.salesman_id = u.id
        WHERE r.salesman_id = ? AND DATE(r.created_at) BETWEEN ? AND ?
        ORDER BY r.created_at DESC
    ";
    $returns_params = [$salesman_id, $start_date, $end_date];
    $returns_stmt = $pdo->prepare($returns_sql);
    $returns_stmt->execute($returns_params);
    $returns = $returns_stmt->fetchAll();

    $total_refund_amount = array_sum(array_column($returns, 'returned_amount'));

} catch (PDOException $e) {
    error_log("My Sales Report Error: " . $e->getMessage());
    $sales = []; $returns = [];
    $total_sales_amount = 0; $total_transactions = 0; $total_refund_amount = 0;
    $_SESSION['error_message'] = "Could not load your full report.";
}

// Load the main layout
include 'templates/header.php';
?>

<!-- This content will be placed inside the main layout provided by header.php -->
<div class="fade-in p-4 sm:p-6">
    <h1 class="text-3xl font-bold text-slate-800 mb-6">My Sales & Returns Report</h1>

    <!-- Filter Form with Summary Cards -->
    <form method="GET" action="my_sales.php" class="bg-white p-4 rounded-lg shadow-md mb-8">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="<?= e($start_date) ?>" class="mt-1 p-2 border rounded-md">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="date" name="end_date" id="end_date" value="<?= e($end_date) ?>" class="mt-1 p-2 border rounded-md">
            </div>
            <div><button type="submit" class="btn-primary py-2 px-6">Filter</button></div>
        </div>
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 border-t pt-4">
            <div class="text-center sm:text-left"><p class="text-gray-500">Transactions</p><p class="text-2xl font-bold text-slate-700"><?= e($total_transactions) ?></p></div>
            <div class="text-center sm:text-left"><p class="text-gray-500">Total Sales</p><p class="text-2xl font-bold text-green-600">৳<?= e(number_format($total_sales_amount, 2)) ?></p></div>
            <div class="text-center sm:text-left"><p class="text-gray-500">Total Refunded</p><p class="text-2xl font-bold text-red-600">৳<?= e(number_format($total_refund_amount, 2)) ?></p></div>
        </div>
    </form>
    
    <!-- Grid for Sales and Returns tables -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        <!-- Sales Table -->
        <div class="bg-white p-6 rounded-lg shadow-md border">
            <h2 class="text-2xl font-bold text-slate-800 mb-4">Sales Details</h2>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium uppercase">Order ID</th><th class="px-4 py-3 text-left text-xs font-medium uppercase">Time</th><th class="px-4 py-3 text-right text-xs font-medium uppercase">Amount</th><th class="px-4 py-3 text-right text-xs font-medium uppercase">Action</th></tr></thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if(empty($sales)): ?><tr><td colspan="4" class="text-center py-10 text-gray-500">No sales found.</td></tr>
                    <?php else: foreach($sales as $sale): ?>
                    <tr class="hover:bg-gray-50"><td class="px-4 py-3 text-sm font-medium">#<?= e($sale['id']) ?></td><td class="px-4 py-3 text-sm text-gray-500"><?= date('h:i A', strtotime($sale['created_at'])) ?></td><td class="px-4 py-3 text-sm text-right font-semibold">৳<?= e(number_format($sale['total_amount'], 2)) ?></td><td class="px-4 py-3 text-right text-sm"><a href="order_details.php?id=<?= e($sale['id']) ?>" class="font-medium text-teal-600 hover:underline">Details</a></td></tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- **NEW: Returns Table** -->
        <div class="bg-white p-6 rounded-lg shadow-md border">
            <h2 class="text-2xl font-bold text-slate-800 mb-4">Returns Details</h2>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium uppercase">Return ID</th><th class="px-4 py-3 text-left text-xs font-medium uppercase">Time</th><th class="px-4 py-3 text-right text-xs font-medium uppercase">Refunded</th><th class="px-4 py-3 text-right text-xs font-medium uppercase">Original Order</th></tr></thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if(empty($returns)): ?><tr><td colspan="4" class="text-center py-10 text-gray-500">No returns found.</td></tr>
                    <?php else: foreach($returns as $return): ?>
                    <tr class="hover:bg-gray-50"><td class="px-4 py-3 text-sm font-medium">#<?= e($return['id']) ?></td><td class="px-4 py-3 text-sm text-gray-500"><?= date('h:i A', strtotime($return['created_at'])) ?></td><td class="px-4 py-3 text-sm text-right font-semibold text-red-600">৳<?= e(number_format($return['returned_amount'], 2)) ?></td><td class="px-4 py-3 text-right text-sm"><a href="order_details.php?id=<?= e($return['original_order_id']) ?>" class="font-medium text-teal-600 hover:underline">View Order</a></td></tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>