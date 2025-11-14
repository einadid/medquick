<?php
// ========== PHP Logic (ফাইলের শুরুতে) ==========
    ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

// যদি ইউজার ஏற்கனவே লগইন করা থাকে, তাকে ড্যাشبোর্ডে পাঠিয়ে দেওয়া হবে
if (is_logged_in()) {
    log_audit($pdo, 'USER_LOGIN', "User ID: {$user['id']}");
    redirect('dashboard.php');
}

$error = null;
$email = '';

// যখন ফর্মটি সাবমিট করা হবে
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF টোকেন ভেরিফাই করা
    verify_csrf_token($_POST['csrf_token']);

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // --- নিরাপত্তা: Rate Limiting ---
    // ১৫ মিনিটের মধ্যে ৫ বারের বেশি ভুল চেষ্টা হলে ব্লক করা হবে
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
        $stmt->execute([$ip_address]);
        if ($stmt->fetchColumn() > 5) {
            // production-এ এখানে একটি সুন্দর error পেজ দেখানো উচিত
            die('Too many login attempts. Please try again in 15 minutes.');
        }

        // --- ইউজার ভেরিফিকেশন ---
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // পাসওয়ার্ড ভেরিফাই করা
        if ($user && password_verify($password, $user['password_hash'])) {
            // লগইন সফল!
            
            // 1. এই IP থেকে করা পুরনো ভুল attempt গুলো মুছে ফেলা
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt->execute([$ip_address]);

            // 2. সেশন পুনরায় তৈরি করা (Session fixation attack থেকে সুরক্ষা)
            session_regenerate_id(true);

            // 3. সেশনে ইউজারের তথ্য সংরক্ষণ করা
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            if ($user['shop_id']) {
                $_SESSION['shop_id'] = $user['shop_id'];
            }

            // 4. ড্যাشبোর্ডে Redirect করা
            redirect('dashboard.php');

        } else {
            // লগইন ব্যর্থ!
            // 1. ভুল attempt লগ করা
            $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)");
            $stmt->execute([$ip_address, $email]);

            // 2. Error বার্তা সেট করা
            $error = "Invalid email or password.";
        }

    } catch (PDOException $e) {
        // error_log("Login Error: " . $e->getMessage());
        $error = "A system error occurred. Please try again later.";
    }
}

$pageTitle = "Log In - " . APP_NAME;
// ========== HTML Form (PHP লজিকের নিচে) ==========
?>
<?php include 'templates/header.php'; ?>

<div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-sm p-8 space-y-6 bg-white rounded-lg shadow-md">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900">Welcome Back!</h1>
            <p class="text-gray-600">Log in to your QuickMed account.</p>
        </div>

        <!-- Error message প্রদর্শনের জন্য -->
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-center" role="alert">
                <p><?= e($error); ?></p>
            </div>
        <?php endif; ?>

        <form class="space-y-6" action="login.php" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input id="email" name="email" type="email" required autocomplete="email" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" value="<?= e($email); ?>" autofocus>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <button type="submit" class="w-full px-4 py-2 font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Log In
                </button>
            </div>

            <p class="text-sm text-center text-gray-600">
                Don't have an account?
                <a href="signup.php" class="font-medium text-blue-600 hover:underline">Sign Up</a>
            </p>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>