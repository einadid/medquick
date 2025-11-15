<?php
// FILE: templates/home_view.php
// PURPOSE: The main view for the homepage. It displays different content based on whether the user is logged in
// and what their role is (e.g., public visitor, admin, shop admin).
?>

<?php if (!is_logged_in() || has_role(ROLE_CUSTOMER)): ?>
<!-- ======================================================== -->
<!-- == PUBLIC & CUSTOMER VIEW: Landing Page & E-Commerce  == -->
<!-- ======================================================== -->

<div class="fade-in">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-blue-50 via-teal-50 to-white pt-16 pb-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-6xl font-extrabold text-slate-800 leading-tight">
                Your Health, Delivered <span class="text-teal-600">Fast.</span>
            </h1>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-gray-600">
                The most reliable online pharmacy and inventory system. Find your medicines, place an order, or manage your shop with ease.
            </p>
            <div class="mt-8 max-w-2xl mx-auto relative">
                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" id="main-search" class="w-full p-4 pl-14 border-2 border-gray-200 rounded-full shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-colors" placeholder="Search for medicines, e.g., Napa, Seclo...">
                <div id="search-suggestions" class="absolute top-full left-0 right-0 bg-white text-left mt-2 rounded-lg shadow-lg z-10 hidden border"></div>
            </div>
        </div>
    </section>

    <!-- Quick Stats Banners -->
    <section class="-mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-6 text-center">
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300"><i class="fas fa-pills text-3xl text-blue-500 mb-2"></i><p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_medicines'] ?? 0) ?>">0</p><p class="text-sm text-gray-500 mt-1">Medicines</p></div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300"><i class="fas fa-boxes-stacked text-3xl text-green-500 mb-2"></i><p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_stock'] ?? 0) ?>">0</p><p class="text-sm text-gray-500 mt-1">Stock Units</p></div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300"><i class="fas fa-users text-3xl text-purple-500 mb-2"></i><p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_users'] ?? 0) ?>">0</p><p class="text-sm text-gray-500 mt-1">Users</p></div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300"><i class="fas fa-exclamation-triangle text-3xl text-orange-500 mb-2"></i><p class="text-3xl font-bold text-orange-600 counter" data-target="<?= e($stats['low_stock_count'] ?? 0) ?>">0</p><p class="text-sm text-gray-500 mt-1">Low Stock</p></div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300"><i class="fas fa-hourglass-end text-3xl text-red-500 mb-2"></i><p class="text-3xl font-bold text-red-600 counter" data-target="<?= e($stats['expiring_soon_count'] ?? 0) ?>">0</p><p class="text-sm text-gray-500 mt-1">Expiring Soon</p></div>
            </div>
        </div>
    </section>

    <!-- Featured Medicines Carousel -->
    <section class="py-16 sm:py-20">
        <div class="container mx-auto px-4" data-carousel>
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-slate-800">Featured Medicines</h2>
                <div class="hidden sm:flex items-center space-x-2">
                    <button data-carousel-prev class="bg-slate-200 hover:bg-slate-300 text-slate-700 w-10 h-10 rounded-full flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed transition"><i class="fas fa-chevron-left"></i></button>
                    <button data-carousel-next class="bg-slate-200 hover:bg-slate-300 text-slate-700 w-10 h-10 rounded-full flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed transition"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            <div data-carousel-container class="flex overflow-x-auto scroll-smooth snap-x snap-mandatory scrollbar-hide -mx-2 pb-4">
                <?php foreach ($featured_medicines as $med): ?>
                    <div data-carousel-item class="flex-none w-3/4 sm:w-1/3 md:w-1/4 lg:w-1/6 px-2 snap-start">
                        <div class="bg-white border rounded-lg shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden group h-full flex flex-col">
                            <a href="medicine_details.php?id=<?= e($med['id']) ?>" class="block p-4 flex-grow"><img src="<?= e($med['image_path'] ?? 'assets/images/default_med.png'); ?>" alt="<?= e($med['name']); ?>" class="w-full h-32 object-contain mb-4 transform group-hover:scale-105 transition-transform" loading="lazy"></a>
                            <div class="p-4 border-t bg-slate-50/50">
                                <h3 class="font-semibold text-sm truncate" title="<?= e($med['name']); ?>"><?= e($med['name']); ?></h3>
                                <p class="text-xs text-gray-500 mb-3"><?= e($med['manufacturer']); ?></p>
                                <div class="flex justify-between items-center">
                                    <p class="text-lg font-bold text-teal-600">৳<?= e(number_format($med['price'], 2)) ?></p>
                                    <button class="add-to-cart-btn bg-teal-100 text-teal-700 hover:bg-teal-600 hover:text-white text-xs font-bold px-3 py-1.5 rounded-full transition-colors" data-id="<?= e($med['id']); ?>" data-name="<?= e($med['name']); ?>" data-price="<?= e($med['price']); ?>">Add</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Quick Navigation CTA -->
    <section class="bg-white py-16 sm:py-20 border-t">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-10 text-slate-800">How Can We Help?</h2>
            <div class="max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="catalog.php" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5"><i class="fas fa-th-large text-4xl text-teal-500 group-hover:text-white mb-4 transition-colors"></i><h3 class="font-semibold text-lg">Browse Catalog</h3><p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">See all available medicines.</p></a>
                <a href="<?= is_logged_in() ? 'orders.php' : 'login.php' ?>" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5"><i class="fas fa-receipt text-4xl text-green-500 group-hover:text-white mb-4 transition-colors"></i><h3 class="font-semibold text-lg">My Orders</h3><p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">Track your order history.</p></a>
                <a href="#" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5"><i class="fas fa-map-marker-alt text-4xl text-red-500 group-hover:text-white mb-4 transition-colors"></i><h3 class="font-semibold text-lg">Shop Locator</h3><p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">Find our physical stores.</p></a>
                <a href="<?= is_logged_in() ? 'profile.php' : 'login.php' ?>" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5"><i class="fas fa-user-circle text-4xl text-purple-500 group-hover:text-white mb-4 transition-colors"></i><h3 class="font-semibold text-lg">My Account</h3><p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">Login or view your profile.</p></a>
            </div>
        </div>
    </section>
