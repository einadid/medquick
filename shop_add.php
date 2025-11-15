<?php
// FILE: shop_add.php
require_once 'src/session.php'; require_once 'config/database.php';
if (!has_role(ROLE_ADMIN)) { redirect('dashboard.php'); }
$pageTitle = "Add New Shop";
include 'templates/header.php';
?>
<main class="w-full">
    <div class="fade-in p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-slate-800">Add a New Shop</h1>
            <a href="shops.php" class="text-sm font-medium text-teal-600 hover:underline">&larr; Back to Shop List</a>
        </div>
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md border">
            <form action="shop_process.php" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="create">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Shop Name</label>
                    <input type="text" id="name" name="name" required class="mt-1 w-full p-3 border rounded-md focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Shop Address</label>
                    <textarea id="address" name="address" rows="4" required class="mt-1 w-full p-3 border rounded-md focus:ring-teal-500 focus:border-teal-500"></textarea>
                </div>
                <div class="pt-5 border-t flex justify-end">
                    <button type="submit" class="btn-primary text-lg">Create Shop</button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php include 'templates/footer.php'; ?>