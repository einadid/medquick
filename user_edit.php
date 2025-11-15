<?php
// FILE: user_edit.php (Final & Professional Version)
// PURPOSE: Allows Admins to edit a user's details, role, and shop assignment.

require_once 'src/session.php';
require_once 'config/database.php';
ensure_user_session_data(); // Ensure admin's own session data is fresh

// Security: Admins only.
if (!has_role(ROLE_ADMIN)) {
    redirect('dashboard.php');
}

$user_to_edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Admin cannot edit their own account via this panel.
if ($user_to_edit_id === $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You cannot edit your own account from this panel. Please use the 'My Profile' page.";
    redirect('users.php');
}

if ($user_to_edit_id <= 0) {
    redirect('users.php');
}

$pageTitle = "Edit User";
$user = null;
$shops = [];

try {
    // Fetch the user to be edited
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role, shop_id FROM users WHERE id = ?");
    $stmt->execute([$user_to_edit_id]);
    $user = $stmt->fetch();

    // If user doesn't exist, redirect back with an error.
    if (!$user) {
        $_SESSION['error_message'] = "User not found with ID: $user_to_edit_id";
        redirect('users.php');
    }

    // Fetch all available shops for the assignment dropdown
    $shops = $pdo->query("SELECT id, name FROM shops ORDER BY name")->fetchAll();

} catch (PDOException $e) {
    error_log("User Edit page fetch error: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred while fetching user data.";
    redirect('users.php');
}

include 'templates/header.php';
?>

<!-- This main content area is now defined within the page itself -->
<main class="w-full">
    <div class="fade-in p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-slate-800">Edit User</h1>
            <a href="users.php" class="text-sm font-medium text-teal-600 hover:underline">&larr; Back to User List</a>
        </div>

        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md border">
            <div class="mb-6 pb-6 border-b">
                <p class="font-bold text-lg text-slate-800"><?= e($user['full_name']) ?></p>
                <p class="text-sm text-gray-500"><?= e($user['email']) ?></p>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                 <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?= e($_SESSION['error_message']); ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form action="user_process.php" method="POST" class="space-y-6" x-data="{ role: '<?= e($user['role']) ?>' }">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?= e($user['id']); ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="full_name" id="full_name" value="<?= e($user['full_name']) ?>" required class="mt-1 w-full p-2 border rounded-md">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="text" name="phone" id="phone" value="<?= e($user['phone'] ?? '') ?>" class="mt-1 w-full p-2 border rounded-md">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" id="role" x-model="role" required class="mt-1 w-full p-2 border rounded-md bg-white">
                            <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                            <option value="salesman" <?= $user['role'] === 'salesman' ? 'selected' : '' ?>>Salesman</option>
                            <option value="shop_admin" <?= $user['role'] === 'shop_admin' ? 'selected' : '' ?>>Shop Admin</option>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                     <div x-show="role === 'salesman' || role === 'shop_admin'" x-transition>
                        <label for="shop_id" class="block text-sm font-medium text-gray-700">Assigned Shop</label>
                        <select name="shop_id" id="shop_id" class="mt-1 w-full p-2 border rounded-md bg-white">
                            <option value="">None</option>
                            <?php foreach ($shops as $shop): ?>
                                <option value="<?= e($shop['id']) ?>" <?= $user['shop_id'] == $shop['id'] ? 'selected' : '' ?>><?= e($shop['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">This role requires a shop assignment.</p>
                    </div>
                </div>

                <div class="pt-5 border-t flex justify-end">
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>