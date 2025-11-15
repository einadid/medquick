<?php
// FILE: profile.php (Final Professional Version with all features)
// PURPOSE: Allows logged-in users to manage their profile picture, information, and password.

require_once 'src/session.php';
require_once 'config/database.php';

// Security: User must be logged in to access this page.
if (!is_logged_in()) {
    redirect('login.php?redirect=profile.php');
}

$user_id = $_SESSION['user_id'];

// Fetch current user data to display in the form.
try {
    $stmt = $pdo->prepare("SELECT full_name, email, phone, profile_image_path FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Profile page fetch error: " . $e->getMessage());
    $user = null; // Handle the error gracefully in the view.
}

$pageTitle = "My Profile & Settings";
include 'templates/header.php';
?>

<div class="fade-in bg-slate-50 py-12">
    <div class="container mx-auto px-4 sm:px-6">
        <?php $is_customer = has_role(ROLE_CUSTOMER); ?>
        <div class="flex flex-col <?= $is_customer ? 'lg:flex-row' : '' ?> gap-8">
            
            <!-- Sidebar Navigation (only for customers) -->
            <?php if ($is_customer) { include 'templates/_customer_sidebar.php'; } ?>

            <!-- Main Content -->
            <div class="w-full <?= $is_customer ? 'lg:w-3/4' : '' ?>">
                <h1 class="text-3xl font-bold text-slate-800 mb-8">Profile & Settings</h1>
                
                <!-- Session Messages with auto-fade functionality using Alpine.js -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
                        <p><?= e($_SESSION['success_message']); ?></p>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                     <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
                        <p><?= e($_SESSION['error_message']); ?></p>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    <!-- Left Column: Profile Picture Management -->
                    <div class="xl:col-span-1">
                        <div class="bg-white p-8 rounded-lg shadow-md border text-center">
                            <h2 class="text-xl font-bold text-slate-700 mb-4">Profile Picture</h2>
                            <img src="<?= e($user['profile_image_path'] ?? 'assets/images/default_avatar.png') ?>" alt="Profile Picture" class="w-40 h-40 rounded-full mx-auto object-cover border-4 border-white shadow-lg mb-6">
                            
                            <form action="profile_process.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="update_picture">
                                <div>
                                    <label for="profile_image" class="w-full cursor-pointer bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold py-2 px-4 rounded-md transition-colors inline-block">
                                        <i class="fas fa-upload mr-2"></i> Choose Photo
                                    </label>
                                    <input type="file" name="profile_image" id="profile_image" class="hidden" onchange="this.form.submit()">
                                </div>
                            </form>
                             <?php if ($user['profile_image_path']): ?>
                            <form action="profile_process.php" method="POST" class="mt-2" onsubmit="return confirm('Are you sure you want to remove your profile picture?');">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="remove_picture">
                                <button type="submit" class="text-xs text-red-500 hover:underline">Remove Photo</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Right Column: Information & Password Tabs -->
                    <div class="xl:col-span-2" x-data="{ activeTab: 'info' }">
                        <div class="border-b border-gray-200 mb-6">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <button @click="activeTab = 'info'" :class="{ 'border-teal-500 text-teal-600': activeTab === 'info', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'info' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Profile Information
                                </button>
                                <button @click="activeTab = 'password'" :class="{ 'border-teal-500 text-teal-600': activeTab === 'password', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'password' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Change Password
                                </button>
                            </nav>
                        </div>

                        <div>
                            <!-- Profile Information Tab -->
                            <div x-show="activeTab === 'info'" class="bg-white p-8 rounded-lg shadow-md border" x-transition>
                                <h2 class="text-xl font-bold text-slate-700 mb-6">Update Your Information</h2>
                                <?php if ($user): ?>
                                <form action="profile_process.php" method="POST" class="space-y-6">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>"><input type="hidden" name="action" value="update_info">
                                    <div><label class="block text-sm font-medium text-gray-700">Email Address</label><input type="email" value="<?= e($user['email']) ?>" class="mt-1 block w-full bg-gray-100 rounded-md border-gray-300 shadow-sm" disabled></div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div><label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label><input type="text" name="full_name" id="full_name" value="<?= e($user['full_name']) ?>" required class="mt-1 block w-full p-3 border rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500"></div>
                                        <div><label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label><input type="text" name="phone" id="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="e.g., 01712345678" class="mt-1 block w-full p-3 border rounded-md border-gray-300 focus:border-teal-500 focus:ring-teal-500"></div>
                                    </div>
                                    <div class="text-right pt-4"><button type="submit" class="btn-primary">Save Changes</button></div>
                                </form>
                                <?php else: ?><p class="text-red-500">Could not load your profile data.</p><?php endif; ?>
                            </div>

                            <!-- Change Password Tab with Show/Hide functionality -->
                            <div x-show="activeTab === 'password'" x-data="{ showCurrent: false, showNew: false, showConfirm: false }" class="bg-white p-8 rounded-lg shadow-md border" style="display: none;" x-transition>
                                <h2 class="text-xl font-bold text-slate-700 mb-6">Set a New Password</h2>
                                <form action="profile_process.php" method="POST" class="space-y-6">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>"><input type="hidden" name="action" value="change_password">
                                    <div><label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label><div class="relative mt-1"><input :type="showCurrent ? 'text' : 'password'" name="current_password" id="current_password" required class="block w-full p-3 border rounded-md"><button type="button" @click="showCurrent = !showCurrent" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500"><i class="fas" :class="showCurrent ? 'fa-eye-slash' : 'fa-eye'"></i></button></div></div>
                                    <div><label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label><div class="relative mt-1"><input :type="showNew ? 'text' : 'password'" name="new_password" id="new_password" required minlength="6" class="block w-full p-3 border rounded-md"><button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500"><i class="fas" :class="showNew ? 'fa-eye-slash' : 'fa-eye'"></i></button></div></div>
                                    <div><label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label><div class="relative mt-1"><input :type="showConfirm ? 'text' : 'password'" name="confirm_password" id="confirm_password" required class="block w-full p-3 border rounded-md"><button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500"><i class="fas" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i></button></div></div>
                                    <div class="text-right pt-4"><button type="submit" class="btn-primary w-full md:w-auto">Update Password</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>