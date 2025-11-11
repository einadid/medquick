<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'অননুমোদিত অ্যাক্সেস']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? 0;

try {
    // Check if order belongs to this customer and is pending
    $stmt = $pdo->prepare("
        SELECT id 
        FROM orders 
        WHERE id = ? AND customer_id = ? AND status = 'pending'
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('অর্ডারটি বাতিল করা সম্ভব নয়।');
    }
    
    // Update order status to cancelled
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'cancelled', 
            cancelled_date = NOW(),
            cancellation_reason = 'গ্রাহক কর্তৃক বাতিল'
        WHERE id = ?
    ");
    $stmt->execute([$order_id]);
    
    // Restore stock
    $stmt = $pdo->prepare("
        UPDATE medicines m
        JOIN order_items oi ON m.id = oi.medicine_id
        SET m.quantity = m.quantity + oi.quantity
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    
    // Send success response
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Send error response
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>