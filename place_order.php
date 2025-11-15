<?php
// FILE: place_order.php (The Ultimate, Final, and Secure Version for Single-Shop Checkout)
require_once 'src/session.php';
require_once 'config/database.php'; // Ensure this path is correct
require_once 'src/utils.php';     // Assuming verify_csrf_token and log_audit are here

header('Content-Type: application/json');

// --- 1. Security & Initial Validation ---
if (!is_logged_in() || !has_role(ROLE_CUSTOMER)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied. Please log in as a customer.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid Method. Only POST requests are allowed.']);
    exit;
}

// NOTE: We now expect FormData, so we read from $_POST.
// Ensure verify_csrf_token function is defined in src/utils.php
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh the page.']);
            exit;
        }
    }
}
// Assuming log_audit is also in src/utils.php
if (!function_exists('log_audit')) {
    function log_audit($pdo, $event_type, $description) {
        try {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, event_type, description) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'] ?? null, $event_type, $description]);
        } catch (PDOException $e) {
            error_log("Failed to write audit log: " . $e->getMessage());
        }
    }
}


verify_csrf_token($_POST['csrf_token'] ?? '');

$user_id = $_SESSION['user_id'];
$shop_id = (int)($_POST['shop_id'] ?? 0);
$cart_from_client = json_decode($_POST['cart_data'] ?? '[]', true);

// Address & Phone Retrieval Logic
$delivery_full_name = ''; // Added full_name for delivery
$delivery_address = '';
$customer_phone = '';
$address_option = $_POST['address_option'] ?? 'new';

