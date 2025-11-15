<?php
// FILE: addresses.php
// PURPOSE: Allows customers to manage their saved shipping addresses.

require_once 'src/session.php';
require_once 'config/database.php';

if (!is_logged_in() || !has_role(ROLE_CUSTOMER)) { redirect('login.php'); }

$user_id = $_SESSION['user_id'];
$pageTitle = "My Addresses";

// Fetch all addresses for the current user
try {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll();
} catch (PDOException $e) {
    $addresses = [];
    $_SESSION['error_message'] = "Could not load addresses.";
}

include 'templates/header.php';
?>

<div class="fade-in bg-slate-50 py-12" x-data="{ showForm: false }">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Navigation -->
            <?php include 'templates/_customer_sidebar.php'; ?>

            <!-- Main Content -->
            <div class="w-full lg:w-3/4">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-slate-800">My Addresses</h1>
                    <button @click="showForm = !showForm" class="btn-primary"><i class="fas fa-plus mr-2"></i> Add New Address</button>
                </div>
                
                <!-- Messages -->
                <?php if (isset($_SESSION['success_message'])): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($_SESSION['success_message']); ?></p></div><?php unset($_SESSION['success_message']); ?><?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= e($_SESSION['error_message']); ?></p></div><?php unset($_SESSION['error_message']); ?><?php endif; ?>

                <!-- Add New Address Form (collapsible) -->
                <div x-show="showForm" x-transition class="bg-white p-8 rounded-lg shadow-md border mb-8">
                    <h2 class="text-xl font-bold mb-4">Add a New Address</h2>
                    <form action="address_process.php" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="add">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label for="full_name" class="block text-sm font-medium">Full Name</label><input type="text" name="full_name" required class="mt-1 w-full p-2 border rounded-md"></div>
                            <div><label for="phone" class="block text-sm font-medium">Phone Number</label><input type="text" name="phone" required class="mt-1 w-full p-2 border rounded-md"></div>
                        </div>
                        <div><label for="address_line" class="block text-sm font-medium">Full Address</label><textarea name="address_line" rows="3" required class="mt-1 w-full p-2 border rounded-md"></textarea></div>
                        <div class="flex items-center"><input type="checkbox" name="is_default" value="1" class="h-4 w-4 rounded border-gray-300 text-teal-600"><label for="is_default" class="ml-2 block text-sm">Set as default address</label></div>
                        <div class="flex justify-end gap-4"><button type="button" @click="showForm = false" class="text-sm text-gray-600">Cancel</button><button type="submit" class="btn-primary">Save Address</button></div>
                    </form>
                </div>
                
                <!-- Saved Addresses Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (empty($addresses)): ?>
                        <p class="md:col-span-2 text-center text-gray-500 py-10">You have no saved addresses. Add one to get started!</p>
                    <?php else: foreach ($addresses as $address): ?>
                        <div class="bg-white p-6 rounded-lg shadow-md border relative">
                            <?php if ($address['is_default']): ?>
                                <div class="absolute top-4 right-4 bg-teal-100 text-teal-700 text-xs font-bold px-2 py-1 rounded-full">Default</div>
                            <?php endif; ?>
                            <p class="font-bold text-lg"><?= e($address['full_name']) ?></p>
                            <p class="text-sm text-gray-600"><?= e($address['phone']) ?></p>
                            <p class="text-sm text-gray-600 mt-2"><?= nl2br(e($address['address_line'])) ?></p>
                            <div class="mt-4 pt-4 border-t flex items-center gap-4 text-sm">
                                <!-- <a href="#" class="text-teal-600 hover:underline">Edit</a> -->
                                <form action="address_process.php" method="POST" onsubmit="return confirm('Are you sure?')"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="address_id" value="<?= e($address['id']) ?>"><button type="submit" class="text-red-500 hover:underline">Delete</button></form>
                                <?php if (!$address['is_default']): ?>
                                <form action="address_process.php" method="POST"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="set_default"><input type="hidden" name="address_id" value="<?= e($address['id']) ?>"><button type="submit" class="font-semibold text-gray-600 hover:text-teal-600">Set as Default</button></form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>