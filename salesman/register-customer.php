<?php
$pageTitle = 'Register Customer';
require_once '../includes/header.php';
requireRole('salesman');

$user = getCurrentUser();
$shopId = $user['shop_id'];

// Get auto-approve setting
$autoApprove = Database::getInstance()->fetchOne("
    SELECT setting_value FROM system_settings WHERE setting_key = 'auto_approve_registrations'
")['setting_value'] ?? '1';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    $errors = [];
    
    // Validate
    if (empty($fullName)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    // Check if email already exists in users table
    $existingUser = Database::getInstance()->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existingUser) {
        $errors[] = 'This email is already registered in the system';
    }
    
    // Check if email already in pending registrations
    $existingPending = Database::getInstance()->fetchOne("
        SELECT id FROM pending_registrations WHERE email = ? AND status = 'pending'
    ", [$email]);
    if ($existingPending) {
        $errors[] = 'This email already has a pending registration request';
    }
    
    if (empty($errors)) {
        // Generate member ID from email
        $username = strtolower(substr($email, 0, strpos($email, '@')));
        $memberId = preg_replace('/[^a-z0-9._-]/', '', $username);
        
        // Check for duplicate member IDs
        $checkMemberId = Database::getInstance()->fetchOne("
            SELECT COUNT(*) as count FROM users WHERE member_id = ?
        ", [$memberId])['count'];
        
        if ($checkMemberId > 0) {
            // Add number suffix
            $counter = 1;
            $originalMemberId = $memberId;
            while ($checkMemberId > 0) {
                $memberId = $originalMemberId . $counter;
                $checkMemberId = Database::getInstance()->fetchOne("
                    SELECT COUNT(*) as count FROM users WHERE member_id = ?
                ", [$memberId])['count'];
                $counter++;
                if ($counter > 100) break;
            }
        }
        
        if ($autoApprove == '1') {
            // AUTO-APPROVE: Create user directly
            require_once '../classes/Auth.php';
            
            // Get customer role ID
            $roleId = Database::getInstance()->fetchOne("
                SELECT id FROM roles WHERE role_name = 'customer'
            ")['id'];
            
            // Hash default password
            $hashedPassword = password_hash('newcustomer', PASSWORD_BCRYPT);
            
            // Insert user
            $userId = Database::getInstance()->insert('users', [
                'role_id' => $roleId,
                'shop_id' => null,
                'email' => $email,
                'password' => $hashedPassword,
                'full_name' => $fullName,
                'phone' => $phone,
                'address' => $address,
                'member_id' => $memberId,
                'status' => 'active'
            ]);
            
            // Award signup bonus
            Database::getInstance()->insert('loyalty_transactions', [
                'user_id' => $userId,
                'points' => SIGNUP_BONUS_POINTS,
                'type' => 'earned',
                'description' => 'Signup Bonus (Registered by Salesman)'
            ]);
            
            // Log the registration in pending table for record
            Database::getInstance()->insert('pending_registrations', [
                'salesman_id' => $_SESSION['user_id'],
                'shop_id' => $shopId,
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'member_id' => $memberId,
                'default_password' => 'newcustomer',
                'status' => 'approved',
                'approved_by' => $_SESSION['user_id'],
                'approved_at' => date('Y-m-d H:i:s')
            ]);
            
            logAudit($_SESSION['user_id'], 'customer_registered_offline', "Customer $email registered by salesman");
            
            $_SESSION['new_customer'] = [
                'name' => $fullName,
                'email' => $email,
                'member_id' => $memberId,
                'password' => 'newcustomer'
            ];
            
            redirect('/salesman/registration-success.php');
            
        } else {
            // MANUAL APPROVAL: Create pending registration
            Database::getInstance()->insert('pending_registrations', [
                'salesman_id' => $_SESSION['user_id'],
                'shop_id' => $shopId,
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'member_id' => $memberId,
                'default_password' => 'newcustomer',
                'status' => 'pending'
            ]);
            
            logAudit($_SESSION['user_id'], 'customer_registration_submitted', "Pending registration for $email");
            setFlash('success', 'Registration request submitted. Waiting for admin approval.');
            redirect('/salesman/register-customer.php');
        }
    }
}
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">Register New Customer</h2>
    <p class="text-gray-600">Help walk-in customers create their membership account</p>
</div>

<!-- Info Banner -->
<div class="bg-blue-50 border-2 border-blue-300 p-6 mb-4">
    <h3 class="font-bold mb-2">📝 Quick Customer Registration</h3>
    <ul class="text-sm space-y-1">
        <li>✓ Collect customer's basic information</li>
        <li>✓ Member ID will be auto-generated from email</li>
        <li>✓ Default password: <code class="bg-white px-2 py-1 border">newcustomer</code></li>
        <li>✓ Customer can change password later from their profile</li>
        <?php if ($autoApprove == '1'): ?>
        <li>✓ <strong class="text-green-600">Auto-Approval Active:</strong> Account created immediately</li>
        <?php else: ?>
        <li>⏳ <strong class="text-orange-600">Manual Approval:</strong> Admin must approve first</li>
        <?php endif; ?>
    </ul>
</div>

<!-- Registration Form -->
<div class="bg-white border-2 border-gray-300 p-6">
    <?php if (isset($errors) && !empty($errors)): ?>
    <div class="bg-red-100 border-2 border-red-400 text-red-700 p-4 mb-4">
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
                <li><?php echo clean($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <?php echo csrfField(); ?>
        
        <!-- Live Member ID Preview -->
        <div class="bg-gradient-to-r from-purple-50 to-blue-50 border-2 border-purple-300 p-4 mb-6">
            <div class="text-sm font-bold mb-2">💎 Member ID Preview:</div>
            <div class="text-center">
                <div class="text-3xl font-bold font-mono bg-white border-2 border-purple-400 px-4 py-3 rounded inline-block" id="memberIdPreview">
                    (Enter email below)
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block mb-2 font-bold">Customer Email Address *</label>
                <input type="email" 
                       name="email" 
                       id="emailInput"
                       required 
                       placeholder="customer@example.com"
                       class="w-full p-2 border-2 border-gray-400">
                <div class="text-sm text-gray-600 mt-1">
                    Member ID will be generated from email (part before @)
                </div>
            </div>
            
            <div>
                <label class="block mb-2 font-bold">Full Name *</label>
                <input type="text" 
                       name="full_name" 
                       required 
                       placeholder="John Doe"
                       class="w-full p-2 border-2 border-gray-400">
            </div>
            
            <div>
                <label class="block mb-2 font-bold">Phone Number *</label>
                <input type="text" 
                       name="phone" 
                       required 
                       placeholder="01XXXXXXXXX"
                       class="w-full p-2 border-2 border-gray-400">
            </div>
            
            <div class="md:col-span-2">
                <label class="block mb-2 font-bold">Address (Optional)</label>
                <textarea name="address" 
                          rows="2" 
                          placeholder="Customer's address"
                          class="w-full p-2 border-2 border-gray-400"></textarea>
            </div>
        </div>
        
        <!-- Default Credentials Info -->
        <div class="mt-6 p-4 bg-yellow-50 border-2 border-yellow-400">
            <div class="font-bold mb-2">🔑 Default Login Credentials:</div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-gray-600">Email:</div>
                    <div class="font-mono bg-white border px-2 py-1" id="emailPreview">customer@example.com</div>
                </div>
                <div>
                    <div class="text-gray-600">Password:</div>
                    <div class="font-mono bg-white border px-2 py-1">newcustomer</div>
                </div>
            </div>
            <div class="text-xs text-gray-600 mt-2">
                ⚠️ Customer should change password after first login
            </div>
        </div>
        
        <div class="mt-6">
            <button type="submit" class="w-full p-4 bg-green-600 text-white font-bold text-lg">
                <?php echo $autoApprove == '1' ? '✅ CREATE ACCOUNT NOW' : '📤 SUBMIT FOR APPROVAL'; ?>
            </button>
        </div>
    </form>
</div>

<!-- Recent Registrations -->
<?php
$recentRegistrations = Database::getInstance()->fetchAll("
    SELECT * FROM pending_registrations
    WHERE salesman_id = ?
    ORDER BY created_at DESC
    LIMIT 10
", [$_SESSION['user_id']]);
?>

<?php if (!empty($recentRegistrations)): ?>
<div class="bg-white border-2 border-gray-300 p-6 mt-4">
    <h3 class="text-xl font-bold mb-4">Recent Registrations</h3>
    
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left border">Date</th>
                <th class="p-2 text-left border">Name</th>
                <th class="p-2 text-left border">Email</th>
                <th class="p-2 text-left border">Member ID</th>
                <th class="p-2 text-left border">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentRegistrations as $reg): ?>
            <tr>
                <td class="p-2 border text-sm">
                    <?php echo date('M d, Y', strtotime($reg['created_at'])); ?>
                </td>
                <td class="p-2 border"><?php echo clean($reg['full_name']); ?></td>
                <td class="p-2 border text-sm"><?php echo clean($reg['email']); ?></td>
                <td class="p-2 border">
                    <code class="bg-gray-100 px-2 py-1"><?php echo clean($reg['member_id']); ?></code>
                </td>
                <td class="p-2 border">
                    <span class="px-2 py-1 text-xs <?php 
                        echo $reg['status'] === 'approved' ? 'bg-green-100 border-green-400' : 
                            ($reg['status'] === 'rejected' ? 'bg-red-100 border-red-400' : 'bg-yellow-100 border-yellow-400'); 
                    ?>">
                        <?php echo strtoupper($reg['status']); ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
// Live Member ID and Email Preview
document.getElementById('emailInput')?.addEventListener('input', function() {
    const email = this.value;
    document.getElementById('emailPreview').textContent = email || 'customer@example.com';
    
    if (email.includes('@')) {
        const username = email.split('@')[0];
        const cleanUsername = username.toLowerCase().replace(/[^a-z0-9._-]/g, '');
        document.getElementById('memberIdPreview').textContent = cleanUsername || '(invalid)';
    } else {
        document.getElementById('memberIdPreview').textContent = '(Enter valid email)';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>