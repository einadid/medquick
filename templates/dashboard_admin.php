<?php
// FILE: templates/dashboard_admin.php (Final Version with Integrated User Table)
?>
<div class="fade-in p-4 sm:p-6 space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Admin Dashboard</h1>
        <p class="text-gray-600">Welcome back! Here is the complete overview of your system.</p>
    </div>

    <!-- KPI Cards with Profit -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-lg border"><p class="text-sm font-medium text-gray-500">Today's Sales</p><p class="text-2xl font-bold mt-1">৳<span class="counter" data-target="<?= (int)($stats['today_sales'] ?? 0); ?>">0</span></p></div>
        <div class="bg-green-50 border-green-200 p-6 rounded-xl shadow-lg border"><p class="text-sm font-medium text-green-800">Today's Profit</p><p class="text-2xl font-bold text-green-700 mt-1">৳<span class="counter" data-target="<?= (int)($stats['today_profit'] ?? 0); ?>">0</span></p></div>
        <div class="bg-white p-6 rounded-lg shadow-md border"><p class="text-sm font-medium text-gray-500">Total Stock</p><p class="text-2xl font-bold mt-1 counter" data-target="<?= (int)($stats['total_stock'] ?? 0); ?>">0</p></div>
        <div class="bg-white p-6 rounded-lg shadow-md border"><p class="text-sm font-medium text-gray-500">Medicines</p><p class="text-2xl font-bold mt-1 counter" data-target="<?= (int)($stats['total_medicines'] ?? 0); ?>">0</p></div>
        <div class="bg-white p-6 rounded-lg shadow-md border"><p class="text-sm font-medium text-gray-500">Total Users</p><p class="text-2xl font-bold mt-1 counter" data-target="<?= (int)($stats['total_users'] ?? 0); ?>">0</p></div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md border"><h2 class="text-xl font-bold text-slate-800 mb-4">Sales Trend (Last 30 Days)</h2><div class="h-80"><canvas id="salesOverTimeChart"></canvas></div></div>
        <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md border"><h2 class="text-xl font-bold text-slate-800 mb-4">Sales by Shop</h2><div class="h-80 flex items-center justify-center"><canvas id="salesByShopChart"></canvas></div></div>
    </div>
    
    <!-- **UPDATED: User Management Table is now integrated into the dashboard** -->
    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-md border">
         <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-slate-800">System Users</h2>
            <a href="users.php" class="btn-primary"><i class="fas fa-users-cog mr-2"></i> Advanced Manage</a>
        </div>
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
                     <?php if (empty($all_users)): ?>
                        <tr><td colspan="5" class="text-center py-10">No users found.</td></tr>
                     <?php else: foreach ($all_users as $u): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><div class="font-medium text-gray-900"><?= e($u['full_name']) ?></div><div class="text-sm text-gray-500"><?= e($u['email']) ?></div></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= e(ucfirst(str_replace('_', ' ', $u['role']))) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= e($u['shop_name'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center"><span class="px-2 py-1 text-xs font-semibold rounded-full <?= $u['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <a href="user_edit.php?id=<?= e($u['id']) ?>" class="text-teal-600 hover:text-teal-900">Edit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
         </div>
    </div>
</div>

<!-- Chart.js scripts (same as before) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    
document.addEventListener('DOMContentLoaded', () => {
    // Sales Over Time Line Chart
    const lineCtx = document.getElementById('salesOverTimeChart');
    if (lineCtx) {
        new Chart(lineCtx, { type: 'line', data: { labels: <?= $chart_labels_json ?>, datasets: [{ label: 'Sales', data: <?= $chart_data_json ?>, backgroundColor: "rgba(13, 148, 136, 0.1)", borderColor: "#0D9488", tension: 0.4, fill: true }] }, options: { responsive: true, maintainAspectRatio: false, scales: {y:{beginAtZero:true}}, plugins:{legend:{display:false}} } });
    }

    // Sales by Shop Pie Chart
    const pieCtx = document.getElementById('salesByShopChart');
    if (pieCtx) {
        new Chart(pieCtx, { type: 'pie', data: { labels: <?= $pie_chart_labels_json ?>, datasets: [{ data: <?= $pie_chart_data_json ?>, backgroundColor: ['#0D9488', '#0F766E', '#14B8A6', '#5EEAD4', '#99F6E4'], hoverOffset: 4 }] }, options: { responsive: true, maintainAspectRatio: false } });
    }
});
</script>