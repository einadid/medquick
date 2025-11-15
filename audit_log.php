<?php
require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

if (!has_role(ROLE_ADMIN)) {
    redirect('dashboard.php');
}

$logs = $pdo->query("
    SELECT al.*, u.full_name 
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.timestamp DESC
    LIMIT 100 
")->fetchAll();

$pageTitle = "System Audit Log";
include 'templates/header.php';
?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">System Audit Log</h1>
    <p class="text-gray-600 mb-6">Showing the last 100 critical actions performed in the system.</p>

    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach($logs as $log): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d M Y, H:i:s', strtotime($log['timestamp'])) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= e($log['full_name'] ?? 'System/Guest') ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-700"><?= e($log['action']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 truncate" title="<?= e($log['details']) ?>"><?= e($log['details']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= e($log['ip_address']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'templates/footer.php'; ?>