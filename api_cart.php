<?php
// FILE: api_cart.php (Most Robust Version for Debugging)
header('Content-Type: application/json');

// Immediately enable error reporting to catch any include/DB issues.
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'config/database.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $medicine_ids = $input['ids'] ?? [];

    if (empty($medicine_ids) || !is_array($medicine_ids)) {
        echo json_encode(['success' => true, 'data' => []]); // Return success with empty data
        exit;
    }

    $sanitized_ids = array_filter(array_map('intval', $medicine_ids), fn($id) => $id > 0);
    if (empty($sanitized_ids)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
    
    $sql = "
        SELECT 
            m.id, m.name, m.image_path,
            (SELECT MIN(price) FROM inventory_batches WHERE medicine_id = m.id AND quantity > 0 AND expiry_date > CURDATE()) as price
        FROM medicines m
        WHERE m.id IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($sanitized_ids);
    $results = $stmt->fetchAll();
    
    $response_data = [];
    foreach ($results as $row) {
        $response_data[$row['id']] = [
            'name' => $row['name'],
            'image' => $row['image_path'] ?? 'assets/images/default_med.png',
            'price' => $row['price'] ? (float)$row['price'] : null,
        ];
    }

    echo json_encode(['success' => true, 'data' => $response_data]);

} catch (Throwable $e) {
    // Catch any error (PDOException or other) and return it as JSON
    http_response_code(500);
    error_log("API_CART_ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false, 
        'message' => 'A server error occurred.',
        'error_detail' => $e->getMessage() // For debugging
    ]);
}
?>