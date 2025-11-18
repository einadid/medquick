<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$shopId = $_GET['shop_id'] ?? 0;
$medicineId = $_GET['medicine_id'] ?? 0;

$db = Database::getInstance();
$stock = $db->fetchOne("
    SELECT stock, batch_number, expiry_date
    FROM shop_medicines
    WHERE shop_id = ? AND medicine_id = ?
    ORDER BY expiry_date ASC
    LIMIT 1
", [$shopId, $medicineId]);

if ($stock) {
    echo json_encode($stock);
} else {
    echo json_encode(['stock' => 0, 'batch_number' => '', 'expiry_date' => '']);
}