<?php
// FILE: templates/_salesman_sidebar.php (Final Toggleable Version)
// PURPOSE: A responsive, off-canvas sidebar for the Salesman panel.

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>

<!-- 1. Backdrop (The dark overlay) for the off-canvas menu -->
<div x-show="sidebarOpen" 
     @click="sidebarOpen = false" 
     class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 transition-opacity duration-300 ease-linear lg:hidden" 
     x-transition:enter="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">
</div>

<!-- 
    2. The Sidebar itself.
    On large screens (lg), it's a permanent, fixed sidebar.
    On smaller screens, it's hidden off-screen and slides in when 'sidebarOpen' is true.
-->
<div class="fixed inset-y-0 left-0 w-64 bg-white border-r z-50 flex-shrink-0 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out" 
     :class="{'translate-x-0': sidebarOpen}">
    
    <div class="flex flex-col h-full">
        <!-- Sidebar Header with Logo and Close Button -->
        <div class="h-[68px] flex items-center justify-between px-4 border-b flex-shrink-0">
            <a href="index.php" class="flex items-center gap-2">
                <i class="fas fa-pills text-2xl text-teal-600"></i>
                <span class="font-bold text-xl text-slate-800">QuickMed</span>
            </a>
            <!-- Close button for mobile -->
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-500 hover:text-gray-800 p-2 rounded-full -mr-2">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-grow p-4 space-y-1 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= $current_page === 'dashboard.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100' ?>">
                <i class="fas fa-tachometer-alt w-5 text-center"></i>
                <span>Dashboard</span>
            </a>
            <a href="pos.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= $current_page === 'pos.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100' ?>">
                <i class="fas fa-cash-register w-5 text-center"></i>
                <span>Point of Sale</span>
            </a>
            <a href="my_sales.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= $current_page === 'my_sales.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100' ?>">
                <i class="fas fa-chart-line w-5 text-center"></i>
                <span>My Sales Report</span>
            </a>
             <a href="returns.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= $current_page === 'returns.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100' ?>">
                <i class="fas fa-undo w-5 text-center"></i>
                <span>Process Return</span>
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