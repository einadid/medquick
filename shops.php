<?php
// FILE: shops.php (Final Professional Version with Dedicated Pages)
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
<main class="w-full">
    <div class="fade-in p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-slate-800">Manage Shops</h1>
            <a href="shop_add.php" class="btn-primary"><i class="fas fa-plus mr-2"></i> Add New Shop</a>
        </div>
        
        <!-- Session Messages -->
        <?php if(isset($_SESSION['success_message'])) { echo '<div class="bg-green-100 text-green-700 p-4 mb-6 rounded-md">'.e($_SESSION['success_message']).'</div>'; unset($_SESSION['success_message']); } ?>
        <?php if(isset($_SESSION['error_message'])) { echo '<div class="bg-red-100 text-red-700 p-4 mb-6 rounded-md">'.e($_SESSION['error_message']).'</div>'; unset($_SESSION['error_message']); } ?>
        
        <div class="bg-white p-6 rounded-lg shadow-md border">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if(empty($shops)): ?>
                            <tr><td colspan="3" class="text-center py-10">No shops found. <a href="shop_add.php" class="text-teal-600 font-semibold">Add the first one!</a></td></tr>
                        <?php else: foreach ($shops as $shop): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900"><?= e($shop['name']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= e($shop['address']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                    <a href="shop_edit.php?id=<?= e($shop['id']) ?>" class="text-teal-600 hover:text-teal-900">Edit</a>
                                    <form action="shop_process.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this shop? This might affect existing user and order data.');">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="shop_id" value="<?= e($shop['id']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php include 'templates/footer.php'; ?>