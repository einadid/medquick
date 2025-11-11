<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    echo json_encode(['success' => false, 'error' => 'অননুমোদিত অ্যাক্সেস']);
    exit();
}

// Get address ID
$address_id = $_GET['id'] ?? 0;

// Fetch address details
try {
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? AND customer_id = ?");
    $stmt->execute([$address_id, $_SESSION['user_id']]);
    $address = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($address) {
        echo json_encode(['success' => true, 'address' => $address]);
    } else {
        echo json_encode(['success' => false, 'error' => 'ঠিকানা পাওয়া যায়নি']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'ডাটাবেস ত্রুটি: ' . $e->getMessage()]);
}
?>