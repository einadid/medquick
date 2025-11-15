<?php
// FILE: api_cart.php (Final Robust Version)
header('Content-Type: application/json');
try {
    require_once 'src/session.php';
    require_once 'config/database.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method Not Allowed', 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $medicineIds = $input['ids'] ?? [];

    if (empty($medicineIds) || !is_array($medicineIds)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $sanitized_ids = array_filter(array_map('intval', $medicineIds), fn($id) => $id > 0);
    if (empty($sanitized_ids)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($sanitized_ids), '?'));
    
    $sql = "
        SELECT m.id, m.name, m.image_path,
               (SELECT MIN(price) FROM inventory_batches WHERE medicine_id = m.id AND quantity > 0 AND expiry_date > CURDATE()) as price
        FROM medicines m WHERE m.id IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($sanitized_ids);
    $results = $stmt->fetchAll();
    
    $responseData = [];
    foreach ($results as $row) {
        $responseData[$row['id']] = [
            'name' => $row['name'],
            'image' => $row['image_path'] ?? 'assets/images/default_med.png',
            'price' => $row['price'] ? (float)$row['price'] : null
        ];
    }
    echo json_encode(['success' => true, 'data' => $responseData]);
} catch (Throwable $e) {
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 500);
    error_log("API_CART_ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error while fetching cart data.']);
}
?>