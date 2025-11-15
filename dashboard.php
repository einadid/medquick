<?php
// FILE: dashboard.php (Final & Complete Controller for All Roles)
// PURPOSE: Acts as a central router to load the correct dashboard view and all necessary data based on user role.

require_once 'src/session.php';
require_once 'config/database.php';

// Security: User must be logged in to access the dashboard.
if (!is_logged_in()) {
    redirect('login.php');
}

// Get current user's information from the session.
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'] ?? null;
$pageTitle = ucfirst(str_replace('_', ' ', $role)) . " Dashboard";

// Initialize variables for chart data.
$chart_labels_json = "[]";
$chart_data_json = "[]";

/**
 * Helper function to get sales data for the last 7 days for charts.
 * @param PDO $pdo The database connection.
 * @param int|null $shop_id Optional shop ID to filter sales.
 * @return array An array containing JSON-encoded labels and data.
 */
function get_last_7_days_sales($pdo, $shop_id = null) {
    // Correctly fetches data including today.
    $sql = "SELECT DATE(created_at) as sale_date, SUM(total_amount) as daily_total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
    $params = [];
    if ($shop_id !== null) {
        $sql .= " AND shop_id = ?";
        $params[] = $shop_id;
    }
    $sql .= " GROUP BY sale_date ORDER BY sale_date ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $labels = [];
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('D, M j', strtotime($date));
        $data[] = (float)($sales_data[$date] ?? 0);
    }
    return ['labels' => json_encode($labels), 'data' => json_encode($data)];
}

// Load the main header, which will determine the layout.
include 'templates/header.php';

