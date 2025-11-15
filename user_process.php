<?php
// FILE: user_process.php (Final Central Processor for All User Actions)
// This script handles creating, updating, and changing the status of users by an Admin.

require_once 'src/session.php';
require_once 'config/database.php';

// --- 1. Security Checks ---
// Ensure only an Admin can access this script.
if (!has_role(ROLE_ADMIN)) {
    // If it's an AJAX request, send a JSON error. Otherwise, redirect.
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    } else {
        redirect('dashboard.php');
    }
    exit;
}

// Determine if the request is from AJAX (JSON) or a standard form post.
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    verify_csrf_token($input['csrf_token'] ?? '');
    $action = $input['action'] ?? '';
} else {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('users.php');
    }
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
}

// --- 2. Main Logic ---
try {
    $pdo->beginTransaction();

    if ($action === 'create') {
        // This action comes from a standard form post from user_add.php
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $shop_id = in_array($role, ['salesman', 'shop_admin']) ? (int)$_POST['shop_id'] : null;

        if (empty($full_name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password) || empty($role)) {
            throw new Exception("All fields (Full Name, Email, Password, Role) are required.");
        }
        if (in_array($role, ['salesman', 'shop_admin']) && empty($shop_id)) {
            throw new Exception("A shop must be assigned for Salesman or Shop Admin roles.");
        }
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) { throw new Exception("This email address is already registered."); }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, shop_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $password_hash, $role, $shop_id]);
        $new_user_id = $pdo->lastInsertId();
        
        log_audit($pdo, 'USER_CREATED_BY_ADMIN', "New User ID: $new_user_id, Email: $email, Role: $role");
        $_SESSION['success_message'] = 'New user has been created successfully.';
        $redirect_page = 'users.php';

    } elseif ($action === 'update') {
        // This action comes from a standard form post from user_edit.php
        $user_id = (int)$_POST['user_id'];
        if ($user_id === $_SESSION['user_id']) { throw new Exception("You cannot edit your own role or shop from this panel."); }

        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];
        $shop_id = in_array($role, ['salesman', 'shop_admin']) ? (int)$_POST['shop_id'] : null;

        if (empty($full_name) || empty($role)) { throw new Exception("Full Name and Role are required."); }
        if (in_array($role, ['salesman', 'shop_admin']) && empty($shop_id)) { throw new Exception('A shop must be assigned for this role.'); }
        if (!in_array($role, ['salesman', 'shop_admin'])) { $shop_id = null; }

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, role = ?, shop_id = ? WHERE id = ?");
        $stmt->execute([$full_name, $phone, $role, $shop_id, $user_id]);
        
        log_audit($pdo, 'USER_UPDATED_BY_ADMIN', "Target User ID: $user_id, New Role: $role");
        $_SESSION['success_message'] = 'User details have been updated successfully.';
        $redirect_page = 'users.php';

    } elseif ($action === 'activate' || $action === 'deactivate') {
        // This action can come from a standard form post from users.php
        $user_id = (int)$_POST['user_id'];
        if ($user_id === $_SESSION['user_id']) { throw new Exception("You cannot change your own activation status."); }
        
        $new_status = ($action === 'activate') ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);
        
        $log_action = ($action === 'activate') ? 'USER_ACTIVATED' : 'USER_DEACTIVATED';
        log_audit($pdo, $log_action, "Target User ID: $user_id");
        $_SESSION['success_message'] = "User status has been updated successfully.";
        $redirect_page = 'users.php';

    } else {
        throw new Exception("Invalid action specified.");
    }
    
    $pdo->commit();
    
    if ($is_ajax) {
        // AJAX requests expect a JSON response, no redirect.
        echo json_encode(['success' => true, 'message' => $_SESSION['success_message']]);
        unset($_SESSION['success_message']); // Clear message after sending
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    
    if ($is_ajax) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    } else {
        $_SESSION['error_message'] = $e->getMessage();
        // Determine where to redirect back on error
        $redirect_page = 'users.php';
        if ($action === 'create') { $redirect_page = 'user_add.php'; }
        if ($action === 'update' && isset($_POST['user_id'])) { $redirect_page = 'user_edit.php?id='.(int)$_POST['user_id']; }
    }
}

// Redirect for standard form submissions
if (!$is_ajax) {
    redirect($redirect_page);
}
?>