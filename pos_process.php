<?php
// FILE: pos_process.php (Final & Secure Version with Customer Assignment & Points)
require_once 'src/session.php';
require_once 'config/database.php';

header('Content-Type: application/json');

// --- 1. Security & Initial Validation ---
if (!has_role(ROLE_SALESMAN)) {
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

$bill_items_from_client = $input['items'] ?? null;
$discount_percent_from_client = (float)($input['discount'] ?? 0);
$vat_rate_from_client = (float)($input['vat_rate'] ?? 0);
$customer_id = (int)($input['customer_id'] ?? 1); // Default to 1 (Walk-in Customer)
$shop_id = $_SESSION['shop_id'];
$salesman_id = $_SESSION['user_id'];


if (empty($bill_items_from_client)) {
    echo json_encode(['success' => false, 'message' => 'The bill is empty.']);
    exit;
}

$pdo->beginTransaction();
try {
    // --- 2. Server-side Recalculation & FEFO Deduction ---
    $server_subtotal = 0;
    $order_items_to_insert = [];

    foreach ($bill_items_from_client as $item) {
        $medicine_id = (int)$item['id'];
        $quantity_requested = (int)$item['qty'];

        if ($quantity_requested <= 0) continue;

        // Fetch all available batches for this item from the DB
        $stmt = $pdo->prepare("SELECT id, quantity, price, purchase_price FROM inventory_batches WHERE medicine_id = ? AND shop_id = ? AND quantity > 0 AND expiry_date > CURDATE() ORDER BY expiry_date ASC");
        $stmt->execute([$medicine_id, $shop_id]);
        $batches = $stmt->fetchAll();
        
        if (empty($batches)) {
            $name_stmt = $pdo->prepare("SELECT name FROM medicines WHERE id = ?");
            $name_stmt->execute([$medicine_id]);
            $med_name = $name_stmt->fetchColumn();
            throw new Exception("Stock for '{$med_name}' just ran out.");
        }

        $quantity_fulfilled = 0;
        $sale_price_for_item = 0;
        $purchase_cost_for_item = 0;

        // FEFO Logic
        foreach ($batches as $batch) {
            if ($quantity_fulfilled >= $quantity_requested) break;
            $take_from_this_batch = min($quantity_requested - $quantity_fulfilled, $batch['quantity']);
            
            $update_stmt = $pdo->prepare("UPDATE inventory_batches SET quantity = quantity - ? WHERE id = ?");
            $update_stmt->execute([$take_from_this_batch, $batch['id']]);

            $quantity_fulfilled += $take_from_this_batch;
            $sale_price_for_item += $take_from_this_batch * $batch['price'];
            $purchase_cost_for_item += $take_from_this_batch * ($batch['purchase_price'] ?? $batch['price'] * 0.8);
        }

        if ($quantity_fulfilled < $quantity_requested) {
            throw new Exception("Not enough stock for an item. Only {$quantity_fulfilled} available.");
        }

        $server_subtotal += $sale_price_for_item;
        $order_items_to_insert[] = [
            'medicine_id' => $medicine_id,
            'quantity' => $quantity_requested,
            'price_per_unit' => $sale_price_for_item / $quantity_requested,
            'cost_per_unit' => $purchase_cost_for_item / $quantity_requested
        ];
    }

    if (empty($order_items_to_insert)) {
        throw new Exception("No valid items were processed.");
    }
    
    // --- 3. Final Calculation on Server-side ---
    $amount_after_discount = $server_subtotal - ($server_subtotal * ($discount_percent_from_client / 100));
    $vat_amount = $amount_after_discount * ($vat_rate_from_client / 100);
    $final_total_on_server = $amount_after_discount + $vat_amount;

    // --- 4. Create Order Record with Salesman and Customer ID ---
    $order_stmt = $pdo->prepare("INSERT INTO orders (customer_id, salesman_id, shop_id, total_amount, order_status, payment_method, order_source) VALUES (?, ?, ?, ?, 'Delivered', 'Cash', 'pos')");
    $order_stmt->execute([$customer_id, $salesman_id, $shop_id, $final_total_on_server]);
    $order_id = $pdo->lastInsertId();

    // --- 5. Create Order Items Record ---
    $order_item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, medicine_id, quantity, price_per_unit, cost_per_unit) VALUES (?, ?, ?, ?, ?)");
    foreach ($order_items_to_insert as $item_data) {
        $order_item_stmt->execute([$order_id, $item_data['medicine_id'], $item_data['quantity'], $item_data['price_per_unit'], $item_data['cost_per_unit']]);
    }
    
    // --- 6. Award Loyalty Points to Registered Customers ---
    // Rule: 100 points for every 1000 Taka spent.
    $points_to_earn = floor($final_total_on_server / 1000) * 100;
    // Don't award points to the default "Walk-in Customer" (ID 1)
    if ($points_to_earn > 0 && $customer_id !== 1) {
        $pdo->prepare("UPDATE users SET points_balance = points_balance + ? WHERE id = ?")->execute([$points_to_earn, $customer_id]);
        $pdo->prepare("UPDATE orders SET points_earned = ? WHERE id = ?")->execute([$points_to_earn, $order_id]);
    }
    
    // --- 7. Finalize Transaction ---
    $pdo->commit();
    log_audit($pdo, 'POS_SALE', "Order ID: {$order_id}, Total: " . number_format($final_total_on_server, 2));
    echo json_encode(['success' => true, 'message' => 'Sale completed successfully.', 'order_id' => $order_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>