// Use a switch statement to handle logic for each role.
switch ($role) {
// dashboard.php ফাইলের switch স্টেটমেন্টের ভেতরে

// dashboard.php ফাইলের switch স্টেটমেন্টের ভেতরে

case ROLE_ADMIN:
    // --- FINAL & COMPLETE DATA FETCHING FOR ADMIN DASHBOARD ---
    try {
        // 1. Fetch KPI Card Stats
        $stats = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM users) as total_users,
                (SELECT COUNT(*) FROM medicines) as total_medicines,
                (SELECT COALESCE(SUM(quantity), 0) FROM inventory_batches) as total_stock,
                (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE()) as today_sales,
                (SELECT COALESCE(SUM((oi.price_per_unit - oi.cost_per_unit) * oi.quantity), 0) 
                 FROM order_items oi JOIN orders o ON oi.order_id = o.id
                 WHERE DATE(o.created_at) = CURDATE()) as today_profit
        ")->fetch();

        // 2. Data for Charts (remains the same)
        $chart_data = get_last_7_days_sales($pdo);
        $chart_labels_json = $chart_data['labels'];
        $chart_data_json = $chart_data['data'];
        
        $sales_by_shop_stmt = $pdo->query("SELECT s.name, COALESCE(SUM(o.total_amount), 0) as total FROM shops s LEFT JOIN orders o ON s.id = o.shop_id GROUP BY s.id ORDER BY total DESC");
        $sales_by_shop = $sales_by_shop_stmt->fetchAll(PDO::FETCH_ASSOC);
        $pie_chart_labels_json = json_encode(array_column($sales_by_shop, 'name'));
        $pie_chart_data_json = json_encode(array_column($sales_by_shop, 'total'));
        
        // 3. **CRITICAL: Fetch all users for the dashboard table**
        $all_users = $pdo->query("
            SELECT u.id, u.full_name, u.email, u.role, u.is_active, s.name as shop_name 
            FROM users u 
            LEFT JOIN shops s ON u.shop_id = s.id 
            ORDER BY u.created_at DESC
        ")->fetchAll();

    } catch (PDOException $e) {
        error_log("Admin Dashboard DB Error: " . $e->getMessage());
        // Set empty defaults on error
        $stats = []; $all_users = []; $chart_labels_json = "[]"; $chart_data_json = "[]";
        $pie_chart_labels_json = "[]"; $pie_chart_data_json = "[]";
    }
    
    include 'templates/dashboard_admin.php';
    break;

    // dashboard.php ফাইলের switch স্টেটমেন্টের ভেতরে

case ROLE_SHOP_ADMIN:
    if (empty($shop_id)) { redirect('login.php?error=session_issue'); }
    try {
        // Fetch shop name
        $stmt = $pdo->prepare("SELECT name FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop_name = $stmt->fetchColumn() ?: "Unknown Shop";

        // --- 1. Main Stats Cards Data ---
        $stats_stmt = $pdo->prepare("
            SELECT 
                (SELECT COALESCE(SUM(quantity),0) FROM inventory_batches WHERE shop_id = ?) as total_stock,
                (SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE shop_id = ? AND DATE(created_at) = CURDATE()) as today_sales
        ");
        $stats_stmt->execute([$shop_id, $shop_id]);
        $stats = $stats_stmt->fetch();

        // --- 2. Order Status Counts ---
        $order_counts_stmt = $pdo->prepare("
            SELECT order_status, COUNT(*) as count 
            FROM orders 
            WHERE shop_id = ? AND order_status IN ('Pending', 'Processing', 'Shipped')
            GROUP BY order_status
        ");
        $order_counts_stmt->execute([$shop_id]);
        $order_counts = $order_counts_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Creates [status => count]

        // --- 3. Top Selling Products (Last 30 days) ---
        $top_selling_stmt = $pdo->prepare("
            SELECT m.name, SUM(oi.quantity) as total_sold
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN medicines m ON oi.medicine_id = m.id
            WHERE o.shop_id = ? AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY oi.medicine_id
            ORDER BY total_sold DESC
            LIMIT 5
        ");
        $top_selling_stmt->execute([$shop_id]);
        $top_selling_products = $top_selling_stmt->fetchAll();

        // --- 4. Inventory Health Metrics ---
        $inventory_health_stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT medicine_id) as total_products,
                SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock_count
            FROM inventory_batches ib
            JOIN medicines m ON ib.medicine_id = m.id
            WHERE ib.shop_id = ?
        ");
        $inventory_health_stmt->execute([$shop_id]);
        $inventory_health = $inventory_health_stmt->fetch();
        
        $total_products = $inventory_health['total_products'] ?? 0;
        $low_stock_count = $inventory_health['low_stock_count'] ?? 0;
        $healthy_stock_count = $total_products - $low_stock_count;
        $healthy_percentage = ($total_products > 0) ? round(($healthy_stock_count / $total_products) * 100) : 100;

    } catch (PDOException $e) {
        error_log("Shop Admin Dashboard DB Error: " . $e->getMessage());
        // Set default empty values on error
        $shop_name = "Error"; $stats = []; $order_counts = []; $top_selling_products = []; $inventory_health = []; $healthy_percentage = 0;
    }
    
    include 'templates/dashboard_shop_admin.php';
    break;

    case ROLE_SALESMAN:
        if (empty($shop_id)) { redirect('login.php?error=session_issue'); }
        try {
            $salesman_stats = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) as today_sales, COUNT(*) as sales_count FROM orders WHERE shop_id = ? AND salesman_id = ? AND DATE(created_at) = CURDATE() AND order_source = 'pos'");
            $salesman_stats->execute([$shop_id, $user_id]);
            $stats = $salesman_stats->fetch();

            $low_stock_stmt = $pdo->prepare("SELECT m.name, SUM(ib.quantity) as current_stock FROM inventory_batches ib JOIN medicines m ON ib.medicine_id = m.id WHERE ib.shop_id = ? AND ib.quantity > 0 GROUP BY m.id, m.name, m.reorder_level HAVING current_stock <= m.reorder_level ORDER BY current_stock ASC LIMIT 5");
            $low_stock_stmt->execute([$shop_id]);
            $low_stock_items = $low_stock_stmt->fetchAll();

            $expiring_soon_stmt = $pdo->prepare("SELECT m.name, ib.batch_number, ib.expiry_date FROM inventory_batches ib JOIN medicines m ON ib.medicine_id = m.id WHERE ib.shop_id = ? AND ib.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND ib.quantity > 0 ORDER BY ib.expiry_date ASC LIMIT 5");
            $expiring_soon_stmt->execute([$shop_id]);
            $expiring_soon_items = $expiring_soon_stmt->fetchAll();

            $recent_sales_stmt = $pdo->prepare("SELECT o.id, o.created_at, o.total_amount FROM orders o WHERE o.salesman_id = ? ORDER BY o.created_at DESC LIMIT 10");
            $recent_sales_stmt->execute([$user_id]);
            $recent_sales = $recent_sales_stmt->fetchAll();
        } catch (PDOException $e) { /* Error handling */ }
        include 'templates/dashboard_salesman.php';
        break;

    // dashboard.php ফাইলের switch স্টেটমেন্টের ভেতরে

case ROLE_CUSTOMER:
    // --- FINAL & COMPLETE DATA FETCHING FOR CUSTOMER DASHBOARD ---
    try {
        // 1. Fetch user's name and points balance
        $user_stmt = $pdo->prepare("SELECT full_name, points_balance FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user_data = $user_stmt->fetch();
        $stats['health_wallet_points'] = $user_data['points_balance'] ?? 0;

        // 2. Fetch total order count
        $orders_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
        $orders_count_stmt->execute([$user_id]);
        $stats['total_orders'] = $orders_count_stmt->fetchColumn();

        // 3. Fetch the very latest order for the status tracker
        $latest_order_stmt = $pdo->prepare("SELECT id, order_status FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
        $latest_order_stmt->execute([$user_id]);
        $latest_order = $latest_order_stmt->fetch();

        // 4. Fetch frequently purchased items for quick re-order
        $frequent_items_stmt = $pdo->prepare("
            SELECT 
                m.id, m.name, m.image_path, MIN(ib.price) as price,
                (SELECT SUM(quantity) FROM inventory_batches WHERE medicine_id = m.id AND quantity > 0) as total_stock
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN medicines m ON oi.medicine_id = m.id
            LEFT JOIN inventory_batches ib ON m.id = ib.medicine_id AND ib.quantity > 0
            WHERE o.customer_id = ?
            GROUP BY oi.medicine_id
            ORDER BY COUNT(oi.medicine_id) DESC, m.name ASC
            LIMIT 4
        ");
        $frequent_items_stmt->execute([$user_id]);
        $frequent_items = $frequent_items_stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Customer Dashboard DB Error: " . $e->getMessage());
        $stats = []; $latest_order = null; $frequent_items = [];
    }
    
    include 'templates/dashboard_customer.php';
    break;

    default:
        echo "<div class='text-center p-8'><p class='text-red-500'>Error: Unknown user role.</p></div>";
        break;
}

// Include Chart.js library and initialization script if chart data exists
if ($chart_labels_json !== "[]" && $chart_data_json !== "[]") {
    echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script>document.addEventListener("DOMContentLoaded",function(){const e=document.getElementById("salesChart");if(e){new Chart(e.getContext("2d"),{type:"line",data:{labels:'.$chart_labels_json.',datasets:[{label:"Daily Sales",data:'.$chart_data_json.',backgroundColor:"rgba(13,148,136,0.1)",borderColor:"#0D9488",borderWidth:3,pointBackgroundColor:"#0D9488",pointRadius:5,pointHoverRadius:7,tension:.4,fill:true}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{callback:function(e){return"৳"+e.toLocaleString()}}}},plugins:{legend:{display:false},tooltip:{callbacks:{label:function(e){return" Sales: ৳"+e.parsed.y.toLocaleString()}}}}})}});</script>';
}

include 'templates/footer.php';
?>