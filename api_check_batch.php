<?php
// FILE: api_check_batch.php (Final & Secure Version)
// PURPOSE: AJAX endpoint to check if a batch number already exists for a specific medicine in the current shop.

require_once 'src/session.php';
require_once 'config/database.php';

header('Content-Type: application/json');

// Security: Only Shop Admins can use this endpoint.
if (!is_logged_in() || !has_role(ROLE_SHOP_ADMIN)) {
    http_response_code(403);
    echo json_encode(['exists' => false, 'error' => 'Access Denied.']);
    exit;
}

// Get Shop ID from session to ensure security.
$shop_id = $_SESSION['shop_id'];
$medicine_id = isset($_GET['medicine_id']) ? (int)$_GET['medicine_id'] : 0;
$batch_number = trim($_GET['batch_number'] ?? '');

if ($medicine_id <= 0 || empty($batch_number)) {
    // Not enough info to check, so we say it doesn't exist to avoid blocking the user.
    echo json_encode(['exists' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT id FROM inventory_batches WHERE medicine_id = ? AND shop_id = ? AND batch_number = ?"
    );
    $stmt->execute([$medicine_id, $shop_id, $batch_number]);
    
    $exists = $stmt->fetch() !== false;
    
    echo json_encode(['exists' => $exists]);

} catch (PDOException $e) {
    error_log("API Check Batch Error: " . $e->getMessage());
    // In case of a DB error, we don't block the user, server-side validation will catch it.
    echo json_encode(['exists' => false, 'error' => 'Database query failed.']);
}
?>