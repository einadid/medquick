<?php
// FILE: templates/_admin_sidebar.php (Final Toggleable Version)
// PURPOSE: A responsive sidebar for the Admin panel. It's an overlay on all screen sizes, triggered by a button.

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>

<!-- 1. Backdrop (The dark overlay) -->
<!-- This appears when the sidebar is open and closes the sidebar when clicked. -->
<div x-show="sidebarOpen" 
     @click="sidebarOpen = false" 
     class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 transition-opacity duration-300 ease-linear" 
     x-transition:enter="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">
</div>

<!-- 2. The Sidebar itself -->
<!-- It's hidden off-screen to the left by default ('-translate-x-full') -->
<!-- When 'sidebarOpen' is true, the 'translate-x-0' class is applied, sliding it into view. -->
<div class="fixed inset-y-0 left-0 w-64 bg-white border-r z-50 flex flex-col transform -translate-x-full transition-transform duration-300 ease-in-out" 
     :class="{'translate-x-0': sidebarOpen}">
    
    <div class="flex flex-col h-full">
        <!-- Sidebar Header with Logo and Close Button -->
        <div class="h-[68px] flex items-center justify-between px-4 border-b flex-shrink-0">
            <a href="index.php" class="flex items-center gap-2">
                <i class="fas fa-pills text-2xl text-teal-600"></i>
                <span class="font-bold text-xl text-slate-800">QuickMed</span>
            </a>
            <!-- Close button (useful on mobile and desktop) -->
            <button @click="sidebarOpen = false" class="text-gray-500 hover:text-gray-800 p-2 rounded-full -mr-2">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-grow p-4 space-y-1 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= $current_page === 'dashboard.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100' ?>">
                <i class="fas fa-tachometer-alt w-5 text-center"></i><span>Dashboard</span>
            </a>
            <a href="users.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= in_array($current_page, ['users.php', 'user_edit.php', 'user_add.php']) ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100' ?>">
                <i class="fas fa-users-cog w-5 text-center"></i><span>Manage Users</span>
            </a>
            <a href="medicines.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= in_array($current_page, ['medicines.php', 'medicine_add.php', 'medicine_edit.php']) ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100' ?>">
                <i class="fas fa-capsules w-5 text-center"></i><span>Manage Catalog</span>
            </a>
            <a href="shops.php" class="flex items-center gap-3 px-4 py-2.5 rounded-md font-medium text-sm transition-colors <?= in_array($current_page, ['shops.php', 'shop_edit.php', 'shop_add.php']) ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100' ?>"><i class="fas fa-store-alt w-5 text-center"></i><span>Manage Shops</span></a>
            <a href="orders.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= ($current_page === 'orders.php' || $current_page === 'order_details.php') ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100' ?>">
                <i class="fas fa-receipt w-5 text-center"></i><span>All Orders</span>
            </a>
            <a href="reports.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= $current_page === 'reports.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100' ?>">
                <i class="fas fa-chart-bar w-5 text-center"></i><span>Sales Reports</span>
            </a>
             <a href="audit_log.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= $current_page === 'audit_log.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100' ?>">
                <i class="fas fa-history w-5 text-center"></i><span>Audit Log</span>
            </a>
        </nav>
        
        <!-- User Profile link at the bottom -->
        <div class="p-4 border-t">
             <a href="profile.php" class="w-full flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <img src="<?= e($_SESSION['user_image'] ?? 'assets/images/default_avatar.png') ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border">
                <div class="flex-grow text-left">
                    <p class="text-sm font-semibold text-gray-800 truncate"><?= e($_SESSION['user_name']) ?></p>
                    <p class="text-xs text-gray-500">View Profile & Settings</p>
                </div>
                <i class="fas fa-chevron-right text-xs text-gray-400"></i>
            </a>
        </div>
    </div>
</div>