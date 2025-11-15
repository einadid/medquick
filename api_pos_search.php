<?php
// FILE: api_pos_search.php (Final Robust Version)
header('Content-Type: application/json');

// Enable error reporting to catch any issues during development
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'src/session.php';
    require_once 'config/database.php';

    if (!has_role(ROLE_SALESMAN)) {
        http_response_code(403);
        throw new Exception('Access Denied. Salesman role required.');
    }

    $shop_id = $_SESSION['shop_id'];
    $query = trim($_GET['q'] ?? '');
    $category = trim($_GET['category'] ?? '');

    $params = [$shop_id];
    $sql = "
        SELECT m.id, m.name, m.manufacturer, m.image_path, ib.price, SUM(ib.quantity) as stock
        FROM medicines m
        JOIN inventory_batches ib ON m.id = ib.medicine_id
        WHERE ib.shop_id = ? AND ib.quantity > 0 AND ib.expiry_date > CURDATE()
    ";

    if (!empty($query)) {
        $sql .= " AND (m.name LIKE ? OR m.manufacturer LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }
    
    if (!empty($category)) {
        $sql .= " AND m.category = ?";
        $params[] = $category;
    }

    $sql .= " GROUP BY m.id ORDER BY m.name ASC LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'medicines' => $results]);

} catch (Throwable $e) {
    http_response_code(500);
    error_log("API POS Search Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error while searching for products.',
        'error_detail' => $e->getMessage() // For debugging
    ]);
}
?>