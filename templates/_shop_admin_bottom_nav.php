<?php // FILE: templates/_shop_admin_bottom_nav.php (Final Version)
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="lg:hidden fixed bottom-0 left-0 right-0 h-[70px] bg-white border-t z-40">
    <div class="grid grid-cols-4 h-full">
        <a href="dashboard.php" class="flex flex-col items-center justify-center pt-2 <?= $current_page === 'dashboard.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-tachometer-alt text-xl"></i><span class="text-xs mt-1">Dashboard</span></a>
        <a href="inventory_add.php" class="flex flex-col items-center justify-center pt-2 <?= $current_page === 'inventory_add.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-plus text-xl"></i><span class="text-xs mt-1">Add Stock</span></a>
        <a href="manage_stock.php" class="flex flex-col items-center justify-center pt-2 <?= $current_page === 'manage_stock.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-boxes text-xl"></i><span class="text-xs mt-1">Manage</span></a>
        <a href="orders.php" class="flex flex-col items-center justify-center pt-2 <?= $current_page === 'orders.php' ? 'text-teal-600' : 'text-gray-500' ?>"><i class="fas fa-dolly-flatbed text-xl"></i><span class="text-xs mt-1">Orders</span></a>
    </div>
</div>