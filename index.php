<?php
$pageTitle = "Welcome to QuickMed";
require_once 'src/session.php';
require_once 'config/database.php';
include 'templates/header.php';

// Fetch data for dashboard cards (admin/shop_admin view)
$totalMedicines = $pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
$totalStock = $pdo->query("SELECT SUM(quantity) FROM inventory_batches")->fetchColumn();
// etc...

// Fetch featured medicines
$featured_stmt = $pdo->query("SELECT * FROM medicines LIMIT 8");
$featured_medicines = $featured_stmt->fetchAll();
?>

<!-- Search & Filter Box -->
<div class="bg-blue-600 text-white p-8 rounded-lg shadow-lg mb-8 text-center">
    <h1 class="text-4xl font-bold mb-4">Your Health, Delivered Fast.</h1>
    <div class="max-w-2xl mx-auto relative">
        <input type="text" id="main-search" class="w-full p-4 rounded-full text-gray-800 focus:outline-none focus:ring-2 focus:ring-yellow-400" placeholder="Search for medicines, manufacturers...">
        <div id="search-suggestions" class="absolute top-full left-0 right-0 bg-white text-black mt-1 rounded-md shadow-lg z-10 hidden"></div>
    </div>
</div>

<!-- Customer View: Featured Medicines Carousel -->
<?php if (!is_logged_in() || has_role(ROLE_CUSTOMER)): ?>
<div class="mb-8">
    <h2 class="text-2xl font-bold mb-4">Featured Products</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
        <?php foreach ($featured_medicines as $med): ?>
        <div class="bg-white p-4 rounded-lg shadow-md hover:shadow-xl hover:scale-105 transition-transform">
            <img src="<?= e($med['image_path'] ?? 'assets/images/default_med.png'); ?>" alt="<?= e($med['name']); ?>" class="w-full h-32 object-contain mb-4" loading="lazy">
            <h3 class="font-bold text-sm"><?= e($med['name']); ?></h3>
            <p class="text-xs text-gray-500 mb-2"><?= e($med['manufacturer']); ?></p>
            <!-- Price would be fetched from inventory, this is a simplification -->
            <button class="add-to-cart-btn w-full bg-blue-500 text-white text-sm py-2 rounded mt-2 hover:bg-blue-600" data-id="<?= e($med['id']); ?>" data-name="<?= e($med['name']); ?>">Add to Cart</button>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Quick Navigation CTA Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <!-- Card 1 -->
    <a href="index.php#catalog" class="bg-white p-6 rounded-lg shadow flex items-center justify-center flex-col text-center hover:bg-gray-50">
        <i class="fa-solid fa-pills text-3xl text-blue-500 mb-2"></i>
        <span class="font-semibold">Browse Medicines</span>
    </a>
    <!-- Card 2 -->
    <a href="#" class="bg-white p-6 rounded-lg shadow flex items-center justify-center flex-col text-center hover:bg-gray-50">
        <i class="fa-solid fa-shop text-3xl text-green-500 mb-2"></i>
        <span class="font-semibold">Shop Locator</span>
    </a>
    <!-- More cards... -->
</div>


<?php include 'templates/footer.php'; ?>