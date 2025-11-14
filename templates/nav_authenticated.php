<nav class="bg-white shadow-md">
    <div class="container mx-auto px-6 py-3">
        <div class="flex items-center justify-between">
            <div class="text-xl font-semibold text-gray-700">
                <a href="index.php" class="text-blue-600 hover:text-blue-800">QuickMed</a>
            </div>
            
            <div class="hidden md:flex items-center space-x-6">
                <a href="dashboard.php" class="text-gray-600 hover:text-blue-600">Dashboard</a>
                <a href="catalog.php" class="text-gray-600 hover:text-blue-600">Catalog</a>

                <?php if (has_role(ROLE_ADMIN) || has_role(ROLE_SHOP_ADMIN)): ?>
                    <a href="reports.php" class="text-gray-600 hover:text-blue-600">Reports</a>
                <?php endif; ?>

                <?php if (has_role(ROLE_ADMIN)): ?>
                    <a href="audit_log.php" class="text-gray-600 hover:text-blue-600">Audit Log</a>
                <?php endif; ?>
            </div>

            <div class="flex items-center">
                <?php if (has_role(ROLE_CUSTOMER)): ?>
                <a href="cart.php" class="text-gray-600 hover:text-blue-600 mr-4 relative">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">0</span>
                </a>
                <?php endif; ?>

                <span class="text-gray-700 mr-4 hidden sm:inline">Welcome, <?= e(explode(' ', $_SESSION['user_name'])[0]); ?>!</span>
                <a href="logout.php" class="px-4 py-2 bg-red-500 text-white text-sm font-medium rounded hover:bg-red-600">Logout</a>
            </div>
        </div>
    </div>
</nav>