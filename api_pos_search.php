<?php
header('Content-Type: application/json');
require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

if (!has_role(ROLE_SALESMAN)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$shop_id = $_SESSION['shop_id'];
$response = [];

if (isset($_GET['q'])) {
    $query = trim($_GET['q']);
    
    if (strlen($query) >= 2) {
        try {
            $searchTerm = "%" . $query . "%";
            // This query is specific to the salesman's shop
            // It finds medicines that exist in their shop's inventory
            $sql = "
                SELECT m.id, m.name, m.manufacturer, ib.price, SUM(ib.quantity) as stock
                FROM medicines m
                JOIN inventory_batches ib ON m.id = ib.medicine_id
                WHERE ib.shop_id = ? 
                  AND ib.quantity > 0 
                  AND ib.expiry_date > CURDATE()
                  AND (m.name LIKE ? OR m.manufacturer LIKE ?)
                GROUP BY m.id, m.name, m.manufacturer, ib.price
                ORDER BY m.name
                LIMIT 10
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$shop_id, $searchTerm, $searchTerm]);
            $response = $stmt->fetchAll();

        } catch (PDOException $e) {
            // error_log("POS Search API error: " . $e->getMessage());
        }
    }
}

echo json_encode($response);