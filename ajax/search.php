<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$db = Database::getInstance();
$results = $db->fetchAll("
    SELECT m.id, m.name, m.generic_name, c.name as category_name,
           MIN(sm.selling_price) as min_price
    FROM medicines m
    JOIN categories c ON m.category_id = c.id
    LEFT JOIN shop_medicines sm ON m.id = sm.medicine_id
    WHERE m.status = 'active' 
    AND (m.name LIKE ? OR m.generic_name LIKE ?)
    GROUP BY m.id
    LIMIT 10
", ["%$query%", "%$query%"]);

echo json_encode($results);