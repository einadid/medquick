<?php
// FILE: templates/_customer_sidebar.php (Final Professional Version)
// PURPOSE: A clean and informative sidebar for the customer account section.

// This variable helps in highlighting the active menu item.
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>

<!-- 
    This sidebar is designed to be a content block within a two-column layout on desktop,
    and a simple block on mobile, rather than a fixed or off-canvas element.
-->
<div class="w-full lg:w-1/4 lg:pr-8">
    <div class="bg-white p-6 rounded-lg shadow-md border">
        
        <!-- User Profile Header Section -->
        <div class="flex items-center gap-4 mb-6 pb-6 border-b">
            <a href="profile.php" class="relative group" title="Edit Profile">
                <img src="<?= e($_SESSION['user_image'] ?? 'assets/images/default_avatar.png') ?>" alt="Avatar" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-md group-hover:ring-2 group-hover:ring-teal-300 transition-all">
                <span class="absolute bottom-0 right-0 block h-4 w-4 rounded-full bg-green-400 border-2 border-white"></span>
            </a>
            <div>
                <p class="font-bold text-lg text-slate-800 truncate" title="<?= e($_SESSION['user_name']) ?>"><?= e($_SESSION['user_name']) ?></p>
                <!-- Membership ID -->
                <p class="text-xs text-gray-500 mt-1">
                    Member ID: 
                    <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">
                        <?= e(explode('@', $_SESSION['user_email'])[0]) ?>
                    </span>
                </p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="space-y-2">
            <a href="dashboard.php" 
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= $current_page === 'dashboard.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100 hover:text-gray-900' ?>">
                <i class="fas fa-th-large w-5 text-center"></i>
                <span>Dashboard</span>
            </a>
            <a href="orders.php" 
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= ($current_page === 'orders.php' || $current_page === 'order_details.php') ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100 hover:text-gray-900' ?>">
                <i class="fas fa-receipt w-5 text-center"></i>
                <span>My Orders</span>
            </a>
            <a href="addresses.php" 
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= $current_page === 'addresses.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100 hover:text-gray-900' ?>">
                <i class="fas fa-map-marker-alt w-5 text-center"></i>
                <span>My Addresses</span>
            </a>
             <a href="profile.php" 
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors <?= $current_page === 'profile.php' ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-slate-100 hover:text-gray-900' ?>">
                <i class="fas fa-user-cog w-5 text-center"></i>
                <span>Profile Settings</span>
            </a>
            
            <!-- Separator -->
            <div class="pt-2 border-t mt-2 !mb-0"></div>

            <a href="logout.php" 
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors">
                <i class="fas fa-sign-out-alt w-5 text-center"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>
</div>