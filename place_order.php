<?php
// FILE: place_order.php (Final & Secure Version)
// PURPOSE: Finalizes the customer's order, validates stock, saves all data, and awards points.

require_once 'src/session.php';
require_once 'config/database.php';

header('Content-Type: application/json');

// --- 1. Security & Initial Validation ---
if (!is_logged_in() || !has_role(ROLE_CUSTOMER)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied. You must be logged in as a customer.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$user_id = $_SESSION['user_id'];
$shop_id = (int)($_POST['shop_id'] ?? 0);
$cart_from_client = json_decode($_POST['cart_data'] ?? '[]', true);
$address_option = $_POST['address_option'] ?? 'new';

$delivery_address = '';
$customer_phone = '';

try {
    // --- 2. Validate Inputs and Cart ---
    if (empty($cart_from_client)) { throw new Exception('Your shopping cart is empty.'); }
    if (empty($shop_id)) { throw new Exception('Please select a fulfilling pharmacy branch.'); }

    // --- 3. Determine and Validate Delivery Address ---
    if ($address_option === 'new') {
        $customer_phone = trim($_POST['phone']);
        $delivery_address = trim($_POST['address']);
        if (empty($customer_phone) || empty($delivery_address)) {
            throw new Exception("Please provide a valid phone number and delivery address for the new address.");
        }
    } else {
        $address_id = (int)$address_option;
        if ($address_id <= 0) { throw new Exception("Invalid saved address selected."); }
        
        $addr_stmt = $pdo->prepare("SELECT phone, address_line FROM user_addresses WHERE id = ? AND user_id = ?");
        $addr_stmt->execute([$address_id, $user_id]);
        $saved_address = $addr_stmt->fetch();
        
        if (!$saved_address) {
            throw new Exception("Selected address could not be found or does not belong to you.");
        }
        $customer_phone = $saved_address['phone'];
        $delivery_address = $saved_address['address_line'];
    }

    // --- 4. Start Database Transaction ---
    $pdo->beginTransaction();
    
    // --- 5. Process Each Item: Server-side validation, FEFO, and Calculation ---
    $total_order_amount = 0;
    $order_items_to_insert = [];

    foreach ($cart_from_client as $medicine_id => $item) {
        $medicine_id = (int)$medicine_id;
        $quantity_requested = (int)$item['qty'];

        if ($quantity_requested <= 0) continue;

        $stmt = $pdo->prepare("SELECT id, quantity, price, purchase_price FROM inventory_batches WHERE medicine_id = ? AND shop_id = ? AND quantity > 0 AND expiry_date > CURDATE() ORDER BY expiry_date ASC");
        $stmt->execute([$medicine_id, $shop_id]);
        $batches = $stmt->fetchAll();
        
        if (empty($batches)) { throw new Exception("Sorry, '{$item['name']}' is out of stock in the selected shop."); }

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

        if ($quantity_fulfilled < $quantity_requested) { throw new Exception("Not enough stock for '{$item['name']}'. Only {$quantity_fulfilled} available."); }

        $total_order_amount += $sale_price_for_item;
        $order_items_to_insert[] = [
            'medicine_id' => $medicine_id,
            'quantity' => $quantity_requested,
            'price_per_unit' => ($quantity_requested > 0) ? $sale_price_for_item / $quantity_requested : 0,
            'cost_per_unit' => ($quantity_requested > 0) ? $purchase_cost_for_item / $quantity_requested : 0
        ];
    }

    if (empty($order_items_to_insert)) {
        throw new Exception("No valid items were found in your cart to process.");
    }
    
    // --- 6. Create Main Order Record ---
    $order_stmt = $pdo->prepare("INSERT INTO orders (customer_id, shop_id, total_amount, order_status, delivery_address, customer_phone) VALUES (?, ?, ?, 'Pending', ?, ?)");
    $order_stmt->execute([$user_id, $shop_id, $total_order_amount, $delivery_address, $customer_phone]);
    $order_id = $pdo->lastInsertId();
    
    // --- 7. Create Order Items Records ---
    $order_item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, medicine_id, quantity, price_per_unit, cost_per_unit) VALUES (?, ?, ?, ?, ?)");
    foreach ($order_items_to_insert as $item_data) {
        $order_item_stmt->execute([$order_id, $item_data['medicine_id'], $item_data['quantity'], $item_data['price_per_unit'], $item_data['cost_per_unit']]);
    }
    
    // --- 8. Award Loyalty Points ---
    // Rule: Award 100 points for every 1000 Taka spent.
    $points_to_earn = floor($total_order_amount / 1000) * 100;
    if ($points_to_earn > 0) {
        $pdo->prepare("UPDATE users SET points_balance = points_balance + ? WHERE id = ?")->execute([$points_to_earn, $user_id]);
        $pdo->prepare("UPDATE orders SET points_earned = ? WHERE id = ?")->execute([$points_to_earn, $order_id]);
    }

    // --- 9. Finalize Transaction ---
    $pdo->commit();
    log_audit($pdo, 'CUSTOMER_ORDER_PLACED', "Order ID: $order_id, Shop ID: $shop_id, Total: $total_order_amount");
    echo json_encode(['success' => true, 'order_id' => $order_id, 'message' => "Order #$order_id placed successfully!"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>