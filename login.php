<?php
session_start();
require 'includes/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($role == 'admin') {
                header('Location: admin/index.php');
            } elseif ($role == 'salesman') {
                header('Location: salesman/index.php');
            } else {
                header('Location: customer/index.php');
            }
            exit();
        } else {
            $error = "ভুল ইউজারনেম, পাসওয়ার্ড বা রোল!";
        }
    } catch (PDOException $e) {
        $error = "ডাটাবেস এরর: " . $e->getMessage();
    }
}
// ... existing code ...

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['shop_id'] = $user['shop_id']; // For salesman

            // Redirect based on role
            if ($role == 'admin') {
                header('Location: admin/index.php');
            } elseif ($role == 'salesman') {
                header('Location: salesman/index.php');
            } else {
                header('Location: customer/index.php');
            }
            exit();
        } else {
            $error = "ভুল ইউজারনেম, পাসওয়ার্ড বা রোল!";
        }
    } catch (PDOException $e) {
        $error = "ডাটাবেস এরর: " . $e->getMessage();
    }
}

// ... existing code ...
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-lg mt-10">
    <h2 class="text-2xl font-bold text-center mb-6">লগইন করুন</h2>
    
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
            <label class="block text-gray-700 text-sm font-bold mb-2">পাসওয়ার্ড</label>
            <input type="password" name="password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">আপনার রোল</label>
            <select name="role" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="customer">গ্রাহক</option>
                <option value="admin">অ্যাডমিন</option>
                <option value="salesman">সেলসম্যান</option>
            </select>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-300">
            লগইন করুন
        </button>
    </form>

    <p class="text-center mt-4">
        একাউন্ট নেই? <a href="signup.php" class="text-blue-600 hover:underline">সাইন আপ করুন</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>