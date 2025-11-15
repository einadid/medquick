<?php
// FILE: address_process.php (Final & Secure Version)
// PURPOSE: Handles adding, deleting, and setting default addresses for customers.

require_once 'src/session.php';
require_once 'config/database.php';

// Security: User must be logged in and have the customer role.
if (!is_logged_in() || !has_role(ROLE_CUSTOMER)) {
    redirect('login.php');
}

// Security: Only process POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('addresses.php');
}

// Security: Verify CSRF token.
verify_csrf_token($_POST['csrf_token']);

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    // Start a transaction for actions that involve multiple steps.
    $pdo->beginTransaction();

    if ($action === 'add') {
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $address_line = trim($_POST['address_line']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        // Basic validation
        if (empty($full_name) || empty($phone) || empty($address_line)) {
            throw new Exception("All address fields are required.");
        }

        // If setting new address as default, unset any previous default for this user.
        if ($is_default) {
            $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }

        $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, full_name, phone, address_line, is_default) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $full_name, $phone, $address_line, $is_default]);
        
        $_SESSION['success_message'] = 'New address has been added successfully.';
    
    } elseif ($action === 'delete') {
        $address_id = (int)$_POST['address_id'];
        
        // Ensure user can only delete their own address.
        $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$address_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = 'Address deleted successfully.';
        } else {
            // This could happen if a user tries to delete an address that isn't theirs.
            throw new Exception("Could not delete address. It may not exist or you don't have permission.");
        }

    } elseif ($action === 'set_default') {
        $address_id = (int)$_POST['address_id'];

        // Step 1: Unset any existing default address for this user.
        $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        
        // Step 2: Set the new default address, ensuring it belongs to the user.
        $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$address_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = 'Default address has been updated successfully.';
        } else {
            throw new Exception("Could not set the default address.");
        }
    } else {
        throw new Exception("Invalid action specified.");
    }
    
    // If everything was successful, commit the changes to the database.
    $pdo->commit();

} catch (Exception $e) {
    // If any error occurred, roll back all database changes.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Store the error message in the session to display it on the next page.
    $_SESSION['error_message'] = $e->getMessage();
}

// Redirect back to the addresses page to show the result.
redirect('addresses.php');
?>