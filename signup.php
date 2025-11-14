<?php
// ========== PHP Logic (ফাইলের самом শুরুতে থাকবে) ==========

// প্রয়োজনীয় ফাইলগুলো যুক্ত করা
require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

// যদি ইউজার ஏற்கனவே লগইন করা থাকে, তাকে ড্যাشبোর্ডে পাঠিয়ে দেওয়া হবে
if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$fullName = '';
$email = '';

// যখন ফর্মটি সাবমিট করা হবে (POST method)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF টোকেন ভেরিফাই করা (सुरক্ষার জন্য)
    verify_csrf_token($_POST['csrf_token']);

    // 2. ফর্ম থেকে ডেটা সংগ্রহ করা
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $verificationCode = trim($_POST['verification_code'] ?? '');
    $shopId = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : null;

    // 3. বেসিক ভ্যালিডেশন
    if (empty($fullName)) { $errors[] = 'Full Name is required.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Invalid email format.'; }
    if (strlen($password) < 6) { $errors[] = 'Password must be at least 6 characters long.'; }

    // 4. ভেরিফিকেশন কোড অনুযায়ী Role নির্ধারণ করা
    $role = ROLE_CUSTOMER; // ডিফল্ট Role
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
            default:
                $errors[] = 'Invalid verification code.';
        }
    }
    
    // shop_admin বা salesman হলে shop_id আবশ্যক
    if (in_array($role, [ROLE_SHOP_ADMIN, ROLE_SALESMAN]) && empty($shopId)) {
        $errors[] = 'Please select a shop.';
    }

    // 5. ইমেলটি 이미 ব্যবহৃত হয়েছে কিনা তা ডেটাবেসে চেক করা
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email address is already registered.';
        }
    }

    // 6. যদি কোনো error না থাকে, তাহলে ডেটাবেসে ইউজার তৈরি করা
    if (empty($errors)) {
        // পাসওয়ার্ডকে सुरक्षितভাবে হ্যাশ করা
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $sql = "INSERT INTO users (full_name, email, password_hash, role, shop_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            // shop_admin বা salesman না হলে shopId null হবে
            $finalShopId = in_array($role, [ROLE_SHOP_ADMIN, ROLE_SALESMAN]) ? $shopId : null;

            $stmt->execute([$fullName, $email, $passwordHash, $role, $finalShopId]);

            // রেজিস্ট্রেশনের পর ইউজারকে অটো-লগইন করানো
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $fullName;
            $_SESSION['role'] = $role;
            if ($finalShopId) {
                $_SESSION['shop_id'] = $finalShopId;
            }
            session_regenerate_id(true); // Session fixation attack থেকে সুরক্ষা

            // ড্যাشبোর্ডে Redirect করা
            redirect('dashboard.php');

        } catch (PDOException $e) {
            // ডেটাবেস error হলে, একটি সাধারণ বার্তা দেখানো
            // error_log($e->getMessage()); // Production-এ error log করা উচিত
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

// Shop list fetch করা (shop_admin/salesman রেজিস্ট্রেশনের জন্য)
try {
    $shops_stmt = $pdo->query("SELECT id, name FROM shops ORDER BY name");
    $shops = $shops_stmt->fetchAll();
} catch (PDOException $e) {
    $shops = []; // Error হলে empty array
}


$pageTitle = "Sign Up - " . APP_NAME;
// ========== HTML Form (PHP লจিকের নিচে থাকবে) ==========
?>
<?php include 'templates/header.php'; ?>

<div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900">Create an Account</h1>
            <p class="text-gray-600">Join QuickMed Today!</p>
        </div>

        <!-- Error messages প্রদর্শনের জন্য -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="space-y-6" action="signup.php" method="POST" id="signup-form">
            <!-- CSRF Token (மிகவும் জরুরি) -->
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">

            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input id="full_name" name="full_name" type="text" required class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" value="<?= e($fullName); ?>">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input id="email" name="email" type="email" required autocomplete="email" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" value="<?= e($email); ?>">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="verification_code" class="block text-sm font-medium text-gray-700">Verification Code (Optional)</label>
                <input id="verification_code" name="verification_code" type="text" placeholder="For Admin/Salesman roles" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Shop Selection (JavaScript দিয়ে hide/show করা হবে) -->
            <div id="shop-selection" class="hidden">
                <label for="shop_id" class="block text-sm font-medium text-gray-700">Select Your Shop</label>
                <select id="shop_id" name="shop_id" class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Select Shop --</option>
                    <?php foreach ($shops as $shop): ?>
                        <option value="<?= e($shop['id']); ?>"><?= e($shop['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button type="submit" class="w-full px-4 py-2 font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Sign Up
                </button>
            </div>

            <p class="text-sm text-center text-gray-600">
                Already have an account?
                <a href="login.php" class="font-medium text-blue-600 hover:underline">Log In</a>
            </p>
        </form>
    </div>
</div>

<!-- JavaScript to show shop selection based on verification code -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const verificationInput = document.getElementById('verification_code');
    const shopSelectionDiv = document.getElementById('shop-selection');
    
    const shopAdminCode = "<?= VERIFICATION_CODE_SHOP_ADMIN ?>";
    const salesmanCode = "<?= VERIFICATION_CODE_SALESMAN ?>";

    verificationInput.addEventListener('input', function() {
        const code = this.value.trim();
        if (code === shopAdminCode || code === salesmanCode) {
            shopSelectionDiv.classList.remove('hidden');
        } else {
            shopSelectionDiv.classList.add('hidden');
        }
    });
});
</script>

<?php include 'templates/footer.php'; ?>