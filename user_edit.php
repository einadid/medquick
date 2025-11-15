<?php
// FILE: user_edit.php
// PURPOSE: Allows Admins to edit a user's details, role, and shop assignment.

require_once 'src/session.php';
require_once 'config/database.php';

// Security: Admins only.
if (!has_role(ROLE_ADMIN)) {
    redirect('dashboard.php');
}

$user_to_edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Admin cannot edit their own account via this page to prevent self-lockout.
if ($user_to_edit_id === $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You cannot edit your own account from this panel. Please use the 'My Profile' page.";
    redirect('dashboard.php');
}

if ($user_to_edit_id <= 0) {
    redirect('dashboard.php');
}

// --- DATA FETCHING ---
try {
    // Fetch user to be edited
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role, shop_id, is_active FROM users WHERE id = ?");
    $stmt->execute([$user_to_edit_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error_message'] = "User not found.";
        redirect('dashboard.php');
    }

    // Fetch all available shops for the dropdown
    $shops = $pdo->query("SELECT id, name FROM shops ORDER BY name")->fetchAll();

} catch (PDOException $e) {
    error_log("User Edit fetch error: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred.";
    redirect('dashboard.php');
}

$pageTitle = "Edit User";
include 'templates/header.php';
?>
<div class="fade-in bg-slate-50 py-12">
    <div class="container mx-auto px-4 sm:px-6 max-w-2xl">
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Edit User</h1>
        <p class="text-gray-600 mb-8">Editing profile for: <strong class="text-teal-600"><?= e($user['full_name']) ?></strong></p>

        <?php if (isset($_SESSION['error_message'])): ?>
             <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?= e($_SESSION['error_message']); ?></p>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-lg shadow-md border">
            <form action="user_manage_process.php" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" value="<?= e($user['id']); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="full_name" id="full_name" value="<?= e($user['full_name']) ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" id="email" value="<?= e($user['email']) ?>" class="mt-1 block w-full bg-gray-100 rounded-md border-gray-300" disabled>
                    </div>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" name="phone" id="phone" value="<?= e($user['phone'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" id="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                            <option value="salesman" <?= $user['role'] === 'salesman' ? 'selected' : '' ?>>Salesman</option>
                            <option value="shop_admin" <?= $user['role'] === 'shop_admin' ? 'selected' : '' ?>>Shop Admin</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                     <div id="shop-assignment-div">
                        <label for="shop_id" class="block text-sm font-medium text-gray-700">Assigned Shop</label>
                        <select name="shop_id" id="shop_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="">None</option>
                            <?php foreach ($shops as $shop): ?>
                                <option value="<?= e($shop['id']) ?>" <?= $user['shop_id'] == $shop['id'] ? 'selected' : '' ?>><?= e($shop['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Required for Shop Admin and Salesman roles.</p>
                    </div>
                </div>

                <div class="flex justify-end items-center gap-4 pt-4 border-t">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Cancel</a>
                    <button type="submit" class="btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show/hide shop assignment based on selected role
document.addEventListener('DOMContentLoaded', () => {
    const roleSelect = document.getElementById('role');
    const shopDiv = document.getElementById('shop-assignment-div');

    const toggleShopDiv = () => {
        const selectedRole = roleSelect.value;
        if (selectedRole === 'salesman' || selectedRole === 'shop_admin') {
            shopDiv.style.display = 'block';
        } else {
            shopDiv.style.display = 'none';
        }
    };

    roleSelect.addEventListener('change', toggleShopDiv);
    toggleShopDiv(); // Run on page load
});
</script>

<?php include 'templates/footer.php'; ?>