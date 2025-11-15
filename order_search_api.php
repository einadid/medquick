<?php
// FILE: order_search_api.php (Upgraded with Duplicate Return Check)
require_once 'src/session.php';
require_once 'config/database.php';
header('Content-Type: application/json');

if (!has_role(ROLE_SALESMAN) && !has_role(ROLE_ADMIN)) { http_response_code(403); exit(json_encode(['success' => false, 'message' => 'Access Denied.'])); }

$order_id = (int)($_GET['id'] ?? 0);
$shop_id = $_SESSION['shop_id'];

if($order_id <= 0) { http_response_code(400); exit(json_encode(['success' => false, 'message' => 'Invalid Order ID.'])); }

try {
    // --- 1. Check if this order has already been returned ---
    $return_check_stmt = $pdo->prepare("SELECT id FROM returns WHERE original_order_id = ?");
    $return_check_stmt->execute([$order_id]);
    if ($return_check_stmt->fetch()) {
        throw new Exception("This order (#$order_id) has already been processed for a return. Cannot process again.");
    }

    // --- 2. Find the original order (if not returned) ---
    $sql = "SELECT o.id, o.created_at, u.full_name as customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.id = ? AND o.order_source = 'pos'";
    $params = [$order_id];
    if (has_role(ROLE_SALESMAN)) { $sql .= " AND o.shop_id = ?"; $params[] = $shop_id; }

    $order_stmt = $pdo->prepare($sql);
    $order_stmt->execute($params);
    $order = $order_stmt->fetch();

    if(!$order) { throw new Exception('Order not found in your shop, or it is not a POS sale.'); }

    // --- 3. Fetch order items ---
    $items_stmt = $pdo->prepare("SELECT oi.id, m.name as medicine_name, oi.quantity, oi.price_per_unit FROM order_items oi JOIN medicines m ON oi.medicine_id = m.id WHERE oi.order_id = ?");
    $items_stmt->execute([$order_id]);
    $order['items'] = $items_stmt->fetchAll();
    
    if (empty($order['items'])) { throw new Exception('This order has no items to return.'); }
    
    echo json_encode(['success' => true, 'order' => $order]);

} catch(Exception $e) {
    http_response_code(400); // Bad Request or Not Found
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>