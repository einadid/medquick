<?php
$pageTitle = 'User Management';
require_once '../includes/header.php';
requireRole('admin');

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $action = $_POST['action'];
    $userId = $_POST['user_id'];
    
    switch ($action) {
        case 'ban':
            Database::getInstance()->query("UPDATE users SET status = 'banned' WHERE id = ?", [$userId]);
            logAudit($_SESSION['user_id'], 'user_banned', "User #$userId banned");
            setFlash('success', 'User banned successfully');
            break;
            
        case 'activate':
            Database::getInstance()->query("UPDATE users SET status = 'active' WHERE id = ?", [$userId]);
            logAudit($_SESSION['user_id'], 'user_activated', "User #$userId activated");
            setFlash('success', 'User activated successfully');
            break;
            
        case 'change_role':
            $newRoleId = $_POST['role_id'];
            Database::getInstance()->query("UPDATE users SET role_id = ? WHERE id = ?", [$newRoleId, $userId]);
            logAudit($_SESSION['user_id'], 'user_role_changed', "User #$userId role changed to role_id: $newRoleId");
            setFlash('success', 'Role changed successfully');
            break;
            
        case 'change_shop':
            $newShopId = $_POST['shop_id'] ?: null;
            Database::getInstance()->query("UPDATE users SET shop_id = ? WHERE id = ?", [$newShopId, $userId]);
            logAudit($_SESSION['user_id'], 'user_shop_changed', "User #$userId shop changed to shop_id: $newShopId");
            setFlash('success', 'Branch/Shop assigned successfully');
            break;
            
        case 'delete':
            Database::getInstance()->query("UPDATE users SET status = 'inactive' WHERE id = ?", [$userId]);
            logAudit($_SESSION['user_id'], 'user_deleted', "User #$userId deleted");
            setFlash('success', 'User deleted successfully');
            break;
    }
    
    redirect('/admin/users.php');
}

