<?php
$pageTitle = 'Edit Profile';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$user = getCurrentUser();
$userId = $user['id'];

// Get current user details
$userDetails = Database::getInstance()->fetchOne("
    SELECT * FROM users WHERE id = ?
", [$userId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $fullName = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validate
    $errors = [];
    
    if (empty($fullName)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    // Handle profile picture upload
    $profilePicture = $userDetails['profile_picture'];
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        
        // Validate image
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid image type. Only JPG, PNG, GIF allowed.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image too large. Maximum 2MB.';
        } else {
            // Upload image
            $newName = 'profile_' . $userId . '_' . uniqid() . '.' . $ext;
            $uploadPath = UPLOAD_PATH . 'profiles/';
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath . $newName)) {
                // Delete old profile picture
                if ($profilePicture && file_exists(UPLOAD_PATH . $profilePicture)) {
                    unlink(UPLOAD_PATH . $profilePicture);
                }
                
                $profilePicture = 'profiles/' . $newName;
            } else {
                $errors[] = 'Failed to upload image';
            }
        }
    }
    
    if (empty($errors)) {
        // Update profile
        Database::getInstance()->updateById('users', $userId, [
            'full_name' => $fullName,
            'phone' => $phone,
            'address' => $address,
            'profile_picture' => $profilePicture
        ]);
        
        logAudit($userId, 'profile_updated', 'User updated profile information');
        setFlash('success', 'Profile updated successfully!');
        redirect('/profile.php');
    }
}
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white border-2 border-gray-300 p-6 mb-4">
        <h2 class="text-2xl font-bold">Edit Profile</h2>
        <p class="text-gray-600">Update your personal information and profile picture</p>
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
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            
            <!-- Profile Picture Upload -->
            <div class="mb-6 text-center">
                <div class="mb-4">
                    <?php if ($userDetails['profile_picture'] && file_exists(UPLOAD_PATH . $userDetails['profile_picture'])): ?>
                        <img src="<?php echo UPLOAD_URL . $userDetails['profile_picture']; ?>" 
                             alt="Profile Picture" 
                             class="w-32 h-32 rounded-full mx-auto border-4 border-gray-300 object-cover">
                    <?php else: ?>
                        <div class="w-32 h-32 rounded-full mx-auto border-4 border-gray-300 bg-blue-600 text-white flex items-center justify-center text-4xl font-bold">
                            <?php echo strtoupper(substr($userDetails['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <label class="block mb-2 font-bold">Change Profile Picture</label>
                <input type="file" 
                       name="profile_picture" 
                       accept="image/*"
                       class="block mx-auto p-2 border-2 border-gray-400">
                <div class="text-sm text-gray-600 mt-1">JPG, PNG, GIF (Max 2MB)</div>
            </div>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Email Address</label>
                <input type="email" 
                       value="<?php echo clean($userDetails['email']); ?>" 
                       disabled 
                       class="w-full p-2 border-2 border-gray-300 bg-gray-100">
                <div class="text-sm text-gray-600 mt-1">Email cannot be changed. Contact admin if needed.</div>
            </div>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Full Name *</label>
                <input type="text" 
                       name="full_name" 
                       value="<?php echo clean($userDetails['full_name']); ?>" 
                       required 
                       class="w-full p-2 border-2 border-gray-400">
            </div>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Phone Number *</label>
                <input type="text" 
                       name="phone" 
                       value="<?php echo clean($userDetails['phone']); ?>" 
                       required 
                       placeholder="01XXXXXXXXX"
                       class="w-full p-2 border-2 border-gray-400">
            </div>
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Address</label>
                <textarea name="address" 
                          rows="3" 
                          class="w-full p-2 border-2 border-gray-400"><?php echo clean($userDetails['address']); ?></textarea>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="flex-1 p-3 bg-blue-600 text-white font-bold">
                    SAVE CHANGES
                </button>
                <a href="profile.php" class="flex-1 p-3 bg-gray-400 text-white text-center font-bold">
                    CANCEL
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>