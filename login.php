<?php
// At the top of login.php
// ... includes ...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // 1. Rate Limiting Check
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$ip_address]);
    if ($stmt->fetchColumn() > 5) {
        die('Too many login attempts. Please try again in 15 minutes.');
    }

    // 2. Fetch User
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 3. Verify Password
    if ($user && password_verify($password, $user['password_hash'])) {
        // Success: Clear attempts, set session
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip_address]);

        session_regenerate_id(true); // IMPORTANT
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        if ($user['shop_id']) {
            $_SESSION['shop_id'] = $user['shop_id'];
        }

        redirect('dashboard.php');
    } else {
        // Failure: Log attempt
        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)");
        $stmt->execute([$ip_address, $email]);
        $error = "Invalid email or password.";
    }
}
?>