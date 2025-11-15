<?php
// FILE: shops.php (Admin Panel for Shop Management)
require_once 'src/session.php';
require_once 'config/database.php';
if (!has_role(ROLE_ADMIN)) { redirect('dashboard.php'); }

$pageTitle = "Manage Shops";
try {
    $shops = $pdo->query("SELECT * FROM shops ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $shops = []; $_SESSION['error_message'] = "Could not fetch shops.";
}
include 'templates/header.php';
?>
<div class="fade-in p-6" x-data="{ showForm: false, isEdit: false, shop: {} }">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-slate-800">Manage Shops</h1>
        <button @click="showForm = true; isEdit = false; shop = {}" class="btn-primary"><i class="fas fa-plus mr-2"></i> Add New Shop</button>
    </div>
    <!-- Add/Edit Shop Modal -->
    <div x-show="showForm" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div @click.away="showForm = false" class="bg-white p-8 rounded-lg shadow-xl w-full max-w-lg">
            <h2 class="text-2xl font-bold mb-6" x-text="isEdit ? 'Edit Shop' : 'Create a New Shop'"></h2>
            <form action="shop_process.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" :value="isEdit ? 'update' : 'create'">
                <input type="hidden" name="shop_id" :value="shop.id">
                <div><label>Shop Name</label><input type="text" name="name" x-model="shop.name" required class="w-full p-2 border rounded"></div>
                <div><label>Shop Address</label><textarea name="address" x-model="shop.address" required class="w-full p-2 border rounded"></textarea></div>
                <div class="flex justify-end gap-4 pt-4"><button type="button" @click="showForm = false" class="text-gray-600">Cancel</button><button type="submit" class="btn-primary" x-text="isEdit ? 'Save Changes' : 'Create Shop'"></button></div>
            </form>
        </div>
    </div>
    <!-- Messages & Table -->
    <div class="bg-white p-6 rounded-lg shadow-md border">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th>Name</th><th>Address</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($shops as $s): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium"><?= e($s['name']) ?></td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?= e($s['address']) ?></td>
                    <td class="px-6 py-4 text-right text-sm space-x-4">
                        <button @click="showForm=true; isEdit=true; shop={id:<?=e($s['id'])?>, name:'<?=e($s['name'])?>', address:'<?=e($s['address'])?>'}" class="text-teal-600 hover:underline">Edit</button>
                        <form action="shop_process.php" method="POST" class="inline" onsubmit="return confirm('Delete this shop?');"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>"><input type="hidden" name="shop_id" value="<?=e($s['id'])?>"><input type="hidden" name="action" value="delete"><button type="submit" class="text-red-600 hover:underline">Delete</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'templates/footer.php'; ?>