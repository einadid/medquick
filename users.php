<?php
// FILE: users.php (Final Version with Advanced Filters)
require_once 'src/session.php';
require_once 'config/database.php';

if (!has_role(ROLE_ADMIN)) { redirect('dashboard.php'); }

$pageTitle = "Manage Users";

// --- 1. Get Filter Values from URL ---
$search = trim($_GET['search'] ?? '');
$filter_role = trim($_GET['role'] ?? '');
$filter_status = trim($_GET['status'] ?? ''); // 'active', 'inactive', or ''

try {
    // --- 2. Build SQL Query with Filters ---
    $sql = "SELECT u.id, u.full_name, u.email, u.role, u.is_active, s.name as shop_name 
            FROM users u 
            LEFT JOIN shops s ON u.shop_id = s.id
            WHERE 1=1"; // Start with a true condition to easily append AND clauses
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    if (!empty($filter_role)) {
        $sql .= " AND u.role = ?";
        $params[] = $filter_role;
    }
    if ($filter_status === 'active') {
        $sql .= " AND u.is_active = 1";
    } elseif ($filter_status === 'inactive') {
        $sql .= " AND u.is_active = 0";
    }

    $sql .= " ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all_users = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("User management page error: " . $e->getMessage());
    $all_users = [];
    $_SESSION['error_message'] = "Could not fetch user data.";
}

include 'templates/header.php';
?>

<div class="fade-in p-4 sm:p-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
        <h1 class="text-3xl font-bold text-slate-800">User Management</h1>
        <a href="user_add.php" class="btn-primary w-full sm:w-auto text-center"><i class="fas fa-user-plus mr-2"></i> Add New User</a>
    </div>
    
    <!-- Session Messages -->
    <?php if(isset($_SESSION['success_message'])) { echo '<div class="bg-green-100 text-green-700 p-4 mb-6 rounded-md">'.e($_SESSION['success_message']).'</div>'; unset($_SESSION['success_message']); } ?>
    <?php if(isset($_SESSION['error_message'])) { echo '<div class="bg-red-100 text-red-700 p-4 mb-6 rounded-md">'.e($_SESSION['error_message']).'</div>'; unset($_SESSION['error_message']); } ?>
    
    <!-- User Table and Filters -->
    <div class="bg-white p-6 rounded-lg shadow-md border">
        <!-- **NEW: Advanced Filter Form** -->
        <form method="GET" action="users.php" class="mb-6 pb-6 border-b">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label for="search" class="sr-only">Search</label>
                    <input type="text" name="search" id="search" value="<?= e($search) ?>" placeholder="Search by name or email..." class="p-2 border rounded-md w-full">
                </div>
                <div>
                    <label for="role" class="sr-only">Filter by Role</label>
                    <select name="role" id="role" class="p-2 border rounded-md w-full">
                        <option value="">All Roles</option>
                        <option value="customer" <?= $filter_role === 'customer' ? 'selected' : '' ?>>Customer</option>
                        <option value="salesman" <?= $filter_role === 'salesman' ? 'selected' : '' ?>>Salesman</option>
                        <option value="shop_admin" <?= $filter_role === 'shop_admin' ? 'selected' : '' ?>>Shop Admin</option>
                        <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="sr-only">Filter by Status</label>
                     <select name="status" id="status" class="p-2 border rounded-md w-full">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-4">
                <a href="users.php" class="text-sm text-gray-600 hover:text-teal-600 self-center">Reset Filters</a>
                <button type="submit" class="btn-primary py-2 px-6">Filter Users</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if(empty($all_users)): ?>
                        <tr><td colspan="5" class="text-center py-10 text-gray-500">No users found matching your criteria.</td></tr>
                    <?php else: foreach ($all_users as $u): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4"><div class="font-medium text-gray-900"><?= e($u['full_name']) ?></div><div class="text-sm text-gray-500"><?= e($u['email']) ?></div></td>
                            <td class="px-6 py-4 text-sm"><?= e(ucfirst(str_replace('_', ' ', $u['role']))) ?></td>
                            <td class="px-6 py-4 text-sm"><?= e($u['shop_name'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4 text-center"><span class="px-2 py-1 text-xs font-semibold rounded-full <?= $u['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                            <td class="px-6 py-4 text-right text-sm font-medium space-x-4">
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <a href="user_edit.php?id=<?= e($u['id']) ?>" class="text-teal-600 hover:text-teal-900">Edit</a>
                                    <form action="user_process.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to change this user\'s status?');"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="user_id" value="<?= e($u['id']) ?>"><input type="hidden" name="action" value="<?= $u['is_active'] ? 'deactivate' : 'activate' ?>"><button type="submit" class="text-blue-600 hover:underline"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'templates/footer.php'; ?>