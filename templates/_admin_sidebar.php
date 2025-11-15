<?php
// FILE: templates/_admin_sidebar.php (Final Version for Admin Panel)
// PURPOSE: A dedicated, fixed sidebar for the main Admin panel on desktop.

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="hidden lg:block w-64 bg-white border-r flex-shrink-0 fixed inset-y-0 left-0 z-40">
    <div class="flex flex-col h-full">
        <!-- Logo Section -->
        <div class="h-16 flex items-center justify-center border-b">
            <a href="index.php" class="flex items-center gap-2">
                <i class="fas fa-pills text-2xl text-teal-600"></i>
                <span class="font-bold text-xl text-slate-800">QuickMed</span>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-grow p-4 space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-md font-medium text-sm transition-colors <?= $current_page === 'dashboard.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-tachometer-alt w-5 text-center"></i>
                <span>Dashboard</span>
            </a>

            <!-- **NEW: User Management Link** -->
            <a href="users.php" class="flex items-center gap-3 px-4 py-2.5 rounded-md font-medium text-sm transition-colors <?= ($current_page === 'users.php' || $current_page === 'user_edit.php') ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-users-cog w-5 text-center"></i>
                <span>Manage Users</span>
            </a>
            
            <a href="medicines.php" class="flex items-center gap-3 px-4 py-2.5 rounded-md font-medium text-sm transition-colors <?= ($current_page === 'medicines.php' || $current_page === 'medicine_add.php') ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-capsules w-5 text-center"></i>
                <span>Manage Catalog</span>
            </a>
            
            <a href="orders.php" class="flex items-center gap-3 px-4 py-2.5 rounded-md font-medium text-sm transition-colors <?= $current_page === 'orders.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-receipt w-5 text-center"></i>
                <span>All Orders</span>
            </a>
            
            <a href="reports.php" class="flex items-center gap-3 px-4 py-2.5 rounded-md font-medium text-sm transition-colors <?= $current_page === 'reports.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-chart-bar w-5 text-center"></i>
                <span>Sales Reports</span>
            </a>
            
             <a href="audit_log.php" class="flex items-center gap-3 px-4 py-2.5 rounded-md font-medium text-sm transition-colors <?= $current_page === 'audit_log.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-history w-5 text-center"></i>
                <span>Audit Log</span>
            </a>
        </nav>

        <!-- User Profile & Logout Area -->
        <div class="p-4 border-t">
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex items-center space-x-3 p-2 rounded-md hover:bg-gray-100 transition-colors">
                    <img src="<?= e($_SESSION['user_image'] ?? 'assets/images/default_avatar.png') ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border">
                    <div class="flex-grow text-left">
                        <p class="text-sm font-semibold text-gray-800 truncate">Hi, <?= e(explode(' ', $_SESSION['user_name'])[0]) ?></p>
                        <p class="text-xs text-gray-500">Admin</p>
                    </div>
                    <i class="fas fa-chevron-up text-xs text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': !open }"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div x-show="open" @click.away="open = false" x-transition class="absolute bottom-full mb-2 w-full bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50" style="display: none;">
                    <div class="py-1">
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                        <div class="border-t my-1"></div>
                        <a href="logout.php" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>