<?php
// FILE: api_get_user_details.php
// PURPOSE: AJAX endpoint to fetch detailed stats for a specific user.

require_once 'src/session.php';
require_once 'config/database.php';

header('Content-Type: application/json');

if (!has_role(ROLE_ADMIN)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid User ID.']);
    exit;
}

try {
    // First, get the user's basic info and role
    $user_stmt = $pdo->prepare("SELECT id, full_name, email, role, shop_id FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    $details = [];

    // Fetch details based on the user's role
    if ($user['role'] === 'customer') {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders, 
                COALESCE(SUM(total_amount), 0) as total_spent,
                MAX(created_at) as last_order_date
            FROM orders WHERE customer_id = ?
        ");
        $stmt->execute([$user_id]);
        $details = $stmt->fetch();
    } 
    elseif (in_array($user['role'], ['shop_admin', 'salesman'])) {
        if (!empty($user['shop_id'])) {
            $stmt = $pdo->prepare("
                SELECT 
                    (SELECT name FROM shops WHERE id = ?) as shop_name,
                    (SELECT COALESCE(SUM(quantity), 0) FROM inventory_batches WHERE shop_id = ?) as total_stock,
                    (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE shop_id = ? AND DATE(created_at) = CURDATE()) as today_sales
            ");
            $stmt->execute([$user['shop_id'], $user['shop_id'], $user['shop_id']]);
            $details = $stmt->fetch();
        }
    }
    // Admin role has no specific stats to show in this context

    echo json_encode(['success' => true, 'user' => $user, 'details' => $details]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("API Get User Details Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>