<?php
require_once 'src/session.php';
require_once 'config/database.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$role = $_SESSION['role'];
$pageTitle = ucfirst($role) . " Dashboard";

include 'templates/header.php';

switch ($role) {
    case ROLE_ADMIN:
        // Fetch admin-specific data (totals, all shops, etc.)
        // Example: $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        include 'templates/dashboard_admin.php';
        break;
    case ROLE_SHOP_ADMIN:
        // Fetch data for the specific shop admin
        // Example: $shopId = $_SESSION['shop_id'];
        // $lowStockItems = $pdo->prepare("... WHERE shop_id = ? ...");
        include 'templates/dashboard_shop_admin.php';
        break;
    case ROLE_SALESMAN:
        include 'templates/dashboard_salesman.php';
        break;
    case ROLE_CUSTOMER:
        // Fetch customer's recent orders
        include 'templates/dashboard_customer.php';
        break;
    default:
        echo "<p>Unknown user role.</p>";
        break;
}

include 'templates/footer.php';