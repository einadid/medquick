<?php
// FILE: templates/_salesman_sidebar.php (Final Version with working Dropdown)
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>

<!-- This sidebar is fixed on desktop and is not used on mobile anymore -->
<div class="hidden lg:block w-64 bg-white border-r flex-shrink-0 fixed inset-y-0 left-0 z-40">
    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="h-16 flex items-center justify-center border-b">
            <a href="index.php" class="flex items-center gap-2">
                <i class="fas fa-pills text-2xl text-teal-600"></i>
                <span class="font-bold text-xl text-slate-800">QuickMed</span>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-grow p-4 space-y-1 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-md font-medium text-sm transition-colors <?= $current_page === 'dashboard.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-tachometer-alt w-5 text-center"></i>
                <span>Dashboard</span>
            </a>
            <a href="pos.php" class="flex items-center gap-3 px-4 py-2.5 rounded-md font-medium text-sm transition-colors <?= $current_page === 'pos.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-cash-register w-5 text-center"></i>
                <span>Point of Sale</span>
            </a>
            <a href="my_sales.php" class="flex items-center gap-3 px-4 py-2.5 rounded-md font-medium text-sm transition-colors <?= $current_page === 'my_sales.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-chart-line w-5 text-center"></i>
                <span>My Sales Report</span>
            </a>
             <a href="returns.php" class="flex items-center gap-3 px-4 py-2.5 rounded-md font-medium text-sm transition-colors <?= $current_page === 'returns.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' ?>">
                <i class="fas fa-undo w-5 text-center"></i>
                <span>Process Return</span>
            </a>
        </nav>

        <!-- **UPDATED: User Profile Area with working Dropdown** -->
        <div class="p-4 border-t">
             <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="w-full flex items-center space-x-3 p-2 rounded-md hover:bg-gray-100 transition-colors">
                    <img src="<?= e($_SESSION['user_image'] ?? 'assets/images/default_avatar.png') ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border">
                    <div class="flex-grow text-left">
                        <p class="text-sm font-semibold text-gray-800 truncate">Hi, <?= e(explode(' ', $_SESSION['user_name'])[0]) ?></p>
                        <p class="text-xs text-gray-500">Salesman</p>
                    </div>
                    <i class="fas fa-chevron-up text-xs text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': !open }"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div x-show="open" 
                     @click.away="open = false" 
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute bottom-full mb-2 w-full bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50" 
                     style="display: none;">
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