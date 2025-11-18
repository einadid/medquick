<?php
$pageTitle = 'Verification Codes';
require_once '../includes/header.php';
requireRole('admin');

// Generate new code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_code'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $roleId = $_POST['role_id'];
    $shopId = $_POST['shop_id'] ?: null;
    $count = (int)$_POST['count'];
    
    $role = Database::getInstance()->fetchOne("SELECT role_name FROM roles WHERE id = ?", [$roleId]);
    $prefix = substr($role['role_name'], 0, 3);
    
    for ($i = 0; $i < $count; $i++) {
        $code = generateVerificationCode($prefix, $role['role_name']);
        Database::getInstance()->insert('signup_codes', [
            'code' => $code,
            'role_id' => $roleId,
            'shop_id' => $shopId,
            'used' => 0
        ]);
    }
    
    logAudit($_SESSION['user_id'], 'codes_generated', "$count codes generated for role #$roleId");
    setFlash('success', "$count verification code(s) generated");
    redirect('/admin/codes.php');
}

// Get all codes
$codes = Database::getInstance()->fetchAll("
    SELECT sc.*, r.role_name, s.name as shop_name
    FROM signup_codes sc
    JOIN roles r ON sc.role_id = r.id
    LEFT JOIN shops s ON sc.shop_id = s.id
    ORDER BY sc.created_at DESC
");

// Get roles and shops
$roles = Database::getInstance()->fetchAll("SELECT * FROM roles WHERE role_name != 'customer'");
$shops = Database::getInstance()->fetchAll("SELECT * FROM shops WHERE status = 'active'");
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">Verification Code Management</h2>
    <p class="text-gray-600">Generate one-time codes for staff registration</p>
</div>

<!-- Generate Codes Form -->
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h3 class="text-xl font-bold mb-4">Generate New Codes</h3>
    
    <form method="POST">
        <?php echo csrfField(); ?>
        <input type="hidden" name="generate_code" value="1">
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block mb-2 font-bold">Role *</label>
                <select name="role_id" required class="w-full p-2 border-2 border-gray-400">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>"><?php echo ucfirst(str_replace('_', ' ', $role['role_name'])); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block mb-2 font-bold">Shop (Optional)</label>
                <select name="shop_id" class="w-full p-2 border-2 border-gray-400">
                    <option value="">Not Assigned</option>
                    <?php foreach ($shops as $shop): ?>
                        <option value="<?php echo $shop['id']; ?>"><?php echo clean($shop['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block mb-2 font-bold">Quantity *</label>
                <input type="number" name="count" value="1" min="1" max="10" required class="w-full p-2 border-2 border-gray-400">
            </div>
            
            <div>
                <label class="block mb-2">&nbsp;</label>
                <button type="submit" class="w-full p-2 bg-green-600 text-white font-bold">GENERATE</button>
            </div>
        </div>
    </form>
</div>

<!-- Codes Table -->
<div class="bg-white border-2 border-gray-300 p-6">
    <h3 class="text-xl font-bold mb-4">All Codes</h3>
    
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left border">Code</th>
                <th class="p-2 text-left border">Role</th>
                <th class="p-2 text-left border">Shop</th>
                <th class="p-2 text-left border">Status</th>
                <th class="p-2 text-left border">Used By</th>
                <th class="p-2 text-left border">Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($codes as $code): ?>
            <tr>
                <td class="p-2 border">
                    <code class="bg-gray-100 px-2 py-1 font-mono"><?php echo $code['code']; ?></code>
                </td>
                <td class="p-2 border"><?php echo ucfirst(str_replace('_', ' ', $code['role_name'])); ?></td>
                <td class="p-2 border"><?php echo $code['shop_name'] ?? 'N/A'; ?></td>
                <td class="p-2 border">
                    <?php if ($code['used']): ?>
                        <span class="px-2 py-1 text-xs bg-red-100 border-red-400">USED</span>
                    <?php else: ?>
                        <span class="px-2 py-1 text-xs bg-green-100 border-green-400">AVAILABLE</span>
                    <?php endif; ?>
                </td>
                <td class="p-2 border text-sm"><?php echo $code['used_by'] ?? '-'; ?></td>
                <td class="p-2 border text-sm"><?php echo date('M d, Y', strtotime($code['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>