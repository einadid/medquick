<?php
// FILE: signup.php (Final Professional Version with Bonus Points)
// PURPOSE: Handles new user registration with an improved UI and awards bonus points to new customers.

require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

// If a user is already logged in, redirect them to the dashboard.
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Initialize variables for form pre-filling and error display.
$errors = [];
$fullName = '';
$email = '';

// Process the form on POST request.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);

    // --- 1. Get and Sanitize Form Data ---
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $verificationCode = trim($_POST['verification_code'] ?? '');
    $shopId = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : null;

    // --- 2. Validate Inputs ---
    if (empty($fullName)) { $errors[] = 'Full Name is required.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email format is required.'; }
    if (strlen($password) < 6) { $errors[] = 'Password must be at least 6 characters long.'; }

    // Determine role based on verification code. Default is 'customer'.
    $role = ROLE_CUSTOMER;
    if (!empty($verificationCode)) {
        switch ($verificationCode) {
            case VERIFICATION_CODE_ADMIN: $role = ROLE_ADMIN; break;
            case VERIFICATION_CODE_SHOP_ADMIN: $role = ROLE_SHOP_ADMIN; break;
            case VERIFICATION_CODE_SALESMAN: $role = ROLE_SALESMAN; break;
            default: $errors[] = 'The verification code you entered is invalid.';
        }
    }
    
    // For shop-related roles, a shop must be selected.
    if (in_array($role, [ROLE_SHOP_ADMIN, ROLE_SALESMAN]) && empty($shopId)) {
        $errors[] = 'Please select an assigned shop for this role.';
    }
    
    // Check if the email is already registered.
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email address is already registered. Please log in.';
        }
    }

    // --- 3. Create User if No Errors ---
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $finalShopId = in_array($role, [ROLE_SHOP_ADMIN, ROLE_SALESMAN]) ? $shopId : null;
            
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, shop_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$fullName, $email, $passwordHash, $role, $finalShopId]);
            $user_id = $pdo->lastInsertId();
            
            // **NEW: Add 100 bonus points for new customers**
            if ($role === ROLE_CUSTOMER) {
                $bonus_stmt = $pdo->prepare("UPDATE users SET points_balance = 100 WHERE id = ?");
                $bonus_stmt->execute([$user_id]);
            }
            
            log_audit($pdo, 'USER_SIGNUP', "New User ID: {$user_id}, Role: {$role}");

            // Auto-login the user after successful signup
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = $role;
            if ($finalShopId) { $_SESSION['shop_id'] = $finalShopId; }

            redirect('dashboard.php');
        } catch (PDOException $e) {
            error_log("Signup DB Error: " . $e->getMessage());
            $errors[] = 'A system error occurred. Please try again.';
        }
    }
}

// Fetch shops for the role-specific dropdown.
try {
    $shops = $pdo->query("SELECT id, name FROM shops ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $shops = [];
    $errors[] = 'Could not load shop list. Please contact support.';
}

$pageTitle = "Create an Account";
include 'templates/header.php';
?>

<!-- Professional Signup Page Design -->
<div class="fade-in min-h-[80vh] flex items-center justify-center bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-5xl mx-auto bg-white shadow-2xl rounded-2xl overflow-hidden flex flex-col md:flex-row">
        
        <!-- Left Side: Image -->
        <div class="hidden md:block md:w-1/2 bg-teal-500 relative">
             <img class="absolute h-full w-full object-cover" src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1170&q=80" alt="Doctor with tablet">
             <div class="absolute inset-0 bg-teal-700 opacity-70"></div>
             <div class="absolute inset-0 flex items-center justify-center p-12 text-white text-center">
                 <div><h3 class="text-3xl font-bold">Join the Future of Pharmacy</h3><p class="mt-4 text-lg opacity-90">Create your account to access personalized health services, manage orders, and earn rewards.</p></div>
             </div>
        </div>

        <!-- Right Side: Signup Form -->
        <div class="w-full md:w-1/2 p-8 sm:p-12">
            <div><h2 class="text-3xl font-extrabold text-slate-900">Create Your Account</h2><p class="mt-2 text-gray-600">Get started with QuickMed in seconds.</p></div>

            <?php if (!empty($errors)): ?>
                <div class="mt-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                    <p class="font-bold">Please fix the following issues:</p>
                    <ul class="list-disc pl-5 mt-2 text-sm">
                        <?php foreach ($errors as $error): ?><li><?= e($error); ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-4" action="signup.php" method="POST" id="signup-form">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                <div><label for="full_name" class="sr-only">Full Name</label><input id="full_name" name="full_name" type="text" required class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm" placeholder="Full Name" value="<?= e($fullName); ?>"></div>
                <div><label for="email" class="sr-only">Email Address</label><input id="email" name="email" type="email" required autocomplete="email" class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm" placeholder="Email Address" value="<?= e($email); ?>"></div>
                <div><label for="password" class="sr-only">Password</label><input id="password" name="password" type="password" required autocomplete="new-password" minlength="6" class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm" placeholder="Password (min. 6 characters)"></div>
                <div><label for="verification_code" class="sr-only">Verification Code</label><input id="verification_code" name="verification_code" type="text" class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm" placeholder="Verification Code (Optional for special roles)"></div>
                
                <!-- Shop Selection (conditionally shown by JS) -->
                <div id="shop-selection" class="hidden transition-all duration-300">
                     <label for="shop_id" class="sr-only">Select Your Shop</label>
                     <select id="shop_id" name="shop_id" class="relative block w-full px-3 py-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500 sm:text-sm">
                        <option value="">-- Select Assigned Shop --</option>
                        <?php foreach ($shops as $shop): ?><option value="<?= e($shop['id']); ?>"><?= e($shop['name']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div><button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">Create Account</button></div>
            </form>
            <p class="mt-6 text-center text-sm text-gray-600">Already have an account? <a href="login.php" class="font-medium text-teal-600 hover:text-teal-500">Log in here</a></p>
        </div>
    </div>
</div>

<!-- JavaScript to show/hide shop selection -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const verificationInput = document.getElementById('verification_code');
    const shopSelectionDiv = document.getElementById('shop-selection');
    const shopAdminCode = "<?= e(VERIFICATION_CODE_SHOP_ADMIN) ?>";
    const salesmanCode = "<?= e(VERIFICATION_CODE_SALESMAN) ?>";
    const checkCode = () => {
        const code = verificationInput.value.trim();
        if (code === shopAdminCode || code === salesmanCode) {
            shopSelectionDiv.classList.remove('hidden');
        } else {
            shopSelectionDiv.classList.add('hidden');
        }
    };
    verificationInput.addEventListener('input', checkCode);
    checkCode();
});
</script>

<?php include 'templates/footer.php'; ?>