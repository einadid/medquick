<?php
// FILE: login.php (Final Professional Version)
// PURPOSE: Handles user login, authentication, rate-limiting, and sets up a complete user session.

require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

// If the user is already logged in, redirect them straight to their dashboard.
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Initialize variables to hold potential errors or pre-fill form data.
$error = null;
$email = '';

// Process the form only if it was submitted via POST method.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. SECURITY: Verify the CSRF token.
    verify_csrf_token($_POST['csrf_token']);

    // 2. FORM DATA: Get and sanitize inputs.
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    try {
        // --- SECURITY: Rate Limiting ---
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
        $stmt->execute([$ip_address]);
        if ($stmt->fetchColumn() > 5) {
            die('Too many login attempts. Please try again in 15 minutes.');
        }

        // --- USER AUTHENTICATION ---
        // Fetch all necessary user data at once.
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify the submitted password against the stored hash.
        if ($user && password_verify($password, $user['password_hash'])) {
            // --- LOGIN SUCCESS ---
            
            // 1. Clear any previous failed login attempt records for this IP.
            $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip_address]);

            // 2. Regenerate the session ID for security (prevents session fixation).
            session_regenerate_id(true);

            // 3. **CRITICAL:** Store all necessary user information in the session.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email']; // For Membership ID
            $_SESSION['role'] = $user['role'];
            
            // Only set shop_id if it's relevant for the role.
            if (!empty($user['shop_id'])) {
                $_SESSION['shop_id'] = $user['shop_id'];
            }
            // Store profile image path for the navbar.
            if (!empty($user['profile_image_path'])) {
                $_SESSION['user_image'] = $user['profile_image_path'];
            }
            
            // 4. AUDIT LOGGING: Record this successful login event.
            log_audit($pdo, 'USER_LOGIN', "User ID: {$user['id']}");

            // 5. Redirect the user to their dashboard.
            redirect('dashboard.php');

        } else {
            // --- LOGIN FAILURE ---
            $pdo->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)")->execute([$ip_address, $email]);
            $error = "Invalid email or password.";
        }

    } catch (PDOException $e) {
        error_log("Login Page DB Error: " . $e->getMessage());
        $error = "A system error occurred. Please try again.";
    }
}

$pageTitle = "Log In to QuickMed";
include 'templates/header.php';
?>

<!-- Professional Login Page Design -->
<div class="fade-in min-h-[80vh] flex items-center justify-center bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-4xl mx-auto bg-white shadow-2xl rounded-2xl overflow-hidden flex flex-col md:flex-row">
        
        <!-- Left Side: Login Form -->
        <div class="w-full md:w-1/2 p-8 sm:p-12">
            <div>
                <a href="index.php" class="flex items-center gap-2 mb-8"><i class="fas fa-pills text-2xl text-teal-600"></i><span class="font-bold text-xl text-slate-800">QuickMed</span></a>
                <h2 class="text-3xl font-extrabold text-slate-900">Welcome Back!</h2>
                <p class="mt-2 text-gray-600">Log in to continue to your dashboard.</p>
            </div>

            <?php if ($error): ?>
                <div class="mt-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert"><p><?= e($error); ?></p></div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div><label for="email" class="sr-only">Email Address</label><input id="email" name="email" type="email" required autocomplete="email" class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm" placeholder="Email address" value="<?= e($email); ?>" autofocus></div>
                    <div><label for="password" class="sr-only">Password</label><input id="password" name="password" type="password" required autocomplete="current-password" class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm" placeholder="Password"></div>
                </div>
                <div class="flex items-center justify-end"><div class="text-sm"><a href="forgot_password.php" class="font-medium text-teal-600 hover:text-teal-500">Forgot your password?</a></div></div>
                <div><button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">Log In</button></div>
            </form>
            <p class="mt-6 text-center text-sm text-gray-600">Not a member? <a href="signup.php" class="font-medium text-teal-600 hover:text-teal-500">Sign up now</a></p>
        </div>

        <!-- Right Side: Image -->
        <div class="hidden md:block w-1/2 bg-teal-500 relative">
             <img class="absolute h-full w-full object-cover" src="https://images.unsplash.com/photo-1584308666744-8480404b65ae?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1170&q=80" alt="Pharmacy">
             <div class="absolute inset-0 bg-teal-700 opacity-60"></div>
             <div class="absolute inset-0 flex items-center justify-center p-12 text-white text-center">
                 <div><h3 class="text-3xl font-bold">Your Health, Our Priority</h3><p class="mt-4 text-lg opacity-90">Access thousands of medicines and manage your health seamlessly with QuickMed.</p></div>
             </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>