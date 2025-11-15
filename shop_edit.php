<?php
// FILE: shop_edit.php
require_once 'src/session.php'; require_once 'config/database.php';
if (!has_role(ROLE_ADMIN)) { redirect('dashboard.php'); }

$shop_id = (int)($_GET['id'] ?? 0);
if ($shop_id <= 0) { redirect('shops.php'); }

try {
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch();
    if (!$shop) { $_SESSION['error_message'] = "Shop not found."; redirect('shops.php'); }
} catch (PDOException $e) { $_SESSION['error_message'] = "Database error."; redirect('shops.php'); }

$pageTitle = "Edit Shop";
include 'templates/header.php';
?>
<main class="w-full">
    <div class="fade-in p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-slate-800">Edit Shop</h1>
            <a href="shops.php" class="text-sm font-medium text-teal-600 hover:underline">&larr; Back to Shop List</a>
        </div>
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md border">
            <form action="shop_process.php" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="shop_id" value="<?= e($shop['id']) ?>">
                <div>
                    <label for="name" class="block text-sm font-medium">Shop Name</label>
                    <input type="text" id="name" name="name" value="<?= e($shop['name']) ?>" required class="mt-1 w-full p-3 border rounded-md">
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium">Shop Address</label>
                    <textarea id="address" name="address" rows="4" required class="mt-1 w-full p-3 border rounded-md"><?= e($shop['address']) ?></textarea>
                </div>
                <div class="pt-5 border-t flex justify-end">
                    <button type="submit" class="btn-primary text-lg">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php include 'templates/footer.php'; ?>