<?php
// FILE: inventory_edit.php
// PURPOSE: Allows Shop Admins to edit a specific inventory batch.

require_once 'src/session.php';
require_once 'config/database.php';

// Security: Only Shop Admins can access.
if (!has_role(ROLE_SHOP_ADMIN)) {
    redirect('dashboard.php');
}

$shop_id = $_SESSION['shop_id'];
$batch_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($batch_id <= 0) {
    redirect('dashboard.php');
}

// --- DATA FETCHING ---
try {
    // Fetch the specific batch, but ensure it belongs to the logged-in user's shop.
    $stmt = $pdo->prepare("
        SELECT ib.*, m.name as medicine_name, m.manufacturer
        FROM inventory_batches ib
        JOIN medicines m ON ib.medicine_id = m.id
        WHERE ib.id = ? AND ib.shop_id = ?
    ");
    $stmt->execute([$batch_id, $shop_id]);
    $batch = $stmt->fetch();

    // If batch doesn't exist or doesn't belong to this shop, redirect.
    if (!$batch) {
        $_SESSION['error_message'] = "Invalid inventory item or access denied.";
        redirect('dashboard.php');
    }
} catch (PDOException $e) {
    error_log("Inventory Edit fetch error: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred.";
    redirect('dashboard.php');
}

// --- FORM PROCESSING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    
    $new_quantity = (int)$_POST['quantity'];
    $new_price = (float)$_POST['price'];
    $new_expiry = trim($_POST['expiry_date']);

    // Basic validation
    if ($new_quantity < 0 || $new_price <= 0 || empty($new_expiry)) {
        $_SESSION['error_message'] = "Invalid data submitted. Please check all fields.";
        redirect("inventory_edit.php?id=$batch_id");
    }

    try {
        $update_stmt = $pdo->prepare("UPDATE inventory_batches SET quantity = ?, price = ?, expiry_date = ? WHERE id = ? AND shop_id = ?");
        $update_stmt->execute([$new_quantity, $new_price, $new_expiry, $batch_id, $shop_id]);

        log_audit($pdo, 'STOCK_UPDATED', "Batch ID: $batch_id, New Qty: $new_quantity, New Price: $new_price");
        $_SESSION['success_message'] = "Inventory item has been updated successfully.";
        redirect('dashboard.php');

    } catch (PDOException $e) {
        error_log("Inventory Edit update error: " . $e->getMessage());
        $_SESSION['error_message'] = "Could not update inventory item.";
        redirect("inventory_edit.php?id=$batch_id");
    }
}

$pageTitle = "Edit Inventory";
include 'templates/header.php';
?>
<div class="fade-in bg-slate-50 py-12">
    <div class="container mx-auto px-4 sm:px-6 max-w-2xl">
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Edit Inventory Item</h1>
        <p class="text-gray-600 mb-8">You are editing a batch for: <strong class="text-teal-600"><?= e($batch['medicine_name']) ?></strong></p>

        <?php if (isset($_SESSION['error_message'])): ?>
             <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?= e($_SESSION['error_message']); ?></p>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-lg shadow-md border">
            <form action="inventory_edit.php?id=<?= e($batch_id) ?>" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Medicine</label>
                    <p class="text-lg font-semibold mt-1"><?= e($batch['medicine_name']) ?> <span class="text-sm text-gray-500">(<?= e($batch['manufacturer']) ?>)</span></p>
                </div>

                <div>
                    <label for="batch_number" class="block text-sm font-medium text-gray-700">Batch Number</label>
                    <input type="text" id="batch_number" value="<?= e($batch['batch_number']) ?>" class="mt-1 block w-full bg-gray-100 rounded-md border-gray-300" disabled>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity (Units)</label>
                        <input type="number" name="quantity" id="quantity" value="<?= e($batch['quantity']) ?>" required min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Price per Unit (৳)</label>
                        <input type="number" name="price" id="price" value="<?= e($batch['price']) ?>" required step="0.01" min="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>

                <div>
                    <label for="expiry_date" class="block text-sm font-medium text-gray-700">Expiry Date</label>
                    <input type="date" name="expiry_date" id="expiry_date" value="<?= e($batch['expiry_date']) ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                </div>

                <div class="flex justify-end items-center gap-4 pt-4 border-t">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Cancel</a>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>
