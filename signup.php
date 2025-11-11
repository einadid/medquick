<?php
session_start();
require 'includes/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $verification_key = $_POST['verification_key'] ?? '';

    // Validation
    if ($password !== $confirm_password) {
        $error = "পাসওয়ার্ড মিলেনি!";
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "ইউজারনেম বা ইমেইল ইতিমধ্যে নেওয়া হয়েছে!";
        } else {
            // For admin/salesman, verify the key
            if (($role == 'admin' || $role == 'salesman') && $verification_key != 'ADMIN123') {
                $error = "ভুল ভেরিফিকেশন কী!";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                
                if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                    $_SESSION['signup_success'] = true;
                    header('Location: login.php');
                    exit();
                } else {
                    $error = "রেজিস্ট্রেশন ব্যর্থ হয়েছে! আবার চেষ্টা করুন।";
                }
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-lg mt-10">
    <h2 class="text-2xl font-bold text-center mb-6">সাইন আপ করুন</h2>
    
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">ইউজারনেম</label>
            <input type="text" name="username" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">ইমেইল</label>
            <input type="email" name="email" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">পাসওয়ার্ড</label>
            <input type="password" name="password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">পাসওয়ার্ড নিশ্চিত করুন</label>
            <input type="password" name="confirm_password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">আপনি কে?</label>
            <select name="role" id="role" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="customer">গ্রাহক</option>
                <option value="admin">অ্যাডমিন</option>
                <option value="salesman">সেলসম্যান</option>
            </select>
        </div>

        <div class="mb-6" id="verificationKeyDiv" style="display: none;">
            <label class="block text-gray-700 text-sm font-bold mb-2">ভেরিফিকেশন কী</label>
            <input type="text" name="verification_key" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-gray-500 mt-1">অ্যাডমিন/সেলসম্যান একাউন্টের জন্য ভেরিফিকেশন কী প্রয়োজন</p>
        </div>

        <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition duration-300">
            একাউন্ট তৈরি করুন
        </button>
    </form>

    <p class="text-center mt-4">
        ইতিমধ্যে একাউন্ট আছে? <a href="login.php" class="text-blue-600 hover:underline">লগইন করুন</a>
    </p>
</div>

<script>
// Show/hide verification key field based on role
document.getElementById('role').addEventListener('change', function() {
    const verificationDiv = document.getElementById('verificationKeyDiv');
    if (this.value === 'admin' || this.value === 'salesman') {
        verificationDiv.style.display = 'block';
    } else {
        verificationDiv.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>