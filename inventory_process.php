<?php
// FILE: inventory_process.php
// PURPOSE: Handles backend actions for inventory management (like deleting a batch).

require_once 'src/session.php';
require_once 'config/database.php';

// Security: Only Shop Admins can perform these actions.
if (!has_role(ROLE_SHOP_ADMIN)) {
    redirect('dashboard.php');
}

// Security: POST requests only and CSRF check.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
verify_csrf_token($_POST['csrf_token']);

$shop_id = $_SESSION['shop_id'];
$action = $_POST['action'] ?? '';
$batch_id = isset($_POST['batch_id']) ? (int)$_POST['batch_id'] : 0;

if ($action === 'delete_batch' && $batch_id > 0) {
    try {
        // Prepare a statement to delete a batch, but ONLY if it belongs to the current user's shop.
        // This is a crucial security check.
        $stmt = $pdo->prepare("DELETE FROM inventory_batches WHERE id = ? AND shop_id = ?");
        $stmt->execute([$batch_id, $shop_id]);

        // Check if a row was actually deleted.
        if ($stmt->rowCount() > 0) {
            log_audit($pdo, 'STOCK_BATCH_DELETED', "Batch ID: $batch_id");
            $_SESSION['success_message'] = "Inventory batch has been successfully deleted.";
        } else {
            // This means the batch didn't exist or didn't belong to this shop.
            $_SESSION['error_message'] = "Could not delete batch. It may not exist or you don't have permission.";
        }
    } catch (PDOException $e) {
        error_log("Inventory delete error: " . $e->getMessage());
        $_SESSION['error_message'] = "A database error occurred. Could not delete the batch.";
    }
}

redirect('dashboard.php');
?>