<?php
$pageTitle = 'Change Password';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$user = getCurrentUser();
$userId = $user['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    $errors = [];
    
    // Get current password hash
    $userDb = Database::getInstance()->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
    
    // Verify current password
    if (!password_verify($currentPassword, $userDb['password'])) {
        $errors[] = 'Current password is incorrect';
    }
    
    // Validate new password
    if (strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters';
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New passwords do not match';
    }
    
    if (empty($errors)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        Database::getInstance()->query("
            UPDATE users SET password = ? WHERE id = ?
        ", [$hashedPassword, $userId]);
        
        logAudit($userId, 'password_changed', 'User changed password');
        setFlash('success', 'Password changed successfully!');
        redirect('/profile.php');
    }
}
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white border-2 border-gray-300 p-6 mb-4">
        <h2 class="text-2xl font-bold">Change Password</h2>
        <p class="text-gray-600">Update your account password</p>
    </div>
    
    <?php if (isset($errors) && !empty($errors)): ?>
    <div class="bg-red-100 border-2 border-red-400 text-red-700 p-4 mb-4">
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
                <li><?php echo clean($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="bg-white border-2 border-gray-300 p-6">
        <form method="POST">
            <?php echo csrfField(); ?>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Current Password *</label>
                <input type="password" 
                       name="current_password" 
                       required 
                       class="w-full p-2 border-2 border-gray-400">
            </div>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">New Password *</label>
                <input type="password" 
                       name="new_password" 
                       required 
                       minlength="6"
                       class="w-full p-2 border-2 border-gray-400">
                <div class="text-sm text-gray-600 mt-1">Minimum 6 characters</div>
            </div>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Confirm New Password *</label>
                <input type="password" 
                       name="confirm_password" 
                       required 
                       minlength="6"
                       class="w-full p-2 border-2 border-gray-400">
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="flex-1 p-3 bg-blue-600 text-white font-bold">
                    CHANGE PASSWORD
                </button>
                <a href="profile.php" class="flex-1 p-3 bg-gray-400 text-white text-center font-bold">
                    CANCEL
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>