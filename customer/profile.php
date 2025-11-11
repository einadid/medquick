<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Fetch customer details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();

// Update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET username = ?, email = ?, phone = ?, address = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$name, $email, $phone, $address, $_SESSION['user_id']]);
        
        // Update session
        $_SESSION['username'] = $name;
        $_SESSION['email'] = $email;
        
        $success = "প্রোফাইল সফলভাবে আপডেট করা হয়েছে!";
        
        // Refresh customer data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $customer = $stmt->fetch();
        
    } catch (PDOException $e) {
        $error = "প্রোফাইল আপডেট করতে সমস্যা: " . $e->getMessage();
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (password_verify($current_password, $customer['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            
            $password_success = "পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে!";
        } else {
            $password_error = "নতুন পাসওয়ার্ড মেলেনি!";
        }
    } else {
        $password_error = "বর্তমান পাসওয়ার্ড ভুল!";
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">আমার প্রোফাইল</h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">প্রোফাইল তথ্য</h2>
                
                <?php if (isset($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">পূর্ণ নাম*</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($customer['username']); ?>" 
                                   class="w-full px-3 py-2 border rounded-lg" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">ইমেইল*</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" 
                                   class="w-full px-3 py-2 border rounded-lg" required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">ফোন নম্বর</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" 
                                   class="w-full px-3 py-2 border rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">ঠিকানা</label>
                            <textarea name="address" class="w-full px-3 py-2 border rounded-lg" 
                                      rows="2"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_profile" 
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        প্রোফাইল আপডেট করুন
                    </button>
                </form>
            </div>
            
            <!-- Change Password -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h2 class="text-xl font-bold mb-4">পাসওয়ার্ড পরিবর্তন</h2>
                
                <?php if (isset($password_success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo $password_success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($password_error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $password_error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">বর্তমান পাসওয়ার্ড*</label>
                        <input type="password" name="current_password" 
                               class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">নতুন পাসওয়ার্ড*</label>
                            <input type="password" name="new_password" 
                                   class="w-full px-3 py-2 border rounded-lg" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">পাসওয়ার্ড নিশ্চিত করুন*</label>
                            <input type="password" name="confirm_password" 
                                   class="w-full px-3 py-2 border rounded-lg" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="change_password" 
                            class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                        পাসওয়ার্ড পরিবর্তন করুন
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Account Summary -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">অ্যাকাউন্ট সারাংশ</h2>
                
                <div class="space-y-3">
                    <div>
                        <h3 class="font-semibold text-gray-700">সদস্যপদ স্তর</h3>
                        <p class="text-blue-600 font-semibold">স্ট্যান্ডার্ড মেম্বার</p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-700">অ্যাকাউন্ট তৈরি</h3>
                        <p><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-700">মোট অর্ডার</h3>
                        <p>
                            <?php 
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE customer_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $total_orders = $stmt->fetch()['total_orders'];
                            echo $total_orders;
                            ?> টি অর্ডার
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">দ্রুত লিঙ্ক</h2>
                
                <div class="space-y-3">
                    <a href="orders.php" class="flex items-center text-blue-600 hover:text-blue-800">
                        <i class="fas fa-shopping-bag mr-2"></i> আমার অর্ডারসমূহ
                    </a>
                    
                    <a href="addresses.php" class="flex items-center text-blue-600 hover:text-blue-800">
                        <i class="fas fa-address-book mr-2"></i> আমার ঠিকানাসমূহ
                    </a>
                    
                    <a href="wishlist.php" class="flex items-center text-blue-600 hover:text-blue-800">
                        <i class="fas fa-heart mr-2"></i> উইশলিস্ট
                    </a>
                    
                    <a href="../logout.php" class="flex items-center text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-2"></i> লগআউট
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>