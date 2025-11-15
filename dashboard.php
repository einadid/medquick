<?php
// FILE: dashboard.php (The Ultimate, Final, and Corrected Version)
require_once 'src/session.php';
require_once 'config/database.php';
ensure_user_session_data();

if (!is_logged_in()) { redirect('login.php'); }

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'] ?? null;
$pageTitle = ucfirst(str_replace('_', ' ', $role)) . " Dashboard";

// Initialize variables
$stats = []; $chart_labels_json = "[]"; $chart_data_json = "[]"; $pie_chart_labels_json = "[]"; $pie_chart_data_json = "[]";
$all_users = []; $recent_orders = []; $low_stock_items = []; $shop_inventory = [];

/**
 * Helper function for chart data
 */
function get_sales_data_for_chart($pdo, $shop_id = null, $days = 7) {
    // ... (This function is correct from before) ...
}

include 'templates/header.php';
?>

<main class="w-full">
    <div class="fade-in">
        <?php
        try {
            switch ($role) {
                case ROLE_ADMIN:
                    // --- **NEW & SIMPLIFIED SQL QUERIES for Admin Stats** ---

                    // KPI 1: Today's Sales
                    $today_sales_stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE()");
                    $stats['today_sales'] = $today_sales_stmt->fetchColumn();

                    // KPI 2: Today's Profit
                    $today_profit_stmt = $pdo->query("SELECT COALESCE(SUM((oi.price_per_unit - oi.cost_per_unit) * oi.quantity), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) = CURDATE()");
                    $stats['today_profit'] = $today_profit_stmt->fetchColumn();

                    // KPI 3: Other stats
                    $other_stats_stmt = $pdo->query("SELECT (SELECT COUNT(*) FROM users) as total_users, (SELECT COUNT(*) FROM medicines) as total_medicines, (SELECT COALESCE(SUM(quantity), 0) FROM inventory_batches) as total_stock, (SELECT COUNT(*) FROM orders WHERE order_status = 'Pending') as pending_orders, (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today");
                    $other_stats = $other_stats_stmt->fetch(PDO::FETCH_ASSOC);
                    $stats = array_merge($stats, $other_stats);

                    // Chart Data (same as before)
                    // ...
                    
                    // Recent Orders & Low Stock (same as before)
                    $recent_orders = $pdo->query("SELECT o.id, o.total_amount, u.full_name FROM orders o JOIN users u ON o.customer_id = u.id ORDER BY o.id DESC LIMIT 5")->fetchAll();
                    $low_stock_items = $pdo->query("SELECT m.name, SUM(ib.quantity) as current_stock FROM inventory_batches ib JOIN medicines m ON ib.medicine_id=m.id WHERE ib.quantity > 0 GROUP BY m.id, m.name, m.reorder_level HAVING current_stock <= m.reorder_level LIMIT 5")->fetchAll();
                    
                    include 'templates/dashboard_admin.php';
                    break;
                
                // ... (Other roles' cases remain the same) ...
                case ROLE_SHOP_ADMIN:
                    // ...
                    break;
                // ...
            }
        } catch (PDOException $e) {
            error_log("Dashboard page master error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            echo "<div class='p-6 container mx-auto'><div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md'><strong>Database Error:</strong> Could not load all dashboard data. Please check server logs for details.</div></div>";
        }
        ?>
    </div>
</main>

<?php
// Include Chart.js script if chart data exists
// ... (Chart script remains the same) ...
include 'templates/footer.php';
?>