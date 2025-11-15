<?php
// FILE: place_split_order.php (Final & Complete Version with Item Insertion)
require_once 'src/session.php';
require_once 'config/database.php';
header('Content-Type: application/json');

// --- 1. Security & Initial Validation ---
if (!is_logged_in() || !has_role(ROLE_CUSTOMER)) { http_response_code(403); exit(json_encode(['success' => false, 'message' => 'Access Denied.'])); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(json_encode(['success' => false, 'message' => 'Invalid Method.'])); }

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_token($input['csrf_token'] ?? '');

$shipments = $input['shipments'] ?? [];
$address_info = $input['address_info'] ?? [];
$delivery_fee = (float)($input['delivery_fee'] ?? 99);

if (empty($shipments) || empty($address_info) || empty($address_info['address']) || empty($address_info['phone'])) {
    http_response_code(400); exit(json_encode(['success' => false, 'message' => 'Incomplete order data.']));
}

$pdo->beginTransaction();
try {
    $parent_order_id = time() . '-' . $_SESSION['user_id'];
    $created_order_ids = [];
    $is_first_shipment = true;

    foreach ($shipments as $shipment) {
        $shop_id = (int)$shipment['shop_id'];
        $server_subtotal = 0;
        $order_items_to_insert = [];

        // --- 2. For each shipment, process its items ---
        foreach ($shipment['items'] as $item) {
            $medicine_id = (int)$item['id'];
            $quantity_requested = (int)$item['qty'];

            // Server-side validation of stock and price (FEFO)
            $stmt = $pdo->prepare("SELECT id, quantity, price, purchase_price FROM inventory_batches WHERE medicine_id = ? AND shop_id = ? AND quantity > 0 AND expiry_date > CURDATE() ORDER BY expiry_date ASC");
            $stmt->execute([$medicine_id, $shop_id]);
            $batches = $stmt->fetchAll();
            
            if (empty($batches)) { throw new Exception("Item '{$item['name']}' is no longer available in this shop."); }

            $quantity_fulfilled = 0;
            $sale_price_for_item = 0;
            $purchase_cost_for_item = 0;

            foreach ($batches as $batch) {
                if ($quantity_fulfilled >= $quantity_requested) break;
                $take_from_this_batch = min($quantity_requested - $quantity_fulfilled, $batch['quantity']);
                
                $update_stmt = $pdo->prepare("UPDATE inventory_batches SET quantity = quantity - ? WHERE id = ?");
                $update_stmt->execute([$take_from_this_batch, $batch['id']]);

                $quantity_fulfilled += $take_from_this_batch;
                $sale_price_for_item += $take_from_this_batch * $batch['price'];
                $purchase_cost_for_item += $take_from_this_batch * ($batch['purchase_price'] ?? $batch['price'] * 0.8);
            }

            if ($quantity_fulfilled < $quantity_requested) { throw new Exception("Not enough stock for '{$item['name']}'."); }

            $server_subtotal += $sale_price_for_item;
            $order_items_to_insert[] = [
                'medicine_id' => $medicine_id,
                'quantity' => $quantity_requested,
                'price_per_unit' => $sale_price_for_item / $quantity_requested,
                'cost_per_unit' => $purchase_cost_for_item / $quantity_requested
            ];
        }

        // --- 3. Create the order for this shipment ---
        $total_amount_for_this_order = $server_subtotal;
        $delivery_note = null;
        if ($is_first_shipment) {
            $total_amount_for_this_order += $delivery_fee;
            $delivery_note = "Delivery Fee: $delivery_fee";
            $is_first_shipment = false;
        }
        
        $order_stmt = $pdo->prepare("INSERT INTO orders (parent_order_id, customer_id, shop_id, total_amount, delivery_address, customer_phone, delivery_notes, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $order_stmt->execute([$parent_order_id, $_SESSION['user_id'], $shop_id, $total_amount_for_this_order, $address_info['address'], $address_info['phone'], $delivery_note]);
        $order_id = $pdo->lastInsertId();
        $created_order_ids[] = $order_id;
        
        // --- 4. **CRITICAL STEP:** Insert items for THIS order ---
        $item_insert_stmt = $pdo->prepare("INSERT INTO order_items (order_id, medicine_id, quantity, price_per_unit, cost_per_unit) VALUES (?, ?, ?, ?, ?)");
        foreach($order_items_to_insert as $item_data) {
            $item_insert_stmt->execute([
                $order_id,
                $item_data['medicine_id'],
                $item_data['quantity'],
                $item_data['price_per_unit'],
                $item_data['cost_per_unit']
            ]);
        }
    }
    
    $pdo->commit();
    log_audit($pdo, 'SPLIT_ORDER_PLACED', "Parent ID: $parent_order_id, Child IDs: " . implode(',', $created_order_ids));
    echo json_encode(['success' => true, 'message' => 'Your orders have been placed successfully!', 'order_ids' => $created_order_ids]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>