// Get filter values
$searchTerm = $_GET['search'] ?? '';
$filterRole = $_GET['role'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterShop = $_GET['shop'] ?? '';

// Build query with filters
$sql = "SELECT u.*, r.role_name, s.name as shop_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN shops s ON u.shop_id = s.id
        WHERE 1=1";

$params = [];

if ($searchTerm) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

if ($filterRole) {
    $sql .= " AND u.role_id = ?";
    $params[] = $filterRole;
}

if ($filterStatus) {
    $sql .= " AND u.status = ?";
    $params[] = $filterStatus;
}

if ($filterShop) {
    $sql .= " AND u.shop_id = ?";
    $params[] = $filterShop;
}

$sql .= " ORDER BY u.created_at DESC";

$users = Database::getInstance()->fetchAll($sql, $params);

// Get roles and shops for dropdowns
$roles = Database::getInstance()->fetchAll("SELECT * FROM roles");
$shops = Database::getInstance()->fetchAll("SELECT * FROM shops WHERE status = 'active' ORDER BY name");

// Get statistics
$totalUsers = count($users);
$activeUsers = count(array_filter($users, fn($u) => $u['status'] === 'active'));
$bannedUsers = count(array_filter($users, fn($u) => $u['status'] === 'banned'));
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">User Management</h2>
    <p class="text-gray-600">Manage all system users and assign branches</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
    <div class="bg-white border-2 border-gray-300 p-4">
        <div class="text-gray-600">Total Users</div>
        <div class="text-3xl font-bold text-blue-600"><?php echo $totalUsers; ?></div>
    </div>
    <div class="bg-white border-2 border-gray-300 p-4">
        <div class="text-gray-600">Active Users</div>
        <div class="text-3xl font-bold text-green-600"><?php echo $activeUsers; ?></div>
    </div>
    <div class="bg-white border-2 border-gray-300 p-4">
        <div class="text-gray-600">Banned Users</div>
        <div class="text-3xl font-bold text-red-600"><?php echo $bannedUsers; ?></div>
    </div>
    <div class="bg-white border-2 border-gray-300 p-4">
        <div class="text-gray-600">Customers</div>
        <div class="text-3xl font-bold text-purple-600">
            <?php echo count(array_filter($users, fn($u) => $u['role_name'] === 'customer')); ?>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h3 class="text-xl font-bold mb-4">Filter Users</h3>
    
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label class="block mb-2 font-bold">Search</label>
            <input type="text" 
                   name="search" 
                   value="<?php echo clean($searchTerm); ?>" 
                   placeholder="Name, email, phone..." 
                   class="w-full p-2 border-2 border-gray-400">
        </div>
        
        <div>
            <label class="block mb-2 font-bold">Role</label>
            <select name="role" class="w-full p-2 border-2 border-gray-400">
                <option value="">All Roles</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>" <?php echo $filterRole == $role['id'] ? 'selected' : ''; ?>>
                        <?php echo ucfirst(str_replace('_', ' ', $role['role_name'])); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block mb-2 font-bold">Status</label>
            <select name="status" class="w-full p-2 border-2 border-gray-400">
                <option value="">All Status</option>
                <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="banned" <?php echo $filterStatus === 'banned' ? 'selected' : ''; ?>>Banned</option>
                <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        
        <div>
            <label class="block mb-2 font-bold">Shop/Branch</label>
            <select name="shop" class="w-full p-2 border-2 border-gray-400">
                <option value="">All Shops</option>
                <?php foreach ($shops as $shop): ?>
                    <option value="<?php echo $shop['id']; ?>" <?php echo $filterShop == $shop['id'] ? 'selected' : ''; ?>>
                        <?php echo clean($shop['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block mb-2">&nbsp;</label>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 p-2 bg-blue-600 text-white font-bold">FILTER</button>
                <a href="users.php" class="px-3 p-2 bg-gray-400 text-white text-center font-bold">RESET</a>
            </div>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="bg-white border-2 border-gray-300 p-6">
    <h3 class="text-xl font-bold mb-4">All Users (<?php echo count($users); ?>)</h3>
    
    <?php if (empty($users)): ?>
        <div class="text-center text-gray-600 py-8">No users found matching your criteria</div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full border-2 border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left border">ID</th>
                    <th class="p-2 text-left border">Name</th>
                    <th class="p-2 text-left border">Email</th>
                    <th class="p-2 text-left border">Phone</th>
                    <th class="p-2 text-left border">Role</th>
                    <th class="p-2 text-left border">Shop/Branch</th>
                    <th class="p-2 text-left border">Status</th>
                    <th class="p-2 text-left border">Joined</th>
                    <th class="p-2 text-center border">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50">
                    <td class="p-2 border"><?php echo $user['id']; ?></td>
                    <td class="p-2 border font-bold"><?php echo clean($user['full_name']); ?></td>
                    <td class="p-2 border"><?php echo clean($user['email']); ?></td>
                    <td class="p-2 border"><?php echo clean($user['phone']); ?></td>
                    <td class="p-2 border">
                        <span class="px-2 py-1 text-xs bg-blue-100 border border-blue-400">
                            <?php echo strtoupper(str_replace('_', ' ', $user['role_name'])); ?>
                        </span>
                    </td>
                    <td class="p-2 border text-sm">
                        <?php if ($user['shop_name']): ?>
                            <span class="px-2 py-1 text-xs bg-green-100 border">
                                <?php echo clean($user['shop_name']); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-gray-400">No Shop</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-2 border">
                        <span class="px-2 py-1 text-xs <?php 
                            echo $user['status'] === 'active' ? 'bg-green-100 border-green-400' : 
                                ($user['status'] === 'banned' ? 'bg-red-100 border-red-400' : 'bg-gray-100 border-gray-400'); 
                        ?>">
                            <?php echo strtoupper($user['status']); ?>
                        </span>
                    </td>
                    <td class="p-2 border text-sm">
                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                    </td>
                    <td class="p-2 border text-center">
                        <button onclick="openManageModal(<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>)" 
                                class="px-3 py-1 bg-blue-600 text-white text-sm font-bold hover:bg-blue-700">
                            MANAGE
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Manage User Modal -->
<div id="manageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border-4 border-gray-300 p-6 max-w-lg w-full m-4 max-h-screen overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">Manage User</h3>
        
        <!-- User Info Display -->
        <div class="mb-4 p-4 bg-gray-50 border-2 border-gray-300">
            <div class="font-bold text-lg mb-2" id="modalUserName"></div>
            <div class="text-sm text-gray-600 mb-1">
                <strong>Email:</strong> <span id="modalUserEmail"></span>
            </div>
            <div class="text-sm text-gray-600 mb-1">
                <strong>Phone:</strong> <span id="modalUserPhone"></span>
            </div>
            <div class="text-sm text-gray-600 mb-1">
                <strong>Current Role:</strong> <span id="modalUserRole"></span>
            </div>
            <div class="text-sm text-gray-600 mb-1">
                <strong>Current Shop:</strong> <span id="modalUserShop"></span>
            </div>
            <div class="text-sm text-gray-600">
                <strong>Current Status:</strong> <span id="modalUserStatus"></span>
            </div>
        </div>
        
        <!-- Change Role -->
        <div class="mb-4 p-4 border-2 border-blue-300 bg-blue-50">
            <h4 class="font-bold mb-3">Change Role</h4>
            <form method="POST" onsubmit="return confirm('Are you sure you want to change this user\'s role?')">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="change_role">
                <input type="hidden" name="user_id" id="modalUserId1">
                
                <div class="flex gap-2">
                    <select name="role_id" id="modalRoleSelect" required class="flex-1 p-2 border-2 border-gray-400">
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $role['role_name'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-bold hover:bg-blue-700">
                        CHANGE
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Assign Shop/Branch (Staff Only) -->
        <div class="mb-4 p-4 border-2 border-green-300 bg-green-50" id="shopAssignSection">
            <h4 class="font-bold mb-3">🏪 Assign Shop/Branch</h4>
            <form method="POST" onsubmit="return confirm('Are you sure you want to change this user\'s branch?')">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="change_shop">
                <input type="hidden" name="user_id" id="modalUserId2">
                
                <div class="flex gap-2">
                    <select name="shop_id" id="modalShopSelect" class="flex-1 p-2 border-2 border-gray-400">
                        <option value="">No Shop</option>
                        <?php foreach ($shops as $shop): ?>
                            <option value="<?php echo $shop['id']; ?>">
                                <?php echo clean($shop['name']); ?> - <?php echo clean($shop['city']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white font-bold hover:bg-green-700">
                        ASSIGN
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Status Actions -->
        <div class="mb-4 p-4 border-2 border-yellow-300 bg-yellow-50">
            <h4 class="font-bold mb-3">Change Status</h4>
            <div class="grid grid-cols-2 gap-2">
                <form method="POST" onsubmit="return confirm('Are you sure you want to BAN this user?')">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="ban">
                    <input type="hidden" name="user_id" id="modalUserId3">
                    <button type="submit" class="w-full p-2 bg-red-600 text-white font-bold hover:bg-red-700">
                        🚫 BAN
                    </button>
                </form>
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to ACTIVATE this user?')">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="user_id" id="modalUserId4">
                    <button type="submit" class="w-full p-2 bg-green-600 text-white font-bold hover:bg-green-700">
                        ✅ ACTIVATE
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Delete Action -->
        <div class="mb-4 p-4 border-2 border-red-300 bg-red-50">
            <h4 class="font-bold mb-3 text-red-700">Danger Zone</h4>
            <form method="POST" onsubmit="return confirm('⚠️ Are you sure you want to DELETE this user?')">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="modalUserId5">
                <button type="submit" class="w-full p-2 bg-red-700 text-white font-bold hover:bg-red-800">
                    🗑️ DELETE USER
                </button>
            </form>
        </div>
        
        <!-- Close Button -->
        <button onclick="closeManageModal()" class="w-full p-3 bg-gray-400 text-white font-bold hover:bg-gray-500">
            CLOSE
        </button>
    </div>
</div>

<script>
function openManageModal(user) {
    // Populate user info
    document.getElementById('modalUserName').textContent = user.full_name;
    document.getElementById('modalUserEmail').textContent = user.email;
    document.getElementById('modalUserPhone').textContent = user.phone || 'N/A';
    document.getElementById('modalUserRole').textContent = user.role_name.replace('_', ' ').toUpperCase();
    document.getElementById('modalUserShop').textContent = user.shop_name || 'No Shop Assigned';
    document.getElementById('modalUserStatus').textContent = user.status.toUpperCase();
    
    // Set user IDs in all forms
    document.getElementById('modalUserId1').value = user.id;
    document.getElementById('modalUserId2').value = user.id;
    document.getElementById('modalUserId3').value = user.id;
    document.getElementById('modalUserId4').value = user.id;
    document.getElementById('modalUserId5').value = user.id;
    
    // Set current role and shop in selects
    document.getElementById('modalRoleSelect').value = user.role_id;
    document.getElementById('modalShopSelect').value = user.shop_id || '';
    
    // Show/hide shop assignment based on role (only for staff)
    const shopSection = document.getElementById('shopAssignSection');
    if (user.role_name === 'customer') {
        shopSection.style.display = 'none';
    } else {
        shopSection.style.display = 'block';
    }
    
    // Show modal
    document.getElementById('manageModal').classList.remove('hidden');
}

function closeManageModal() {
    document.getElementById('manageModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('manageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeManageModal();
    }
});

// Close with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeManageModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>