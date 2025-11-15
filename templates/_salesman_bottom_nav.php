<?php
// FILE: templates/_salesman_bottom_nav.php (Final Version)
// PURPOSE: A dedicated bottom navigation bar for the Salesman role on mobile devices.

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="lg:hidden fixed bottom-0 left-0 right-0 h-[70px] bg-white border-t border-gray-200 shadow-[0_-2px_10px_rgba(0,0,0,0.05)] z-40">
    <div class="grid grid-cols-4 h-full">
        
        <!-- Dashboard Link -->
        <a href="dashboard.php" 
           class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 transition-colors bottom-nav-item <?= $current_page === 'dashboard.php' ? 'text-teal-600' : 'text-gray-500' ?>"
           aria-label="Dashboard">
            <i class="fas fa-tachometer-alt text-xl"></i>
            <span class="text-xs mt-1">Dashboard</span>
        </a>

        <!-- Point of Sale (POS) Link -->
        <a href="pos.php" 
           class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 transition-colors bottom-nav-item <?= $current_page === 'pos.php' ? 'text-teal-600' : 'text-gray-500' ?>"
           aria-label="Point of Sale">
            <i class="fas fa-cash-register text-xl"></i>
            <span class="text-xs mt-1">POS</span>
        </a>
        
        <!-- My Sales Report Link -->
        <a href="my_sales.php" 
           class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 transition-colors bottom-nav-item <?= $current_page === 'my_sales.php' ? 'text-teal-600' : 'text-gray-500' ?>"
           aria-label="My Sales Report">
            <i class="fas fa-chart-line text-xl"></i>
            <span class="text-xs mt-1">My Sales</span>
        </a>

        <!-- Returns Link -->
        <a href="returns.php" 
           class="flex flex-col items-center justify-center pt-2 hover:bg-gray-50 transition-colors bottom-nav-item <?= $current_page === 'returns.php' ? 'text-teal-600' : 'text-gray-500' ?>"
           aria-label="Process Return">
            <i class="fas fa-undo text-xl"></i>
            <span class="text-xs mt-1">Returns</span>
        </a>
        
    </div>
</div>