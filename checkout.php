<?php
// Ensure this script is called by our application
require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

// Set the response content type to JSON, as this is an API endpoint
header('Content-Type: application/json');

// ---------------------------
// 1. SECURITY AND VALIDATION
// ---------------------------

// Security: Only logged-in customers can place an order.
if (!has_role(ROLE_CUSTOMER)) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Access Denied. Only customers can place orders.']);
    exit;
}

// Security: This endpoint should only be accessed via POST method.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get the raw POST data (which is a JSON string from the fetch request)
$input = json_decode(file_get_contents('php://input'), true);
$cart = $input['cart'] ?? null;

// Validation: Check if the cart is empty or not provided.
if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Cannot process checkout. Your cart is empty.']);
    exit;
}

// ---------------------------------------------------------------------
// 2. TRANSACTION AND CORE LOGIC (FEFO INVENTORY DEDUCTION)
// ---------------------------------------------------------------------

// Start a database transaction. This ensures that all database operations
// (updating stock, creating order, creating order items) either all succeed
// or all fail together, preventing data inconsistency.
$pdo->beginTransaction();

try {
    $total_order_amount = 0;
    $order_items_data = []; // An array to hold processed items before inserting into the DB
    $shop_id_for_order = null; // We need to determine which shop will fulfill the order

    // --- Server-side Validation and FEFO Deduction Loop ---
    foreach ($cart as $medicine_id => $item) {
        // Sanitize inputs received from the client
        $medicine_id = (int)$medicine_id;
        $quantity_requested = (int)$item['qty'];

        if ($quantity_requested <= 0) {
            // Skip invalid items or throw an error
            continue;
        }

        // --- FEFO LOGIC START ---
        // Fetch all available batches for this medicine from inventory,
        // ordered by the nearest expiry date first.
        $stmt = $pdo->prepare(
            "SELECT id, shop_id, quantity, price, expiry_date 
             FROM inventory_batches 
             WHERE medicine_id = ? AND quantity > 0 AND expiry_date > CURDATE()
             ORDER BY expiry_date ASC"
        );
        $stmt->execute([$medicine_id]);
        $available_batches = $stmt->fetchAll();

        // If no batches are found, the item is completely out of stock.
        if (empty($available_batches)) {
            throw new Exception("Sorry, '{$item['name']}' is out of stock and cannot be ordered.");
        }
        
        // Simplification: We'll assume the entire order is fulfilled by the first shop
        // that has stock of any item. A more complex system might split orders or find the best shop.
        if ($shop_id_for_order === null) {
            $shop_id_for_order = $available_batches[0]['shop_id'];
        }

        $quantity_fulfilled = 0;
        $cost_for_item = 0;
        
        // Loop through the available batches (which are already sorted by expiry date)
        foreach ($available_batches as $batch) {
            // If we have already fulfilled the requested quantity, stop processing batches.
            if ($quantity_fulfilled >= $quantity_requested) {
                break;
            }

            // How much more do we need to fulfill the request?
            $needed_from_this_batch = $quantity_requested - $quantity_fulfilled;
            
            // How much can we actually take from this batch? (The minimum of what we need vs. what's available)
            $take_from_this_batch = min($needed_from_this_batch, $batch['quantity']);

            // **CRITICAL: Update the inventory batch quantity in the database**
            $update_stmt = $pdo->prepare("UPDATE inventory_batches SET quantity = quantity - ? WHERE id = ?");
            $update_stmt->execute([$take_from_this_batch, $batch['id']]);

            // Update our counters for this item
            $quantity_fulfilled += $take_from_this_batch;
            $cost_for_item += $take_from_this_batch * $batch['price'];
        }
        // --- FEFO LOGIC END ---

        // After checking all batches, if we still couldn't fulfill the request, there isn't enough stock.
        if ($quantity_fulfilled < $quantity_requested) {
            throw new Exception("Not enough stock for '{$item['name']}'. Requested {$quantity_requested}, but only {$quantity_fulfilled} are available.");
        }

        // Add the cost of this item to the grand total for the order.
        $total_order_amount += $cost_for_item;
        
        // Store the processed item details to be inserted into the `order_items` table later.
        $order_items_data[] = [
            'medicine_id' => $medicine_id,
            'quantity' => $quantity_requested,
            'price_per_unit' => $cost_for_item / $quantity_requested // Calculate the average price if taken from multiple batches
        ];
    }
    
    // Safety check: If no shop could be determined, it means no items had any stock.
    if ($shop_id_for_order === null) {
        throw new Exception("Could not fulfill the order. All items in your cart may be out of stock.");
    }

    // ---------------------------
    // 3. CREATE ORDER RECORDS
    // ---------------------------

    // Create the main order record in the `orders` table.
    $order_stmt = $pdo->prepare(
        "INSERT INTO orders (customer_id, shop_id, total_amount, payment_method, order_status, order_source) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $order_stmt->execute([$_SESSION['user_id'], $shop_id_for_order, $total_order_amount, 'Cash on Delivery', 'Pending', 'web']);
    $order_id = $pdo->lastInsertId(); // Get the ID of the newly created order.

    // Now, create the records for each item in the order in the `order_items` table.
    $order_item_stmt = $pdo->prepare(
        "INSERT INTO order_items (order_id, medicine_id, quantity, price_per_unit) VALUES (?, ?, ?, ?)"
    );
    foreach ($order_items_data as $item) {
        $order_item_stmt->execute([$order_id, $item['medicine_id'], $item['quantity'], $item['price_per_unit']]);
    }

    // ---------------------------------
    // 4. COMMIT AND SEND SUCCESS RESPONSE
    // ---------------------------------

    // If we've reached this point without any errors, all database operations were successful.
    // We can now permanently save the changes.
    $pdo->commit();

    // Log this critical action to the audit trail
    log_audit($pdo, 'CUSTOMER_ORDER', "Order ID: {$order_id}, Total: " . number_format($total_order_amount, 2));

    // Send a success response back to the client's JavaScript.
    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully!', 
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // ------------------------------------
    // 5. ROLLBACK AND SEND ERROR RESPONSE
    // ------------------------------------
    
    // An error occurred at some point. Roll back all database changes made during this transaction.
    $pdo->rollBack();

    // Send a failure response back to the client with the specific error message.
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}