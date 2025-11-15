<?php
// FILE: audit_log.php
// PURPOSE: Allows Admins to view a log of critical system actions.

require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

// 1. SECURITY: This page is for Admins only.
if (!has_role(ROLE_ADMIN)) {
    redirect('dashboard.php');
}

// 2. DATA FETCHING: Get the most recent audit log entries from the database.
try {
    // We join with the users table to get the full name of the user who performed the action.
    // A LEFT JOIN is used so that even if a user is deleted or the action was performed by the system (user_id is NULL),
    // the log entry will still be shown.
    $logs = $pdo->query("
        SELECT al.id, al.timestamp, al.action, al.details, al.ip_address, u.full_name, u.role
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.timestamp DESC
        LIMIT 200 
    ")->fetchAll(); // Limit to the most recent 200 entries for performance.

} catch (PDOException $e) {
    $logs = [];
    $db_error = "Could not fetch audit log data. Please check the database connection.";
    error_log("Audit Log page: could not fetch logs. " . $e->getMessage());
}

$pageTitle = "System Audit Log";
include 'templates/header.php';
?>

<!-- HTML for the Audit Log Viewer page -->
<div class="container mx-auto p-4 md:p-6">
    <h1 class="text-3xl font-bold mb-4">System Audit Log</h1>
    <p class="text-gray-600 mb-6">Showing the last 200 critical actions performed in the system. All times are in server time.</p>

    <!-- Display Database Error if any -->
    <?php if (isset($db_error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-center" role="alert">
            <p><?= e($db_error); ?></p>
        </div>
    <?php else: ?>
    <!-- Log Table -->
    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center py-10 text-gray-500">No audit log entries found.</td></tr>
                <?php else: foreach ($logs as $log): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono"><?= date('d M Y, h:i:s A', strtotime($log['timestamp'])) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php if ($log['full_name']): ?>
                            <span><?= e($log['full_name']) ?></span>
                            <span class="text-xs text-gray-500">(<?= e($log['role']) ?>)</span>
                        <?php else: ?>
                            <span class="text-gray-400 italic">System/Guest</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?php 
                                switch($log['action']) {
                                    case 'USER_LOGIN': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'CUSTOMER_ORDER': echo 'bg-green-100 text-green-800'; break;
                                    case 'POS_SALE': echo 'bg-teal-100 text-teal-800'; break;
                                    case 'MEDICINE_ADDED': echo 'bg-purple-100 text-purple-800'; break;
                                    case 'STOCK_ADDED': echo 'bg-yellow-100 text-yellow-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                            ?>">
                            <?= e($log['action']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate" title="<?= e($log['details']) ?>"><?= e($log['details']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono"><?= e($log['ip_address']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>