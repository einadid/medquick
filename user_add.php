<?php
// FILE: user_add.php (New Dedicated Page for Adding Users)
require_once 'src/session.php';
require_once 'config/database.php';

if (!has_role(ROLE_ADMIN)) { redirect('dashboard.php'); }
$pageTitle = "Add New User";

try {
    $shops = $pdo->query("SELECT id, name FROM shops ORDER BY name")->fetchAll();
} catch (PDOException $e) { $shops = []; }

include 'templates/header.php';
?>
<div class="fade-in p-6" x-data="{ role: 'customer' }">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-slate-800">Create a New User</h1>
        <a href="users.php" class="text-sm font-medium text-teal-600 hover:underline">&larr; Back to User List</a>
    </div>
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md border">
        <form action="user_process.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="create">
            
            <div><label for="full_name" class="block text-sm font-medium">Full Name</label><input type="text" id="full_name" name="full_name" required class="mt-1 w-full p-2 border rounded-md"></div>
            <div><label for="email" class="block text-sm font-medium">Email Address</label><input type="email" id="email" name="email" required class="mt-1 w-full p-2 border rounded-md"></div>
            <div><label for="password" class="block text-sm font-medium">Password</label><input type="password" id="password" name="password" required minlength="6" class="mt-1 w-full p-2 border rounded-md"></div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label for="role" class="block text-sm font-medium">Assign Role</label><select name="role" id="role" x-model="role" class="mt-1 w-full p-2 border rounded-md">
                    <option value="customer">Customer</option><option value="salesman">Salesman</option>
                    <option value="shop_admin">Shop Admin</option><option value="admin">Admin</option>
                </select></div>
                <div x-show="role === 'salesman' || role === 'shop_admin'" x-transition>
                    <label for="shop_id" class="block text-sm font-medium">Assign Shop</label>
                    <select name="shop_id" id="shop_id" class="mt-1 w-full p-2 border rounded-md">
                        <option value="">-- Select a Shop --</option>
                        <?php foreach($shops as $shop): ?><option value="<?= e($shop['id']) ?>"><?= e($shop['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="pt-5 border-t flex justify-end">
                <button type="submit" class="btn-primary text-lg">Create User</button>
            </div>
        </form>
    </div>
</div>
<?php include 'templates/footer.php'; ?>