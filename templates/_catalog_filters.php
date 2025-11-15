<?php
// FILE: templates/_catalog_filters.php (Final & Professional Version)
// PURPOSE: A reusable template part for the medicine catalog's filter form.
// This form is used on both desktop (inline) and mobile (off-canvas).
?>
<form id="filter-form" method="GET" action="catalog.php">
    <!-- 
        This form has two layouts:
        - A grid for desktop view.
        - A simple vertical stack for the mobile off-canvas menu.
    -->
    <div class="space-y-6 md:space-y-0 md:grid md:grid-cols-2 lg:grid-cols-5 md:gap-4">
        
        <!-- Search Input -->
        <div class="lg:col-span-2">
            <label for="search" class="block text-sm font-medium text-gray-700 md:sr-only">Search by Name</label>
            <input type="text" name="search" id="search" value="<?= e($search_term) ?>" placeholder="Search by name or manufacturer..." class="mt-1 w-full p-2 border rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
        </div>
        
        <!-- Availability Filter -->
        <div>
            <label for="availability" class="block text-sm font-medium text-gray-700 md:sr-only">Availability</label>
            <select name="availability" id="availability" class="mt-1 w-full p-2 border rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="all" <?= ($filter_availability == 'all') ? 'selected' : '' ?>>All Availability</option>
                <option value="in_stock" <?= ($filter_availability == 'in_stock') ? 'selected' : '' ?>>In Stock Only</option>
                <option value="out_of_stock" <?= ($filter_availability == 'out_of_stock') ? 'selected' : '' ?>>Out of Stock</option>
            </select>
        </div>

        <!-- Category Filter -->
        <div>
            <label for="category" class="block text-sm font-medium text-gray-700 md:sr-only">Category</label>
            <select name="category" id="category" class="mt-1 w-full p-2 border rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= ($filter_category == $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Sort Order -->
        <div>
            <label for="sort" class="block text-sm font-medium text-gray-700 md:sr-only">Sort By</label>
            <select name="sort" id="sort" class="mt-1 w-full p-2 border rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="name_asc" <?= ($sort_order == 'name_asc') ? 'selected' : '' ?>>Sort: Name (A-Z)</option>
                <option value="name_desc" <?= ($sort_order == 'name_desc') ? 'selected' : '' ?>>Sort: Name (Z-A)</option>
                <option value="price_asc" <?= ($sort_order == 'price_asc') ? 'selected' : '' ?>>Sort: Price (Low-High)</option>
                <option value="price_desc" <?= ($sort_order == 'price_desc') ? 'selected' : '' ?>>Sort: Price (High-Low)</option>
            </select>
        </div>
    </div>
    
    <!-- Form Action Buttons -->
     <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end items-center gap-4">
        <a href="catalog.php" class="w-full sm:w-auto text-center text-sm text-gray-600 hover:text-teal-600 font-medium">
            Reset All Filters
        </a>
        <button type="submit" class="w-full sm:w-auto btn-primary py-2 px-8">
            Apply Filters
        </button>
     </div>
</form>