<?php
// FILE: api_place_order.php
require_once 'src/session.php';
require_once 'config/database.php';

header('Content-Type: application/json');

// Check for POST request, user authentication, and CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_logged_in() || !has_role(ROLE_CUSTOMER) || !isset($_SESSION['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access or invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// CSRF Token verification
if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$shipments = $input['shipments'] ?? [];
$delivery_address_data = $input['delivery_address'] ?? [];
$grand_total = $input['grand_total'] ?? 0;

if (empty($shipments) || empty($delivery_address_data)) {
    echo json_encode(['success' => false, 'message' => 'Missing order or address details.']);
    exit;
}

// Basic validation for delivery address
if (empty($delivery_address_data['name']) || empty($delivery_address_data['phone']) || empty($delivery_address_data['address'])) {
    echo json_encode(['success' => false, 'message' => 'Delivery address details are incomplete.']);
    exit;
}


try {
    $pdo->beginTransaction();

    // 1. Insert into orders table
    $order_stmt = $pdo->prepare("INSERT INTO orders (user_id, delivery_address, total_amount, status) VALUES (?, ?, ?, 'pending')");
    $order_stmt->execute([
        $user_id,
        json_encode($delivery_address_data), // Store address as JSON
        $grand_total
    ]);
    $order_id = $pdo->lastInsertId();

    // 2. Insert into order_items and update inventory for each shipment and item
    foreach ($shipments as $shipment) {
        $shop_id = $shipment['shop_id'];
        foreach ($shipment['items'] as $item) {
            $medicine_id = $item['id'];
            $quantity = $item['qty'];
            $price_per_unit = $item['price'];

            // Insert into order_items
            $order_item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, medicine_id, shop_id, quantity, price_per_unit) VALUES (?, ?, ?, ?, ?)");
            $order_item_stmt->execute([$order_id, $medicine_id, $shop_id, $quantity, $price_per_unit]);

            // Update inventory batches (simple decrement, a more robust system would pick specific batches)
            // This is a simplified inventory update. In a real system, you'd deduct from specific inventory batches
            // based on FIFO or specific batch IDs identified during cart analysis.
            $update_inventory_stmt = $pdo->prepare("
                UPDATE inventory_batches 
                SET quantity = quantity - ? 
                WHERE shop_id = ? AND medicine_id = ? AND quantity >= ? AND expiry_date > CURDATE()
                ORDER BY expiry_date ASC, id ASC -- Prioritize older batches
                LIMIT 1
            ");
            $update_inventory_stmt->execute([$quantity, $shop_id, $medicine_id, $quantity]);

            // Check if update was successful (i.e., if enough stock was available)
            if ($update_inventory_stmt->rowCount() === 0) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to update inventory for medicine ' . $item['name'] . '. Insufficient stock.']);
                exit;
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Order placed successfully!', 'order_id' => $order_id]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Order placement API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error during order placement. Please try again.']);
}