<?php
session_start();
require '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get medicine ID from URL
$medicine_id = $_GET['id'] ?? 0;

if ($medicine_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
        $stmt->execute([$medicine_id]);
        
        $_SESSION['success'] = "মেডিসিন ডিলিট করা হয়েছে!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "ডিলিট করতে সমস্যা: " . $e->getMessage();
    }
}

header('Location: inventory.php');
exit();
?>