try {
    if ($address_option === 'new') {
        $delivery_full_name = trim($_POST['full_name'] ?? '');
        $customer_phone = trim($_POST['phone'] ?? '');
        $delivery_address = trim($_POST['address'] ?? '');
    } else {
        $address_id = (int)$address_option;
        if ($address_id > 0) {
            $addr_stmt = $pdo->prepare("SELECT full_name, phone, address_line FROM user_addresses WHERE id = ? AND user_id = ?");
            $addr_stmt->execute([$address_id, $user_id]);
            $saved_address = $addr_stmt->fetch();
            if ($saved_address) {
                $delivery_full_name = $saved_address['full_name'];
                $customer_phone = $saved_address['phone'];
                $delivery_address = $saved_address['address_line'];
            }
        }
    }
    
    // --- 2. Final Validation ---
    if (empty($cart_from_client)) throw new Exception('Your cart is empty. Please add items before placing an order.');
    if (empty($shop_id)) throw new Exception('No shop selected for fulfillment. Please select a pharmacy.');
    if (empty($delivery_full_name) || empty($delivery_address) || empty($customer_phone)) throw new Exception('Delivery recipient name, address, and phone number are required.');
    
    $pdo->beginTransaction();
    
    // --- 3. Process Each Item: Server-side validation, FEFO, and Calculation ---
    $total_order_amount_products = 0;
    $order_items_to_insert = [];

    foreach ($cart_from_client as $medicine_id_str => $item) {
        $medicine_id = (int)$medicine_id_str; // Ensure it's an integer
        $quantity_requested = (int)$item['qty'];

        if ($quantity_requested <= 0) continue;

        // Fetch available batches for the selected medicine in the chosen shop
        $stmt = $pdo->prepare("SELECT id, quantity, price, purchase_price FROM inventory_batches WHERE medicine_id = ? AND shop_id = ? AND quantity > 0 AND expiry_date > CURDATE() ORDER BY expiry_date ASC"); // FEFO: First Expired, First Out
        $stmt->execute([$medicine_id, $shop_id]);
        $batches = $stmt->fetchAll();
        
        if (empty($batches)) {
            // Rollback immediately if an essential item isn't in stock.
            // For a production system, you might filter these out earlier or allow partial orders.
            throw new Exception("Sorry, '{$item['name']}' is out of stock or expired in the selected shop.");
        }

        $quantity_fulfilled = 0;
        $sale_price_for_item = 0;
        $purchase_cost_for_item = 0;

        foreach ($batches as $batch) {
            if ($quantity_fulfilled >= $quantity_requested) break; // All requested quantity fulfilled
            
            $take_from_this_batch = min($quantity_requested - $quantity_fulfilled, $batch['quantity']);
            
            // Deduct from inventory
            $update_stmt = $pdo->prepare("UPDATE inventory_batches SET quantity = quantity - ? WHERE id = ?");
            $update_stmt->execute([$take_from_this_batch, $batch['id']]);

            // Check if the update actually happened (e.g., if another request didn't grab the stock)
            if ($update_stmt->rowCount() === 0) {
                 // This is a race condition. Re-query or handle gracefully. For simplicity, we throw.
                throw new Exception("Failed to deduct stock for '{$item['name']}'. Please try again.");
            }

            $quantity_fulfilled += $take_from_this_batch;
            $sale_price_for_item += $take_from_this_batch * $batch['price'];
            $purchase_cost_for_item += $take_from_this_batch * ($batch['purchase_price'] ?? $batch['price'] * 0.8); // Fallback if purchase_price is null
        }

        if ($quantity_fulfilled < $quantity_requested) {
            throw new Exception("Not enough stock for '{$item['name']}'. Only {$quantity_fulfilled} available out of {$quantity_requested} requested.");
        }

        $total_order_amount_products += $sale_price_for_item;
        $order_items_to_insert[] = [ 
            'medicine_id' => $medicine_id, 
            'quantity' => $quantity_requested, 
            'price_per_unit' => $sale_price_for_item / $quantity_requested, 
            'cost_per_unit' => $purchase_cost_for_item / $quantity_requested // Average cost per unit
        ];
    }
    
    if (empty($order_items_to_insert)) throw new Exception("No valid or available items to process in your cart from the selected shop.");
    
    // Add delivery fee
    $delivery_fee = ($total_order_amount_products > 0) ? 99 : 0;
    $total_order_amount_with_delivery = $total_order_amount_products + $delivery_fee;

    // --- 4. Handle Point Redemption ---
    $points_to_use = isset($_POST['points_to_use']) ? (int)$_POST['points_to_use'] : 0;
    $final_order_amount = $total_order_amount_with_delivery;
    $redeemed_points = 0;
    
    if ($points_to_use > 0) {
        $user_points_stmt = $pdo->prepare("SELECT points_balance FROM users WHERE id = ? FOR UPDATE"); // Lock row for update
        $user_points_stmt->execute([$user_id]);
        $current_points = (int)$user_points_stmt->fetchColumn();
        
        if ($points_to_use > $current_points) {
            throw new Exception("You are trying to use more points ($points_to_use) than available ($current_points).");
        }
        
        // Ensure redeemed points don't make the order amount negative
        $redeemable_points_value = min($points_to_use, floor($final_order_amount));
        if ($redeemable_points_value > 0) {
            $final_order_amount -= $redeemable_points_value;
            $pdo->prepare("UPDATE users SET points_balance = points_balance - ? WHERE id = ?")->execute([$redeemable_points_value, $user_id]);
            $redeemed_points = $redeemable_points_value;
        }
    }

    // --- 5. Create Order Record ---
    $order_stmt = $pdo->prepare("INSERT INTO orders (customer_id, shop_id, total_amount, order_status, delivery_address, customer_phone, delivery_recipient_name, points_used, delivery_fee) VALUES (?, ?, ?, 'Pending', ?, ?, ?, ?, ?)");
    $order_stmt->execute([
        $user_id, 
        $shop_id, 
        $final_order_amount, 
        $delivery_address, 
        $customer_phone, 
        $delivery_full_name, // New column
        $redeemed_points,
        $delivery_fee
    ]);
    $order_id = $pdo->lastInsertId();
    
    // --- 6. Create Order Items Records ---
    $order_item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, medicine_id, quantity, price_per_unit, cost_per_unit) VALUES (?, ?, ?, ?, ?)");
    foreach ($order_items_to_insert as $item_data) {
        $order_item_stmt->execute([$order_id, $item_data['medicine_id'], $item_data['quantity'], $item_data['price_per_unit'], $item_data['cost_per_unit']]);
    }
    
    // --- 7. Award Loyalty Points ---
    $points_to_earn = floor($final_order_amount / 100); // 1 point for every ৳100 spent (adjust as needed)
    if ($points_to_earn > 0) {
        $pdo->prepare("UPDATE users SET points_balance = points_balance + ? WHERE id = ?")->execute([$points_to_earn, $user_id]);
        $pdo->prepare("UPDATE orders SET points_earned = ? WHERE id = ?")->execute([$points_to_earn, $order_id]);
    }
    
    // --- 8. Finalize Transaction ---
    $pdo->commit();
    log_audit($pdo, 'CUSTOMER_ORDER_PLACED', "Order ID: $order_id, Customer: $user_id, Shop: $shop_id, Total: $final_order_amount");
    echo json_encode(['success' => true, 'order_id' => $order_id, 'message' => "Order #$order_id placed successfully!"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log("Order placement error for user $user_id: " . $e->getMessage()); // Log detailed error
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => "Order placement failed: " . $e->getMessage()]);
}
?>