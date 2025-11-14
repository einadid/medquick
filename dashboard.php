<?php
require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$shop_id = $_SESSION['shop_id'] ?? null;

$pageTitle = ucfirst($role) . " Dashboard";

include 'templates/header.php';

// প্রতিটি রোলের জন্য নির্দিষ্ট ডেটা লোড করা হবে
switch ($role) {
    case ROLE_ADMIN:
        $stats = $pdo->query("
            SELECT
                (SELECT COUNT(*) FROM users) as total_users,
                (SELECT COUNT(*) FROM medicines) as total_medicines,
                (SELECT SUM(quantity) FROM inventory_batches) as total_stock,
                (SELECT SUM(total_amount) FROM orders WHERE DATE(created_at) = CURDATE()) as today_sales
        ")->fetch();

        // Expiring Soon (less than 30 days)
        $expiring_soon = $pdo->query("
            SELECT m.name, s.name as shop_name, ib.batch_number, ib.quantity, ib.expiry_date
            FROM inventory_batches ib
            JOIN medicines m ON ib.medicine_id = m.id
            JOIN shops s ON ib.shop_id = s.id
            WHERE ib.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND ib.quantity > 0
            ORDER BY ib.expiry_date ASC
            LIMIT 10
        ")->fetchAll();
        
        // Low Stock Items
        $low_stock = $pdo->query("
            SELECT m.name, m.reorder_level, SUM(ib.quantity) as current_stock, s.name as shop_name
            FROM inventory_batches ib
            JOIN medicines m ON ib.medicine_id = m.id
            JOIN shops s ON ib.shop_id = s.id
            GROUP BY m.id, s.id
            HAVING current_stock < m.reorder_level
            LIMIT 10
        ")->fetchAll();

        include 'templates/dashboard_admin.php';
        break;

    case ROLE_SHOP_ADMIN:
        $stmt = $pdo->prepare("SELECT name FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop_name = $stmt->fetchColumn();

        $shop_stats = $pdo->prepare("
            SELECT
                (SELECT SUM(quantity) FROM inventory_batches WHERE shop_id = ?) as total_stock,
                (SELECT SUM(total_amount) FROM orders WHERE shop_id = ? AND DATE(created_at) = CURDATE()) as today_sales
        ");
        $shop_stats->execute([$shop_id, $shop_id]);
        $stats = $shop_stats->fetch();

        $low_stock = $pdo->prepare("
            SELECT m.name, m.reorder_level, ib.batch_number, ib.quantity
            FROM inventory_batches ib
            JOIN medicines m ON ib.medicine_id = m.id
            WHERE ib.shop_id = ? AND ib.quantity < m.reorder_level AND ib.quantity > 0
            ORDER BY ib.quantity ASC
            LIMIT 10
        ");
        $low_stock->execute([$shop_id]);
        $shop_low_stock = $low_stock->fetchAll();

        include 'templates/dashboard_shop_admin.php';
        break;

    case ROLE_SALESMAN:
        // সেলসম্যানের নিজের আজকের সেলস
        // Note: This requires knowing which orders were created by which salesman.
        // We'll simplify and show shop's today's sales for now.
        $salesman_stats = $pdo->prepare("
            SELECT SUM(total_amount) as today_sales, COUNT(*) as sales_count
            FROM orders
            WHERE shop_id = ? AND DATE(created_at) = CURDATE()
        ");
        $salesman_stats->execute([$shop_id]);
        $stats = $salesman_stats->fetch();
        
        include 'templates/dashboard_salesman.php';
        break;

    case ROLE_CUSTOMER:
        $orders_stmt = $pdo->prepare(
            "SELECT id, created_at, total_amount, order_status 
             FROM orders 
             WHERE customer_id = ? 
             ORDER BY created_at DESC LIMIT 5"
        );
        $orders_stmt->execute([$user_id]);
        $recent_orders = $orders_stmt->fetchAll();
        include 'templates/dashboard_customer.php';
        break;

    default:
        echo "<div class='text-center p-8'><p class='text-red-500'>Error: Unknown user role.</p></div>";
        break;
}

include 'templates/footer.php';
?>