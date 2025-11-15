<?php
// FILE: order_process.php (Upgraded with Dynamic Redirect)
require_once 'src/session.php';
require_once 'config/database.php';

if (!is_logged_in() || (!has_role(ROLE_ADMIN) && !has_role(ROLE_SHOP_ADMIN))) {
    redirect('dashboard.php');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('orders.php');
}
verify_csrf_token($_POST['csrf_token']);

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$new_status = $_POST['status'] ?? '';
$shop_id = $_SESSION['shop_id'] ?? null;
// **NEW: Get the redirect URL, default to orders.php**
$redirect_url = $_POST['redirect_to'] ?? 'orders.php';

$allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
if ($order_id <= 0 || !in_array($new_status, $allowed_statuses)) {
    $_SESSION['error_message'] = "Invalid data provided.";
    redirect($redirect_url); // Redirect back to the previous page
}

try {
    $sql = "UPDATE orders SET order_status = ? WHERE id = ?";
    $params = [$new_status, $order_id];

    if (has_role(ROLE_SHOP_ADMIN)) {
        $sql .= " AND shop_id = ?";
        $params[] = $shop_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        log_audit($pdo, 'ORDER_STATUS_UPDATED', "Order ID: $order_id, New Status: $new_status");
        $_SESSION['success_message'] = "Order #$order_id status updated to '$new_status'.";
    } else {
        $_SESSION['error_message'] = "Could not update order status. You may not have permission.";
    }
} catch (PDOException $e) {
    error_log("Order status update error: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred.";
}

// Redirect back to the determined URL
redirect($redirect_url);
?>