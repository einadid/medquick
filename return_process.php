<?php
// FILE: return_process.php (Final & Secure Version)
// PURPOSE: Handles the backend logic for processing a sales return, updating inventory, and logging the transaction.

require_once 'src/session.php';
require_once 'config/database.php';
header('Content-Type: application/json');

// --- 1. Security & Initial Validation ---
if (!is_logged_in() || (!has_role(ROLE_SALESMAN) && !has_role(ROLE_ADMIN))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid Method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_token($input['csrf_token'] ?? '');

$order_id = (int)($input['order_id'] ?? 0);
$return_items_from_client = $input['items'] ?? [];
$return_reason = trim($input['reason'] ?? '');
$shop_id = $_SESSION['shop_id'];
$salesman_id = $_SESSION['user_id'];

if ($order_id <= 0 || empty($return_items_from_client)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data. Order ID and items to return are required.']);
    exit;
}

$pdo->beginTransaction();
try {
    // --- 2. **CRITICAL:** Final Server-Side Duplicate Return Check ---
    $return_check_stmt = $pdo->prepare("SELECT id FROM returns WHERE original_order_id = ?");
    $return_check_stmt->execute([$order_id]);
    if ($return_check_stmt->fetch()) {
        throw new Exception("This order (#$order_id) has already been returned and cannot be processed again.");
    }
    
    // --- 3. Process Each Returned Item ---
    $total_refund_amount = 0;
    $items_to_log_in_return = [];

    foreach ($return_items_from_client as $item) {
        $order_item_id = (int)($item['id'] ?? 0);
        $return_qty = (int)($item['return_qty'] ?? 0);

        if ($return_qty <= 0) {
            continue; // Skip items with zero quantity
        }

        // Fetch the original order item to validate against.
        $item_stmt = $pdo->prepare("SELECT oi.*, o.shop_id, m.name as medicine_name FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN medicines m ON oi.medicine_id = m.id WHERE oi.id = ? AND o.id = ?");
        $item_stmt->execute([$order_item_id, $order_id]);
        $original_item = $item_stmt->fetch();

        // Security and Logic Validations
        if (!$original_item || (has_role(ROLE_SALESMAN) && $original_item['shop_id'] != $shop_id)) {
            throw new Exception("An item (#{$order_item_id}) does not belong to this order or your shop.");
        }
        if ($return_qty > $original_item['quantity']) {
            throw new Exception("Return quantity ({$return_qty}) for '{$original_item['medicine_name']}' cannot exceed original quantity ({$original_item['quantity']}).");
        }

        // --- 4. Update Inventory: Add stock back ---
        // Simplified logic: Add to the most recently added batch.
        $stock_update_stmt = $pdo->prepare("UPDATE inventory_batches SET quantity = quantity + ? WHERE medicine_id = ? AND shop_id = ? ORDER BY id DESC LIMIT 1");
        $stock_update_stmt->execute([$return_qty, $original_item['medicine_id'], $original_item['shop_id']]);
        
        if ($stock_update_stmt->rowCount() == 0) {
             throw new Exception("Inventory error: Could not find a batch to return stock for '{$original_item['medicine_name']}'. Please check inventory.");
        }
        
        $refund_for_item = $return_qty * $original_item['price_per_unit'];
        $total_refund_amount += $refund_for_item;
        
        $items_to_log_in_return[] = ['medicine_id' => $original_item['medicine_id'], 'quantity' => $return_qty, 'price_per_unit' => $original_item['price_per_unit'], 'cost_per_unit' => $original_item['cost_per_unit']];
    }
    
    if (empty($items_to_log_in_return)) {
        throw new Exception("No valid items were selected for return.");
    }
    
    // --- 5. Create Records in `returns` and `return_items` Tables ---
    $return_stmt = $pdo->prepare("INSERT INTO returns (original_order_id, salesman_id, shop_id, return_reason, returned_amount) VALUES (?, ?, ?, ?, ?)");
    $return_stmt->execute([$order_id, $salesman_id, $shop_id, $return_reason, $total_refund_amount]);
    $return_id = $pdo->lastInsertId();
    
    $return_item_stmt = $pdo->prepare("INSERT INTO return_items (return_id, medicine_id, quantity, price_per_unit, cost_per_unit) VALUES (?, ?, ?, ?, ?)");
    foreach ($items_to_log_in_return as $ret_item) {
        $return_item_stmt->execute([$return_id, $ret_item['medicine_id'], $ret_item['quantity'], $ret_item['price_per_unit'], $ret_item['cost_per_unit']]);
    }
    
    // --- 6. Update Original Order Status ---
    $pdo->prepare("UPDATE orders SET order_status = 'Returned' WHERE id = ?")->execute([$order_id]);
    
    // --- 7. Finalize ---
    $pdo->commit();
    log_audit($pdo, 'SALE_RETURN_PROCESSED', "Return ID: $return_id for Order ID: $order_id. Refund: $total_refund_amount");
    echo json_encode(['success' => true, 'message' => "Return #$return_id processed successfully. Total refund: ৳" . number_format($total_refund_amount, 2)]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>