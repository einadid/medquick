<?php
require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

header('Content-Type: application/json');

if (!has_role(ROLE_SALESMAN)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$bill_items = $input['items'] ?? null;
$discount_percent = (float)($input['discount'] ?? 0);
$shop_id = $_SESSION['shop_id'];
$walk_in_customer_id = 1; // যে ID টি আপনি সেট করেছিলেন

if (empty($bill_items)) {
    echo json_encode(['success' => false, 'message' => 'The bill is empty.']);
    exit;
}

$pdo->beginTransaction();

try {
    $subtotal = 0;
    $order_items_data = [];

    // --- 1. Server-side Validation and FEFO Deduction ---
    foreach ($bill_items as $medicine_id => $item) {
        $medicine_id = (int)$medicine_id;
        $quantity_requested = (int)$item['qty'];

        // FEFO for the specific shop
        $stmt = $pdo->prepare(
            "SELECT id, quantity, price FROM inventory_batches 
             WHERE medicine_id = ? AND shop_id = ? AND quantity > 0 AND expiry_date > CURDATE()
             ORDER BY expiry_date ASC"
        );
        $stmt->execute([$medicine_id, $shop_id]);
        $batches = $stmt->fetchAll();

        if (empty($batches)) throw new Exception("Stock for '{$item['name']}' just ran out.");

        $quantity_fulfilled = 0;
        $cost_for_item = 0;
        
        foreach ($batches as $batch) {
            if ($quantity_fulfilled >= $quantity_requested) break;
            $take_from_this_batch = min($quantity_requested - $quantity_fulfilled, $batch['quantity']);

            $update_stmt = $pdo->prepare("UPDATE inventory_batches SET quantity = quantity - ? WHERE id = ?");
            $update_stmt->execute([$take_from_this_batch, $batch['id']]);

            $quantity_fulfilled += $take_from_this_batch;
            $cost_for_item += $take_from_this_batch * $batch['price'];
        }

        if ($quantity_fulfilled < $quantity_requested) {
            throw new Exception("Not enough stock for '{$item['name']}'. Only {$quantity_fulfilled} available.");
        }

        $subtotal += $cost_for_item;
        $order_items_data[] = [
            'medicine_id' => $medicine_id,
            'quantity' => $quantity_requested,
            'price_per_unit' => $cost_for_item / $quantity_requested
        ];
    }
    
    // --- 2. Calculate Final Total and Create Order ---
    $discount_amount = $subtotal * ($discount_percent / 100);
    $final_total = $subtotal - $discount_amount;

    $order_stmt = $pdo->prepare(
        "INSERT INTO orders (customer_id, shop_id, total_amount, order_status, payment_method, order_source) VALUES (?, ?, ?, ?, ?, ?)"
    );
    // POS sales are immediately 'Delivered'
    $order_stmt->execute([$walk_in_customer_id, $shop_id, $final_total, 'Delivered', 'Cash', 'pos']);
    $order_id = $pdo->lastInsertId();

    // --- 3. Create Order Items ---
    $order_item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, medicine_id, quantity, price_per_unit) VALUES (?, ?, ?, ?)");
    foreach ($order_items_data as $item) {
        $order_item_stmt->execute([$order_id, $item['medicine_id'], $item['quantity'], $item['price_per_unit']]);
    }

    $pdo->commit();
    log_audit($pdo, 'POS_SALE', "Order ID: {$order_id}, Total: {$final_total}");
    echo json_encode(['success' => true, 'message' => 'Sale completed.', 'order_id' => $order_id, 'total' => $final_total]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}