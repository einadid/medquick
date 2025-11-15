<?php
// FILE: user_manage_process.php (Upgraded with AJAX Role Update)
require_once 'src/session.php';
require_once 'config/database.php';

header('Content-Type: application/json'); // This script now returns JSON

if (!has_role(ROLE_ADMIN)) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Access Denied.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success' => false, 'message' => 'Invalid Method.']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
verify_csrf_token($input['csrf_token'] ?? '');

$user_to_manage_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$action = $input['action'] ?? '';

if ($user_to_manage_id === $_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You cannot manage your own account.']);
    exit;
}

if ($action === 'update_role' && $user_to_manage_id > 0) {
    $new_role = $input['role'] ?? '';
    $shop_id = isset($input['shop_id']) ? (int)$input['shop_id'] : null;

    $allowed_roles = ['customer', 'salesman', 'shop_admin'];
    if (!in_array($new_role, $allowed_roles)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid role specified.']);
        exit;
    }
    
    // If role requires a shop, but no shop is provided, fail
    if (in_array($new_role, ['salesman', 'shop_admin']) && empty($shop_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A shop must be assigned for this role.']);
        exit;
    }
    // If role does not require a shop, ensure shop_id is NULL
    if ($new_role === 'customer') {
        $shop_id = null;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET role = ?, shop_id = ? WHERE id = ?");
        $stmt->execute([$new_role, $shop_id, $user_to_manage_id]);
        
        log_audit($pdo, 'USER_ROLE_CHANGED', "Target User: $user_to_manage_id, New Role: $new_role, Shop ID: " . ($shop_id ?? 'NULL'));
        
        // Fetch new shop name to return to UI
        $new_shop_name = 'N/A';
        if ($shop_id) {
            $shop_stmt = $pdo->prepare("SELECT name FROM shops WHERE id = ?");
            $shop_stmt->execute([$shop_id]);
            $new_shop_name = $shop_stmt->fetchColumn();
        }
        
        echo json_encode(['success' => true, 'message' => 'User role updated successfully.', 'new_shop_name' => $new_shop_name]);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("User role update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action or user ID.']);
}
?>