<?php
// FILE: profile_process.php (Final & Secure Version)
// Handles all profile update actions: info, password, and picture management.

require_once 'src/session.php';
require_once 'config/database.php';

// Security: User must be logged in.
if (!is_logged_in()) {
    redirect('login.php');
}

// Security: Only process POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('profile.php');
}

// Security: Verify CSRF token.
verify_csrf_token($_POST['csrf_token']);

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    // Fetch user's current data before making changes, as it's needed for multiple actions.
    $user_stmt = $pdo->prepare("SELECT profile_image_path, password_hash FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $current_user = $user_stmt->fetch();

    if (!$current_user) {
        throw new Exception("Your user profile could not be found.");
    }

    // --- ACTION: Update Profile Information ---
    if ($action === 'update_info') {
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        
        if (empty($full_name)) {
            throw new Exception("Full Name cannot be empty.");
        }

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$full_name, $phone, $user_id]);
        
        // Update session name for immediate reflection in the UI.
        $_SESSION['user_name'] = $full_name;
        
        log_audit($pdo, 'PROFILE_INFO_UPDATED', "User ID: $user_id");
        $_SESSION['success_message'] = 'Your profile information has been updated successfully.';
    
    } 
    // --- ACTION: Change Password ---
    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception("All password fields are required.");
        }
        if (strlen($new_password) < 6) {
            throw new Exception('New password must be at least 6 characters long.');
        }
        if ($new_password !== $confirm_password) {
            throw new Exception('New password and confirmation password do not match.');
        }

        // Verify the current password before changing.
        if (password_verify($current_password, $current_user['password_hash'])) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $update_stmt->execute([$new_password_hash, $user_id]);
            log_audit($pdo, 'PASSWORD_CHANGED', "User ID: $user_id");
            $_SESSION['success_message'] = 'Your password has been changed successfully.';
        } else {
            throw new Exception('Your current password is incorrect.');
        }
    } 
    // --- ACTION: Update Profile Picture ---
    elseif ($action === 'update_picture') {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

            if (!in_array($file['type'], $allowed_types)) { throw new Exception("Invalid file type. Only JPG, PNG, or GIF are allowed."); }
            if ($file['size'] > 2000000) { throw new Exception("File is too large. Maximum size is 2MB."); }
            
            $upload_dir = 'uploads/avatars/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
            
            $filename = 'user_' . $user_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $destination = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Delete old picture if it exists to save space.
                if (!empty($current_user['profile_image_path']) && file_exists($current_user['profile_image_path'])) {
                    unlink($current_user['profile_image_path']);
                }
                // Update database with the new path.
                $stmt = $pdo->prepare("UPDATE users SET profile_image_path = ? WHERE id = ?");
                $stmt->execute([$destination, $user_id]);
                $_SESSION['user_image'] = $destination; // Update session for immediate display in navbar.
                $_SESSION['success_message'] = 'Profile picture updated successfully.';
            } else { throw new Exception("Failed to upload the file. Please check server permissions."); }
        } else { throw new Exception("No file was uploaded or an upload error occurred."); }
    }
    // --- ACTION: Remove Profile Picture ---
    elseif ($action === 'remove_picture') {
        if (!empty($current_user['profile_image_path']) && file_exists($current_user['profile_image_path'])) {
            unlink($current_user['profile_image_path']);
        }
        $stmt = $pdo->prepare("UPDATE users SET profile_image_path = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        unset($_SESSION['user_image']); // Remove from session.
        $_SESSION['success_message'] = 'Profile picture has been removed.';
    } 
    else {
        throw new Exception("Invalid action specified.");
    }
} catch (Exception $e) {
    // If any error occurs, store it in the session to display on the profile page.
    $_SESSION['error_message'] = $e->getMessage();
}

// Finally, redirect back to the profile page to show the result message.
redirect('profile.php');
?>