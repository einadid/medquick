<?php
// FILE: address_process.php
// PURPOSE: Handles adding, deleting, and setting default addresses for customers.

require_once 'src/session.php';
require_once 'config/database.php';

if (!is_logged_in() || !has_role(ROLE_CUSTOMER)) { redirect('login.php'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('addresses.php'); }

verify_csrf_token($_POST['csrf_token']);

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    $pdo->beginTransaction();

    if ($action === 'add') {
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $address_line = trim($_POST['address_line']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($full_name) || empty($phone) || empty($address_line)) { throw new Exception("All fields are required."); }

        if ($is_default) { // If setting new address as default, unset previous default
            $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }

        $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, full_name, phone, address_line, is_default) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $full_name, $phone, $address_line, $is_default]);
        $_SESSION['success_message'] = 'New address added successfully.';
    
    } elseif ($action === 'delete') {
        $address_id = (int)$_POST['address_id'];
        // Ensure user can only delete their own address
        $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$address_id, $user_id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = 'Address deleted successfully.';
        } else {
            throw new Exception("Could not delete address.");
        }

    } elseif ($action === 'set_default') {
        $address_id = (int)$_POST['address_id'];
        // Unset any existing default
        $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        // Set the new default, ensuring it belongs to the user
        $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$address_id, $user_id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = 'Default address has been updated.';
        } else {
            throw new Exception("Could not set default address.");
        }
    }
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = $e->getMessage();
}

redirect('addresses.php');
?>