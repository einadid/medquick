<?php // FILE: wallet.php
require_once 'src/session.php'; require_once 'config/database.php';
if (!is_logged_in() || !has_role(ROLE_CUSTOMER)) { redirect('login.php'); }

$user_id = $_SESSION['user_id'];
$pageTitle = "My Health Wallet";

try {
    $user_stmt = $pdo->prepare("SELECT points_balance FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $points_balance = $user_stmt->fetchColumn();

    // Fetch transaction history (simplified)
    $earned_stmt = $pdo->prepare("SELECT id, created_at, total_amount, points_earned FROM orders WHERE customer_id = ? AND points_earned > 0 ORDER BY created_at DESC");
    $earned_stmt->execute([$user_id]);
    $earned_history = $earned_stmt->fetchAll();

    $used_stmt = $pdo->prepare("SELECT id, created_at, total_amount, points_used FROM orders WHERE customer_id = ? AND points_used > 0 ORDER BY created_at DESC");
    $used_stmt->execute([$user_id]);
    $used_history = $used_stmt->fetchAll();
} catch (PDOException $e) { /* Error Handling */ }

include 'templates/header.php';
?>
<div class="fade-in bg-slate-50 py-12">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="flex flex-col lg:flex-row gap-8">
            <?php include 'templates/_customer_sidebar.php'; ?>
            <div class="w-full lg:w-3/4">
                <h1 class="text-3xl font-bold text-slate-800 mb-4">My Health Wallet</h1>
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-8 rounded-xl shadow-lg mb-8">
                    <p class="text-lg text-emerald-100">Current Balance</p>
                    <p class="text-5xl font-extrabold"><?= number_format($points_balance ?? 0) ?> <span class="text-3xl font-medium">Points</span></p>
                    <p class="mt-1 text-emerald-200">(Equivalent to ৳<?= number_format($points_balance ?? 0) ?>)</p>
                </div>
                
                <h2 class="text-2xl font-bold text-slate-800 mb-6">Transaction History</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Points Earned -->
                    <div class="bg-white p-6 rounded-lg shadow-md border"><h3 class="font-bold text-lg mb-4 text-green-700">Points Earned</h3><div class="space-y-3">
                        <?php foreach($earned_history as $h): ?>
                        <div class="flex justify-between text-sm"><p>From Order #<?= e($h['id']) ?></p><p class="font-bold text-green-600">+<?= e($h['points_earned']) ?></p></div>
                        <?php endforeach; ?>
                    </div></div>
                    <!-- Points Used -->
                    <div class="bg-white p-6 rounded-lg shadow-md border"><h3 class="font-bold text-lg mb-4 text-red-700">Points Used</h3><div class="space-y-3">
                         <?php foreach($used_history as $h): ?>
                        <div class="flex justify-between text-sm"><p>On Order #<?= e($h['id']) ?></p><p class="font-bold text-red-600">-<?= e($h['points_used']) ?></p></div>
                        <?php endforeach; ?>
                    </div></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>