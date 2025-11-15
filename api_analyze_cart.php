<?php
// FILE: api_analyze_cart.php
require_once 'src/session.php';
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_logged_in() || !has_role(ROLE_CUSTOMER)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access or invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cart = $input['cart'] ?? [];

if (empty($cart)) {
    echo json_encode(['success' => true, 'shipments' => [], 'unavailable_items' => [], 'message' => 'Cart is empty.']);
    exit;
}

$medicine_ids = array_keys($cart);
$sanitized_ids = array_filter(array_map('intval', $medicine_ids), fn($id) => $id > 0);

if (empty($sanitized_ids)) {
    echo json_encode(['success' => true, 'shipments' => [], 'unavailable_items' => [], 'message' => 'No valid items in cart.']);
    exit;
}

try {
    $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
    // Select the lowest priced available batch for each medicine, considering quantity and expiry.
    // This query assumes you want to group by medicine_id to find the best option across all shops,
    // then reconstruct shipments based on those best options.
    // For a more robust solution, you might need to consider total quantity available per shop per item.
    $sql = "
        SELECT ib.shop_id, s.name as shop_name, ib.medicine_id, m.name as medicine_name, m.image_path, m.description, MIN(ib.price) as price, SUM(ib.quantity) as available_quantity
        FROM inventory_batches ib 
        JOIN medicines m ON ib.medicine_id = m.id 
        JOIN shops s ON ib.shop_id = s.id 
        WHERE ib.medicine_id IN ($placeholders) 
          AND ib.quantity > 0 
          AND ib.expiry_date > CURDATE()
        GROUP BY ib.shop_id, ib.medicine_id
        ORDER BY price ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($sanitized_ids);
    $all_available_batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $best_options_per_medicine = [];
    foreach ($all_available_batches as $batch) {
        $med_id = $batch['medicine_id'];
        $requested_qty = $cart[$med_id]['qty'];

        // If we need to fulfill from multiple shops for one item, this logic gets complex.
        // For simplicity, we'll try to find one shop that can supply the quantity at the best price.
        // A real-world scenario might involve splitting an item across shops.
        if (!isset($best_options_per_medicine[$med_id]) || $batch['price'] < $best_options_per_medicine[$med_id]['price']) {
            if ($batch['available_quantity'] >= $requested_qty) {
                 $best_options_per_medicine[$med_id] = $batch;
            }
        }
    }
    
    $shipments = [];
    $found_medicine_ids_in_shipments = [];

    foreach ($best_options_per_medicine as $med_id => $stock) {
        $shop_id = $stock['shop_id'];
        $requested_qty = $cart[$med_id]['qty'];

        if ($stock['available_quantity'] < $requested_qty) {
            // This item cannot be fully fulfilled by the current best option
            // This logic needs to be robust if partial fulfillment is allowed.
            continue; 
        }

        if (!isset($shipments[$shop_id])) {
            $shipments[$shop_id] = [
                'shop_id' => $shop_id,
                'shop_name' => $stock['shop_name'],
                'items' => [],
                'subtotal' => 0
            ];
        }

        $item_price = (float)$stock['price'];
        $item = [
            'id' => $med_id,
            'name' => $stock['medicine_name'],
            'image' => $stock['image_path'] ?? 'assets/images/default_med.png',
            'qty' => $requested_qty,
            'price' => $item_price
        ];
        $shipments[$shop_id]['items'][] = $item;
        $shipments[$shop_id]['subtotal'] += ($item['qty'] * $item['price']);
        $found_medicine_ids_in_shipments[] = $med_id;
    }

    $unavailable_items = [];
    foreach ($cart as $id => $item) {
        if (!in_array($id, $found_medicine_ids_in_shipments)) {
            $unavailable_items[] = ['id' => $id, 'name' => $item['name'] ?? 'Unknown Medicine'];
        }
    }

    echo json_encode([
        'success' => true,
        'shipments' => array_values($shipments), // Convert to array for consistent JS parsing
        'unavailable_items' => $unavailable_items
    ]);

} catch (PDOException $e) {
    error_log("Cart analysis API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error during cart analysis.']);
}