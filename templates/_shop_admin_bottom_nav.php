<?php
// FILE: templates/_shop_admin_bottom_nav.php (Final Version)
// PURPOSE: A dedicated bottom navigation bar for the Shop Admin role on mobile devices.

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="lg:hidden fixed bottom-0 left-0 right-0 h-[70px] bg-white border-t border-gray-200 shadow-[0_-2px_10px_rgba(0,0,0,0.05)] z-40">
    <div class="grid grid-cols-4 h-full">
        
        <!-- Dashboard Link -->
        <a href="dashboard.php" 
           class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 transition-colors <?= $current_page === 'dashboard.php' ? 'text-teal-600' : 'text-gray-500' ?>"
           aria-label="Dashboard">
            <i class="fas fa-tachometer-alt text-xl"></i>
            <span class="text-xs mt-1">Dashboard</span>
        </a>

        <!-- Add Stock Link -->
        <a href="inventory_add.php" 
           class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 transition-colors <?= $current_page === 'inventory_add.php' ? 'text-teal-600' : 'text-gray-500' ?>"
           aria-label="Add Stock">
            <i class="fas fa-plus-circle text-xl"></i>
            <span class="text-xs mt-1">Add Stock</span>
        </a>
        
        <!-- Manage Stock Link -->
        <a href="manage_stock.php" 
           class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 transition-colors <?= in_array($current_page, ['manage_stock.php', 'inventory_edit.php']) ? 'text-teal-600' : 'text-gray-500' ?>"
           aria-label="Manage Stock">
            <i class="fas fa-boxes text-xl"></i>
            <span class="text-xs mt-1">Manage</span>
        </a>

        <!-- Online Orders Link -->
        <a href="orders.php" 
           class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 transition-colors <?= ($current_page === 'orders.php' || $current_page === 'order_details.php') ? 'text-teal-600' : 'text-gray-500' ?>"
           aria-label="Online Orders">
            <i class="fas fa-dolly-flatbed text-xl"></i>
            <span class="text-xs mt-1">Orders</span>
        </a>
        
    </div>
</div>