<?php
// FILE: reset_password.php
// PURPOSE: Page for users to set a new password using a token from email.

require_once 'src/session.php';
require_once 'config/database.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if (empty($token)) {
    $error = "Invalid or missing reset token.";
} else {
    $token_hash = hash('sha256', $token);

    // Find the user with this token hash that has not expired
    $stmt = $pdo->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
    $stmt->execute([$token_hash]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "This password reset link is invalid or has expired. Please request a new one.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    verify_csrf_token($_POST['csrf_token']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update user's password and clear the reset token
            $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
            $update_stmt->execute([$new_password_hash, $user['id']]);

            log_audit($pdo, 'PASSWORD_RESET_COMPLETED', "User ID: {$user['id']}");
            $success = true;

        } catch (PDOException $e) {
            error_log("Reset password error: " . $e->getMessage());
            $error = "An unexpected error occurred. Please try again.";
        }
    }
}

$pageTitle = "Reset Password";
include 'templates/header.php';
?>
<div class="fade-in flex items-center justify-center min-h-[70vh] bg-slate-50">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md border">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-slate-800">Reset Your Password</h1>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                <p><?= e($error); ?></p>
                <?php if (strpos($error, 'expired') !== false): ?>
                    <a href="forgot_password.php" class="font-bold underline">Request a new link</a>
                <?php endif; ?>
            </div>
        <?php elseif ($success): ?>
             <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 text-center" role="alert">
                <p class="font-bold">Password Reset Successfully!</p>
                <p>You can now log in with your new password.</p>
                <a href="login.php" class="mt-4 btn-primary">Go to Login</a>
            </div>
        <?php else: ?>
            <form class="space-y-6" action="reset_password.php?token=<?= e($token) ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" name="new_password" id="new_password" required minlength="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                </div>
                <div>
                    <button type="submit" class="w-full btn-primary">Reset Password</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php include 'templates/footer.php'; ?>