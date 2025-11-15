<?php
// FILE: medicines.php
// PURPOSE: Admin panel to manage all medicines in the catalog.

require_once 'src/session.php';
require_once 'config/database.php';

if (!has_role(ROLE_ADMIN)) { redirect('dashboard.php'); }

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf_token($_POST['csrf_token']);
    $med_id_to_delete = (int)$_POST['medicine_id'];
    if ($med_id_to_delete > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
            $stmt->execute([$med_id_to_delete]);
            log_audit($pdo, 'MEDICINE_DELETED', "Medicine ID: $med_id_to_delete");
            $_SESSION['success_message'] = "Medicine has been deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Could not delete medicine. It might be in use in existing orders or inventory.";
        }
    }
    redirect('medicines.php');
}

$all_medicines = $pdo->query("SELECT * FROM medicines ORDER BY name ASC")->fetchAll();
$pageTitle = "Manage Medicines";
include 'templates/header.php';
?>
<div class="fade-in bg-slate-50 py-12">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-slate-800">Manage Medicines</h1>
            <a href="medicine_add.php" class="btn-primary"><i class="fas fa-plus mr-2"></i> Add New Medicine</a>
        </div>
        <!-- Messages -->
        <?php if (isset($_SESSION['success_message'])): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?= e($_SESSION['success_message']); ?></p></div><?php unset($_SESSION['success_message']); ?><?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?= e($_SESSION['error_message']); ?></p></div><?php unset($_SESSION['error_message']); ?><?php endif; ?>
        
        <div class="bg-white p-6 rounded-lg shadow-md border">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Manufacturer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($all_medicines as $med): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= e($med['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= e($med['manufacturer']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= e($med['category']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                    <a href="#" class="text-teal-600 hover:text-teal-900">Edit</a>
                                    <form action="medicines.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this medicine? This action cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="medicine_id" value="<?= e($med['id']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>