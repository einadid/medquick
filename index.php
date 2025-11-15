<?php
// FILE: index.php (Professional Homepage)
// PURPOSE: The main homepage of QuickMed, showcasing key features and data.

require_once 'src/session.php';
require_once 'config/database.php';

// Redirect logged-in users (except Admin/Customer) to their dashboard.
if (is_logged_in() && !has_role(ROLE_ADMIN) && !has_role(ROLE_CUSTOMER)) {
    redirect('dashboard.php');
}

$pageTitle = "QuickMed - Your Health, Delivered Fast";

// --- Data Fetching for the Homepage ---
try {
    // 1. Stats for the banners
    $stats = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM medicines) as total_medicines,
            (SELECT COALESCE(SUM(quantity), 0) FROM inventory_batches) as total_stock,
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(DISTINCT m.id) 
             FROM medicines m 
             JOIN inventory_batches ib ON m.id = ib.medicine_id 
             WHERE ib.quantity > 0 AND ib.quantity < m.reorder_level
             ) as low_stock_count,
            (SELECT COUNT(*) 
             FROM inventory_batches 
             WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND quantity > 0
            ) as expiring_soon_count
    ")->fetch();

    // 2. Featured/Recommended Medicines
    $featured_medicines = $pdo->query("
        SELECT 
            m.id, m.name, m.manufacturer, m.image_path,
            MIN(ib.price) as price 
        FROM medicines m
        JOIN inventory_batches ib ON m.id = ib.medicine_id
        WHERE ib.quantity > 0 AND ib.expiry_date > CURDATE()
        GROUP BY m.id
        ORDER BY SUM(ib.quantity) DESC
        LIMIT 12
    ")->fetchAll();

} catch (PDOException $e) {
    error_log("Homepage DB Error: " . $e->getMessage());
    $stats = ['total_medicines' => 'N/A', 'total_stock' => 'N/A', 'total_users' => 'N/A', 'low_stock_count' => 'N/A', 'expiring_soon_count' => 'N/A'];
    $featured_medicines = [];
}

include 'templates/header.php';
?>

<div class="fade-in">
    <!-- ================== HERO SECTION ================== -->
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

    <!-- ============ QUICK STATS BANNERS ============= -->
    <section class="-mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-6 text-center">
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300">
                    <i class="fas fa-pills text-3xl text-blue-500 mb-2"></i>
                    <p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_medicines'] ?? 0) ?>">0</p>
                    <p class="text-sm text-gray-500 mt-1">Medicines</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300">
                    <i class="fas fa-boxes-stacked text-3xl text-green-500 mb-2"></i>
                    <p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_stock'] ?? 0) ?>">0</p>
                    <p class="text-sm text-gray-500 mt-1">Stock Units</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300">
                    <i class="fas fa-users text-3xl text-purple-500 mb-2"></i>
                    <p class="text-3xl font-bold text-slate-800 counter" data-target="<?= e($stats['total_users'] ?? 0) ?>">0</p>
                    <p class="text-sm text-gray-500 mt-1">Users</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300">
                    <i class="fas fa-exclamation-triangle text-3xl text-orange-500 mb-2"></i>
                    <p class="text-3xl font-bold text-orange-600 counter" data-target="<?= e($stats['low_stock_count'] ?? 0) ?>">0</p>
                    <p class="text-sm text-gray-500 mt-1">Low Stock</p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-200/80 transform hover:-translate-y-2 transition-transform duration-300">
                    <i class="fas fa-hourglass-end text-3xl text-red-500 mb-2"></i>
                    <p class="text-3xl font-bold text-red-600 counter" data-target="<?= e($stats['expiring_soon_count'] ?? 0) ?>">0</p>
                    <p class="text-sm text-gray-500 mt-1">Expiring Soon</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ======== FEATURED MEDICINES CAROUSEL ========= -->
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
                <?php if (empty($featured_medicines)): ?>
                    <p class="text-gray-500 px-2">No featured medicines available at the moment.</p>
                <?php else: foreach ($featured_medicines as $med): ?>
                    <div data-carousel-item class="flex-none w-3/4 sm:w-1/3 md:w-1/4 lg:w-1/6 px-2 snap-start">
                        <div class="bg-white border rounded-lg shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden group h-full flex flex-col">
                            <a href="medicine_details.php?id=<?= e($med['id']) ?>" class="block p-4 flex-grow">
                                <img src="<?= e($med['image_path'] ?? 'assets/images/default_med.png'); ?>" alt="<?= e($med['name']); ?>" class="w-full h-32 object-contain mb-4 transform group-hover:scale-105 transition-transform" loading="lazy">
                            </a>
                            <div class="p-4 border-t bg-slate-50/50">
                                <h3 class="font-semibold text-sm truncate" title="<?= e($med['name']); ?>"><?= e($med['name']); ?></h3>
                                <p class="text-xs text-gray-500 mb-3"><?= e($med['manufacturer']); ?></p>
                                <div class="flex justify-between items-center">
                                    <p class="text-lg font-bold text-teal-600">৳<?= e(number_format($med['price'], 2)) ?></p>
                                    <?php if (!is_logged_in() || has_role(ROLE_CUSTOMER)): ?>
                                    <button class="add-to-cart-btn bg-teal-100 text-teal-700 hover:bg-teal-600 hover:text-white text-xs font-bold px-3 py-1.5 rounded-full transition-colors"
                                        data-id="<?= e($med['id']); ?>"
                                        data-name="<?= e($med['name']); ?>"
                                        data-price="<?= e($med['price']); ?>">
                                        Add
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </section>

    <!-- =========== QUICK NAVIGATION CTA ============= -->
    <section class="bg-white py-16 sm:py-20 border-t">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-10 text-slate-800">How Can We Help?</h2>
            <div class="max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="catalog.php" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5">
                    <i class="fas fa-th-large text-4xl text-teal-500 group-hover:text-white mb-4 transition-colors"></i>
                    <h3 class="font-semibold text-lg">Browse Catalog</h3>
                    <p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">See all available medicines.</p>
                </a>
                <a href="<?= is_logged_in() ? 'orders.php' : 'login.php' ?>" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5">
                    <i class="fas fa-receipt text-4xl text-green-500 group-hover:text-white mb-4 transition-colors"></i>
                    <h3 class="font-semibold text-lg">My Orders</h3>
                    <p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">Track your order history.</p>
                </a>
                <a href="#" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5">
                    <i class="fas fa-map-marker-alt text-4xl text-red-500 group-hover:text-white mb-4 transition-colors"></i>
                    <h3 class="font-semibold text-lg">Shop Locator</h3>
                    <p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">Find our physical stores.</p>
                </a>
                <a href="<?= is_logged_in() ? 'dashboard.php' : 'login.php' ?>" class="group block bg-slate-50/80 p-6 rounded-lg shadow-sm text-center hover:bg-teal-600 hover:text-white hover:shadow-xl transition-all transform hover:-translate-y-1.5">
                    <i class="fas fa-user-circle text-4xl text-purple-500 group-hover:text-white mb-4 transition-colors"></i>
                    <h3 class="font-semibold text-lg">My Account</h3>
                    <p class="text-sm text-gray-500 group-hover:text-teal-100 transition-colors">Login to your dashboard.</p>
                </a>
            </div>
        </div>
    </section>
</div>
<?php
include 'templates/footer.php';
?>