</div>

<?php elseif (has_role(ROLE_ADMIN)): ?>
<!-- =================================================== -->
<!-- ====== ADMIN VIEW: High-Level Dashboard ========= -->
<!-- =================================================== -->
<div class="fade-in bg-slate-50 py-10">
    <div class="container mx-auto px-4 sm:px-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-800">Welcome, Admin!</h1>
            <p class="text-gray-600">This is the main system overview. Go to your <a href="<?= base_url('dashboard.php') ?>" class="text-teal-600 font-semibold hover:underline">main dashboard</a> for management panels.</p>
        </div>
        <!-- Stats cards are the same as the public view, but you could add more admin-specific ones here -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-6 text-center">
            <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80"><i class="fas fa-pills text-3xl text-blue-500 mb-2"></i><p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_medicines'] ?? 0) ?>">0</p><p class="text-sm text-gray-500 mt-1">Medicines</p></div>
            <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80"><i class="fas fa-boxes-stacked text-3xl text-green-500 mb-2"></i><p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_stock'] ?? 0) ?>">0</p><p class="text-sm text-gray-500 mt-1">Stock Units</p></div>
            <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80"><i class="fas fa-users text-3xl text-purple-500 mb-2"></i><p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_users'] ?? 0) ?>">0</p><p class="text-sm text-gray-500 mt-1">Users</p></div>
            <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80"><i class="fas fa-exclamation-triangle text-3xl text-orange-500 mb-2"></i><p class="text-3xl font-bold text-orange-600 counter" data-target="<?= e($stats['low_stock_count'] ?? 0) ?>">0</p><p class="text-sm text-gray-500 mt-1">Low Stock</p></div>
            <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80"><i class="fas fa-hourglass-end text-3xl text-red-500 mb-2"></i><p class="text-3xl font-bold text-red-600 counter" data-target="<?= e($stats['expiring_soon_count'] ?? 0) ?>">0</p><p class="text-sm text-gray-500 mt-1">Expiring Soon</p></div>
        </div>
        <div class="text-center mt-10">
            <a href="<?= base_url('dashboard.php') ?>" class="btn-primary">Go to Full Dashboard</a>
        </div>
    </div>
</div>

<?php else: ?>
    <?php
    // For any other logged-in role (Shop Admin, Salesman), redirect them to their specific dashboard
    // as this homepage is not designed for them.
    redirect('dashboard.php');
    ?>
<?php endif; ?>