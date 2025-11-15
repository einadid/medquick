<?php
// FILE: shop_process.php
require_once 'src/session.php'; require_once 'config/database.php';
if (!has_role(ROLE_ADMIN)) { redirect('dashboard.php'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('shops.php'); }
verify_csrf_token($_POST['csrf_token']);

$action = $_POST['action'] ?? '';
$shop_id = (int)($_POST['shop_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');

try {
    if ($action === 'create' && !empty($name) && !empty($address)) {
        $stmt = $pdo->prepare("INSERT INTO shops (name, address) VALUES (?, ?)");
        $stmt->execute([$name, $address]);
        $_SESSION['success_message'] = 'New shop created successfully.';
    } elseif ($action === 'update' && $shop_id > 0 && !empty($name) && !empty($address)) {
        $stmt = $pdo->prepare("UPDATE shops SET name = ?, address = ? WHERE id = ?");
        $stmt->execute([$name, $address, $shop_id]);
        $_SESSION['success_message'] = 'Shop details updated successfully.';
    } elseif ($action === 'delete' && $shop_id > 0) {
        // You might want to add checks here to prevent deleting a shop that has inventory or orders.
        $stmt = $pdo->prepare("DELETE FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $_SESSION['success_message'] = 'Shop deleted successfully.';
    } else {
        throw new Exception('Invalid action or missing data.');
    }
} catch (PDOException | Exception $e) {
    $_SESSION['error_message'] = "Operation failed: " . $e->getMessage();
}
redirect('shops.php');
?>