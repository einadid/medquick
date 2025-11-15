<?php
// FILE: templates/dashboard_admin.php (Final Professional Version with Profit)
try { $shops = $pdo->query("SELECT id, name FROM shops ORDER BY name ASC")->fetchAll(); } catch (PDOException $e) { $shops = []; }
?>
<div class="fade-in bg-slate-50 py-10" x-data="adminDashboard()">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="mb-8"><h1 class="text-3xl font-bold text-slate-800">Admin Dashboard</h1><p class="text-gray-600">Full system overview and management panel.</p></div>
        
        <!-- Stats Cards with Profit -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-md border"><p class="text-sm font-medium text-gray-500">Today's Sales</p><p class="text-3xl font-bold text-slate-800 mt-1">৳<span class="counter" data-target="<?= (int)($stats['today_sales_amount'] ?? 0); ?>">0</span></p></div>
            <div class="bg-green-50 border-green-200 p-6 rounded-lg shadow-md border"><p class="text-sm font-medium text-green-800">Today's Profit</p><p class="text-3xl font-bold text-green-700 mt-1">৳<span class="counter" data-target="<?= (int)($stats['today_profit'] ?? 0); ?>">0</span></p></div>
            <div class="bg-white p-6 rounded-lg shadow-md border"><p class="text-sm font-medium text-gray-500">Total Stock</p><p class="text-3xl font-bold text-slate-800 mt-1 counter" data-target="<?= (int)($stats['total_stock'] ?? 0); ?>">0</p></div>
            <div class="bg-white p-6 rounded-lg shadow-md border"><p class="text-sm font-medium text-gray-500">Medicines</p><p class="text-3xl font-bold text-slate-800 mt-1 counter" data-target="<?= (int)($stats['total_medicines'] ?? 0); ?>">0</p></div>
            <div class="bg-white p-6 rounded-lg shadow-md border"><p class="text-sm font-medium text-gray-500">Total Users</p><p class="text-3xl font-bold text-slate-800 mt-1 counter" data-target="<?= (int)($stats['total_users'] ?? 0); ?>">0</p></div>
        </div>

        <!-- Sales Chart -->
        <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md border mb-8"><h2 class="text-2xl font-bold text-slate-800 mb-6">Last 7 Days Sales (All Shops)</h2><div class="h-80"><canvas id="salesChart"></canvas></div></div>
        
        <!-- User Management Table -->
        <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md border">
             <h2 class="text-2xl font-bold text-slate-800 mb-6">User Management</h2>
             <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shop</th><th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                         <?php foreach ($all_users as $u): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-900"><?= e($u['full_name']) ?></div><div class="text-xs text-gray-500"><?= e($u['email']) ?></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($u['id'] === $_SESSION['user_id'] || $u['role'] === 'admin'): ?>
                                        <span class="font-semibold"><?= e(ucfirst(str_replace('_', ' ', $u['role']))) ?></span>
                                    <?php else: ?>
                                        <select data-user-id="<?= e($u['id']) ?>" data-original-role="<?= e($u['role']) ?>" @change="updateRole(<?= e($u['id']) ?>, $event.target.value, $event)" class="rounded-md border-gray-300 shadow-sm text-sm focus:ring-teal-500 focus:border-teal-500">
                                            <option value="customer" <?= $u['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                            <option value="salesman" <?= $u['role'] === 'salesman' ? 'selected' : '' ?>>Salesman</option>
                                            <option value="shop_admin" <?= $u['role'] === 'shop_admin' ? 'selected' : '' ?>>Shop Admin</option>
                                        </select>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" id="shop-name-<?= e($u['id']) ?>"><?= e($u['shop_name'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $u['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?><a href="user_edit.php?id=<?= e($u['id']) ?>" class="text-teal-600 hover:text-teal-900">Edit</a><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
             </div>
        </div>
    </div>
    <!-- Assign Shop Modal -->
    <div x-show="assignShopModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;"><div @click="closeModal" x-show="assignShopModal.open" x-transition class="absolute inset-0 bg-gray-500 bg-opacity-75"></div><div x-show="assignShopModal.open" x-transition class="relative bg-white p-8 rounded-lg shadow-xl w-full max-w-md"><h3 class="text-xl font-bold mb-4">Assign Shop</h3><p class="text-sm mb-6">Please assign a shop for this user role.</p><select x-model="assignShopModal.selectedShop" class="w-full p-3 border rounded-md"><option value="">-- Select a Shop --</option><?php foreach($shops as $shop): ?><option value="<?= e($shop['id']) ?>"><?= e($shop['name']) ?></option><?php endforeach; ?></select><div class="mt-6 flex justify-end gap-4"><button @click="cancelRoleChange" class="text-sm text-gray-600">Cancel</button><button @click="confirmShopAssignment" class="btn-primary">Assign & Save</button></div></div></div>
</div>