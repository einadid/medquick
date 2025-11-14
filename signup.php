<?php
// At the top of signup.php
require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);

    $fullName = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $verificationCode = $_POST['verification_code'] ?? '';

    // Basic validation...

    $role = ROLE_CUSTOMER; // Default role
    if (!empty($verificationCode)) {
        switch ($verificationCode) {
            case VERIFICATION_CODE_ADMIN:
                $role = ROLE_ADMIN;
                break;
            case VERIFICATION_CODE_SHOP_ADMIN:
                $role = ROLE_SHOP_ADMIN;
                break;
            case VERIFICATION_CODE_SALESMAN:
                $role = ROLE_SALESMAN;
                break;
        }
    }
    
    // For shop_admin/salesman, you'd also need a shop_id selection on the form
    $shopId = isset($_POST['shop_id']) && in_array($role, [ROLE_SHOP_ADMIN, ROLE_SALESMAN]) ? (int)$_POST['shop_id'] : null;

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Check if email exists...
    
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, shop_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$fullName, $email, $passwordHash, $role, $shopId]);

    // Log the user in and redirect
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['user_name'] = $fullName;
    $_SESSION['role'] = $role;
    session_regenerate_id(true); // Prevent session fixation

    redirect('dashboard.php');
}
?>
<!-- HTML form with a verification_code field (optional) and CSRF token -->
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">