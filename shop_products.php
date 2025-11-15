<?php
// FILE: shop_products.php
// PURPOSE: Displays all products available in a specific shop.
require_once 'src/session.php';
require_once 'config/database.php';

$shop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($shop_id <= 0) { redirect('shops.php'); }

try {
    $shop_stmt = $pdo->prepare("SELECT name FROM shops WHERE id = ?");
    $shop_stmt->execute([$shop_id]);
    $shop_name = $shop_stmt->fetchColumn();
    if (!$shop_name) { redirect('shops.php'); }

    // Fetch products available only in this specific shop
    $medicines_stmt = $pdo->prepare("
        SELECT m.id, m.name, m.manufacturer, m.image_path, m.description, ib.price, SUM(ib.quantity) as total_stock
        FROM medicines m
        JOIN inventory_batches ib ON m.id = ib.medicine_id
        WHERE ib.shop_id = ? AND ib.quantity > 0 AND ib.expiry_date > CURDATE()
        GROUP BY m.id
        ORDER BY m.name ASC
    ");
    $medicines_stmt->execute([$shop_id]);
    $medicines = $medicines_stmt->fetchAll();
} catch (PDOException $e) {
    $medicines = [];
    $db_error = "Could not load products for this shop.";
}

$pageTitle = "Medicines at " . e($shop_name);
include 'templates/header.php';
?>
<div class="fade-in bg-slate-50" x-data="{ quickViewOpen: false, quickViewMedicine: {} }">
    <div class="bg-white border-b pt-12 pb-8">
        <div class="container mx-auto px-4 sm:px-6 text-center">
            <h1 class="text-4xl font-extrabold text-slate-800">Medicines at <span class="text-teal-600"><?= e($shop_name) ?></span></h1>
            <p class="mt-2 text-lg text-gray-600">Showing all available products from this branch.</p>
        </div>
    </div>
    <div class="container mx-auto px-4 sm:px-6 py-8">
        <div class="mb-4"><a href="shops.php" class="text-sm font-medium text-teal-600 hover:underline">&larr; Back to All Pharmacies</a></div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
            <?php if (isset($db_error)): ?><p class="col-span-full ..."><?= e($db_error); ?></p>
            <?php elseif (empty($medicines)): ?><div class="col-span-full text-center py-16">... No products found in this shop.</div>
            <?php else: foreach ($medicines as $med) { include 'templates/_medicine_card.php'; } endif; ?>
        </div>
    </div>
    <!-- Quick View Modal (same as catalog.php) -->
    <div x-show="quickViewOpen" ...></div>
</div>
<?php include 'templates/footer.php'; ?>