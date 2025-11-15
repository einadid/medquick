<?php
// FILE: forgot_password.php
// PURPOSE: Page for users to request a password reset link.

require_once 'src/session.php';
require_once 'config/database.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate a secure, random token
                $token = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $token);
                
                // Set expiry time (e.g., 1 hour from now)
                $expiry = date('Y-m-d H:i:s', time() + 3600);

                // Store the hashed token and expiry in the database
                $update_stmt = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
                $update_stmt->execute([$token_hash, $expiry, $user['id']]);

                // --- SEND EMAIL ---
                $reset_link = base_url("reset_password.php?token=" . $token);
                
                $subject = "Password Reset Request for QuickMed";
                $body = "
                    Hello,\n\n
                    A password reset was requested for your account.\n
                    If you made this request, please click the link below to reset your password. The link is valid for 1 hour.\n\n"
                    . $reset_link . "\n\n"
                    . "If you did not request a password reset, please ignore this email.\n\n
                    Thanks,\nThe QuickMed Team";
                
                $headers = 'From: no-reply@' . parse_url(BASE_URL, PHP_URL_HOST);

                // Use PHP's mail() function
                if (mail($email, $subject, $body, $headers)) {
                    $message = 'A password reset link has been sent to your email address.';
                    $message_type = 'success';
                } else {
                    // This is a common issue on free hosting
                    $message = 'Could not send the password reset email. Please contact support. (Your link is: '. $reset_link .')';
                    $message_type = 'error';
                    error_log("Failed to send password reset email to: $email");
                }
            } else {
                // To prevent user enumeration, show the same success message even if the email doesn't exist.
                $message = 'If an account with that email exists, a password reset link has been sent.';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $message = 'An unexpected error occurred. Please try again later.';
            $message_type = 'error';
        }
    }
}

$pageTitle = "Forgot Password";
include 'templates/header.php';
?>
<div class="fade-in flex items-center justify-center min-h-[70vh] bg-slate-50">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md border">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-slate-800">Forgot Your Password?</h1>
            <p class="text-gray-600 mt-2">No problem. Enter your email below and we'll send you a link to reset it.</p>
        </div>

        <?php if ($message): ?>
            <div class="<?= $message_type === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700' ?> border-l-4 p-4" role="alert">
                <p><?= e($message); ?></p>
            </div>
        <?php endif; ?>

        <form class="space-y-6" action="forgot_password.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input id="email" name="email" type="email" required autocomplete="email" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500">
            </div>
            <div>
                <button type="submit" class="w-full btn-primary">Send Reset Link</button>
            </div>
            <p class="text-sm text-center text-gray-600">
                Remembered your password? <a href="login.php" class="font-medium text-teal-600 hover:underline">Log In</a>
            </p>
        </form>
    </div>
</div>
<?php include 'templates/footer.php'; ?>