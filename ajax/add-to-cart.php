<?php
session_start();
require_once '../includes/functions.php';
require_once '../classes/Cart.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'CSRF validation failed']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first', 'redirect' => SITE_URL . '/auth/login.php']);
    exit;
}

$shopMedicineId = (int)($_POST['shop_medicine_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($shopMedicineId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid medicine ID']);
    exit;
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

// Check stock availability
$db = Database::getInstance();
$stockCheck = $db->fetchOne("
    SELECT sm.stock, m.name 
    FROM shop_medicines sm 
    JOIN medicines m ON sm.medicine_id = m.id
    WHERE sm.id = ?
", [$shopMedicineId]);

if (!$stockCheck) {
    echo json_encode(['success' => false, 'message' => 'Medicine not found']);
    exit;
}

if ($stockCheck['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock. Only ' . $stockCheck['stock'] . ' available']);
    exit;
}

try {
    $cart = new Cart();
    $result = $cart->addItem($_SESSION['user_id'], $shopMedicineId, $quantity);
    
    if ($result) {
        // Get cart count
        $cartCount = $db->fetchOne("
            SELECT COUNT(*) as count FROM cart WHERE user_id = ?
        ", [$_SESSION['user_id']])['count'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Added to cart successfully',
            'cart_count' => $cartCount
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
    }
} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}