<?php
// FILE: api_check_stock.php
// PURPOSE: AJAX endpoint to check the availability and price of specific medicines in a specific shop.

require_once 'src/session.php';
require_once 'config/database.php';
header('Content-Type: application/json');

// --- Security and Input Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'POST method is required.']);
    exit;
}

// Get data from JSON payload
$input = json_decode(file_get_contents('php://input'), true);
$shop_id = isset($input['shop_id']) ? (int)$input['shop_id'] : 0;
$medicine_ids = $input['ids'] ?? [];

// Validate the input
if ($shop_id <= 0 || empty($medicine_ids) || !is_array($medicine_ids)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'A valid Shop ID and a list of medicine IDs are required.']);
    exit;
}

// Sanitize the medicine IDs to prevent SQL injection
$sanitized_ids = array_filter(array_map('intval', $medicine_ids), fn($id) => $id > 0);
if (empty($sanitized_ids)) {
    // If no valid IDs remain after sanitization, return empty success response.
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    // Create placeholders for the 'IN' clause (e.g., ?,?,?)
    $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
    
    // This query fetches stock and price for each requested medicine ID
    // *specifically for the chosen shop*.
    // We use a LEFT JOIN to ensure all requested medicines are returned, even if they have no stock in the given shop.
    $sql = "
        SELECT 
            m.id, 
            MIN(ib.price) as price,
            SUM(ib.quantity) as total_stock
        FROM medicines m
        LEFT JOIN inventory_batches ib ON m.id = ib.medicine_id 
                                     AND ib.shop_id = ? 
                                     AND ib.quantity > 0 
                                     AND ib.expiry_date > CURDATE()
        WHERE m.id IN ($placeholders)
        GROUP BY m.id
    ";

    // Combine parameters for execute(): shop_id first, then all medicine IDs
    $params = array_merge([$shop_id], $sanitized_ids);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $responseData = [];
    foreach ($results as $row) {
        $responseData[$row['id']] = [
            'price' => $row['price'] ? (float)$row['price'] : null,
            'is_available' => ($row['total_stock'] ?? 0) > 0
        ];
    }
    
    // Ensure all requested IDs have a response, even if they are not available
    foreach($sanitized_ids as $id) {
        if (!isset($responseData[$id])) {
            $responseData[$id] = ['price' => null, 'is_available' => false];
        }
    }
    
    echo json_encode(['success' => true, 'data' => $responseData]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("API Check Stock Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred while checking stock.']);
}
?>