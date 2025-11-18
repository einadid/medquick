<?php
$pageTitle = 'Pending Customer Registrations';
require_once '../includes/header.php';
requireRole('admin');

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $action = $_POST['action'];
    $registrationId = $_POST['registration_id'];
    
    if ($action === 'approve') {
        // Get registration details
        $registration = Database::getInstance()->fetchOne("
            SELECT * FROM pending_registrations WHERE id = ?
        ", [$registrationId]);
        
        if ($registration && $registration['status'] === 'pending') {
            // Create user account
            $roleId = Database::getInstance()->fetchOne("
                SELECT id FROM roles WHERE role_name = 'customer'
            ")['id'];
            
            $hashedPassword = password_hash($registration['default_password'], PASSWORD_BCRYPT);
            
            try {
                // Insert user
                $userId = Database::getInstance()->insert('users', [
                    'role_id' => $roleId,
                    'shop_id' => null,
                    'email' => $registration['email'],
                    'password' => $hashedPassword,
                    'full_name' => $registration['full_name'],
                    'phone' => $registration['phone'],
                    'address' => $registration['address'],
                    'member_id' => $registration['member_id'],
                    'status' => 'active'
                ]);
                
                // Award signup bonus
                Database::getInstance()->insert('loyalty_transactions', [
                    'user_id' => $userId,
                    'points' => SIGNUP_BONUS_POINTS,
                    'type' => 'earned',
                    'description' => 'Signup Bonus'
                ]);
                
                // Update registration status
                Database::getInstance()->query("
                    UPDATE pending_registrations 
                    SET status = 'approved', approved_by = ?, approved_at = NOW()
                    WHERE id = ?
                ", [$_SESSION['user_id'], $registrationId]);
                
                logAudit($_SESSION['user_id'], 'registration_approved', "Approved registration for {$registration['email']}");
                setFlash('success', 'Registration approved and account created');
                
            } catch (Exception $e) {
                setFlash('error', 'Failed to create account: ' . $e->getMessage());
            }
        }
        
    } elseif ($action === 'reject') {
        $reason = $_POST['reason'] ?? 'No reason provided';
        
        Database::getInstance()->query("
            UPDATE pending_registrations 
            SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW()
            WHERE id = ?
        ", [$reason, $_SESSION['user_id'], $registrationId]);
        
        logAudit($_SESSION['user_id'], 'registration_rejected', "Rejected registration #$registrationId");
        setFlash('success', 'Registration rejected');
    }
    
    redirect('/admin/pending-registrations.php');
}

// Toggle auto-approval
if (isset($_GET['toggle_auto'])) {
    $current = Database::getInstance()->fetchOne("
        SELECT setting_value FROM system_settings WHERE setting_key = 'auto_approve_registrations'
    ")['setting_value'];
    
    $newValue = $current == '1' ? '0' : '1';
    
    Database::getInstance()->query("
        UPDATE system_settings SET setting_value = ? WHERE setting_key = 'auto_approve_registrations'
    ", [$newValue]);
    
    setFlash('success', 'Auto-approval setting updated');
    redirect('/admin/pending-registrations.php');
}

// Get settings
$autoApprove = Database::getInstance()->fetchOne("
    SELECT setting_value FROM system_settings WHERE setting_key = 'auto_approve_registrations'
")['setting_value'] ?? '1';

// Get pending registrations
$pending = Database::getInstance()->fetchAll("
    SELECT pr.*, 
           s.full_name as salesman_name, 
           sh.name as shop_name
    FROM pending_registrations pr
    JOIN users s ON pr.salesman_id = s.id
    JOIN shops sh ON pr.shop_id = sh.id
    WHERE pr.status = 'pending'
    ORDER BY pr.created_at DESC
");

// Get recent approved/rejected
$recent = Database::getInstance()->fetchAll("
    SELECT pr.*, 
           s.full_name as salesman_name,
           a.full_name as approver_name
    FROM pending_registrations pr
    JOIN users s ON pr.salesman_id = s.id
    LEFT JOIN users a ON pr.approved_by = a.id
    WHERE pr.status IN ('approved', 'rejected')
    ORDER BY pr.approved_at DESC
    LIMIT 20
");
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold">Pending Customer Registrations</h2>
            <p class="text-gray-600">Approve or reject salesman-assisted registrations</p>
        </div>
        <div>
            <a href="?toggle_auto=1" class="px-6 py-3 <?php echo $autoApprove == '1' ? 'bg-green-600' : 'bg-gray-400'; ?> text-white font-bold">
                <?php echo $autoApprove == '1' ? '✓ AUTO-APPROVE: ON' : '⏸ AUTO-APPROVE: OFF'; ?>
            </a>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="bg-white border-2 border-gray-300 p-4">
        <div class="text-gray-600">Pending</div>
        <div class="text-3xl font-bold text-yellow-600"><?php echo count($pending); ?></div>
    </div>
    <div class="bg-white border-2 border-gray-300 p-4">
        <div class="text-gray-600">Auto-Approval</div>
        <div class="text-3xl font-bold <?php echo $autoApprove == '1' ? 'text-green-600' : 'text-red-600'; ?>">
            <?php echo $autoApprove == '1' ? 'ENABLED' : 'DISABLED'; ?>
        </div>
    </div>
    <div class="bg-white border-2 border-gray-300 p-4">
        <div class="text-gray-600">Total Processed</div>
        <div class="text-3xl font-bold text-blue-600"><?php echo count($recent); ?></div>
    </div>
</div>

<!-- Pending Registrations -->
<?php if (!empty($pending)): ?>
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h3 class="text-xl font-bold mb-4">⏳ Pending Approval (<?php echo count($pending); ?>)</h3>
    
    <?php foreach ($pending as $reg): ?>
    <div class="border-2 border-yellow-300 bg-yellow-50 p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <div class="font-bold text-lg mb-2"><?php echo clean($reg['full_name']); ?></div>
                <div class="space-y-1 text-sm">
                    <div><strong>Email:</strong> <?php echo clean($reg['email']); ?></div>
                    <div><strong>Phone:</strong> <?php echo clean($reg['phone']); ?></div>
                    <div><strong>Member ID:</strong> <code class="bg-white px-2 py-1 border"><?php echo clean($reg['member_id']); ?></code></div>
                    <div><strong>Default Password:</strong> <code class="bg-white px-2 py-1 border"><?php echo clean($reg['default_password']); ?></code></div>
                </div>
            </div>
            <div>
                <div class="space-y-1 text-sm">
                    <div><strong>Registered by:</strong> <?php echo clean($reg['salesman_name']); ?></div>
                    <div><strong>Shop:</strong> <?php echo clean($reg['shop_name']); ?></div>
                    <div><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($reg['created_at'])); ?></div>
                    <?php if ($reg['address']): ?>
                    <div><strong>Address:</strong> <?php echo clean($reg['address']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="flex gap-2">
            <form method="POST" class="flex-1" onsubmit="return confirm('Approve this registration?')">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                <button type="submit" class="w-full p-2 bg-green-600 text-white font-bold">
                    ✅ APPROVE & CREATE ACCOUNT
                </button>
            </form>
            
            <button onclick="showRejectModal(<?php echo $reg['id']; ?>, '<?php echo addslashes($reg['full_name']); ?>')" 
                    class="flex-1 p-2 bg-red-600 text-white font-bold">
                ❌ REJECT
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="bg-white border-2 border-gray-300 p-6 mb-4 text-center text-gray-600">
    No pending registrations
</div>
<?php endif; ?>

<!-- Recent Activity -->
<?php if (!empty($recent)): ?>
<div class="bg-white border-2 border-gray-300 p-6">
    <h3 class="text-xl font-bold mb-4">Recent Activity</h3>
    
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left border">Date</th>
                <th class="p-2 text-left border">Customer</th>
                <th class="p-2 text-left border">Email</th>
                <th class="p-2 text-left border">Member ID</th>
                <th class="p-2 text-left border">Salesman</th>
                <th class="p-2 text-left border">Status</th>
                <th class="p-2 text-left border">By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $reg): ?>
            <tr>
                <td class="p-2 border text-sm">
                    <?php echo date('M d, Y', strtotime($reg['approved_at'])); ?>
                </td>
                <td class="p-2 border"><?php echo clean($reg['full_name']); ?></td>
                <td class="p-2 border text-sm"><?php echo clean($reg['email']); ?></td>
                <td class="p-2 border">
                    <code class="bg-gray-100 px-2 py-1"><?php echo clean($reg['member_id']); ?></code>
                </td>
                <td class="p-2 border text-sm"><?php echo clean($reg['salesman_name']); ?></td>
                <td class="p-2 border">
                    <span class="px-2 py-1 text-xs <?php echo $reg['status'] === 'approved' ? 'bg-green-100' : 'bg-red-100'; ?>">
                        <?php echo strtoupper($reg['status']); ?>
                    </span>
                </td>
                <td class="p-2 border text-sm"><?php echo clean($reg['approver_name']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border-4 border-gray-300 p-6 max-w-md w-full m-4">
        <h3 class="text-xl font-bold mb-4">Reject Registration</h3>
        
        <div class="mb-4" id="rejectCustomerName"></div>
        
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="registration_id" id="rejectId">
            
            <div class="mb-4">
                <label class="block mb-2 font-bold">Reason for Rejection</label>
                <textarea name="reason" 
                          required 
                          rows="3" 
                          class="w-full p-2 border-2 border-gray-400" 
                          placeholder="Enter reason..."></textarea>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="flex-1 p-3 bg-red-600 text-white font-bold">
                    REJECT
                </button>
                <button type="button" onclick="closeRejectModal()" class="flex-1 p-3 bg-gray-400 text-white font-bold">
                    CANCEL
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal(id, name) {
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectCustomerName').textContent = 'Customer: ' + name;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?>