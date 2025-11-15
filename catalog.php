<?php
// FILE: catalog.php (Final Professional Design)
require_once 'src/session.php';
require_once 'config/database.php';

$search_term = trim($_GET['search'] ?? '');
$filter_category = trim($_GET['category'] ?? '');
$filter_manufacturer = trim($_GET['manufacturer'] ?? '');
$filter_availability = trim($_GET['availability'] ?? 'all');
$sort_order = trim($_GET['sort'] ?? 'name_asc');
$limit = 20;

try {
    $categories = $pdo->query("SELECT DISTINCT category FROM medicines ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
    $manufacturers = $pdo->query("SELECT DISTINCT manufacturer FROM medicines ORDER BY manufacturer ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) { $categories = []; $manufacturers = []; }

$params = [];
$count_params = [];
$sql_base = "FROM medicines m LEFT JOIN inventory_batches ib ON m.id = ib.medicine_id AND ib.quantity > 0 AND ib.expiry_date > CURDATE()";
$where_clause = " WHERE 1=1";
if (!empty($search_term)) { $where_clause .= " AND (m.name LIKE ? OR m.manufacturer LIKE ?)"; $params[] = "%$search_term%"; $params[] = "%$search_term%"; $count_params = $params; }
if (!empty($filter_category)) { $where_clause .= " AND m.category = ?"; $params[] = $filter_category; $count_params[] = $filter_category; }
if (!empty($filter_manufacturer)) { $where_clause .= " AND m.manufacturer = ?"; $params[] = $filter_manufacturer; $count_params[] = $filter_manufacturer; }

$group_by = " GROUP BY m.id";
$having_clause = "";
if ($filter_availability === 'in_stock') { $having_clause = " HAVING SUM(ib.quantity) > 0"; } 
elseif ($filter_availability === 'out_of_stock') { $having_clause = " HAVING SUM(ib.quantity) IS NULL OR SUM(ib.quantity) = 0"; }

try {
    $count_sql = "SELECT COUNT(*) FROM (SELECT m.id " . $sql_base . $where_clause . $group_by . $having_clause . ") as subquery";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_results = $count_stmt->fetchColumn();
} catch (PDOException $e) { $total_results = 0; error_log($e->getMessage()); }

$sql = "SELECT m.id, m.name, m.manufacturer, m.category, m.description, m.image_path, MIN(ib.price) as price, SUM(ib.quantity) as total_stock " . $sql_base . $where_clause . $group_by . $having_clause;
switch ($sort_order) {
    case 'price_asc': $sql .= " ORDER BY CASE WHEN price IS NULL THEN 1 ELSE 0 END, price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY CASE WHEN price IS NULL THEN 1 ELSE 0 END, price DESC"; break;
    case 'name_desc': $sql .= " ORDER BY m.name DESC"; break;
    default: $sql .= " ORDER BY m.name ASC"; break;
}
$sql .= " LIMIT $limit";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $medicines = $stmt->fetchAll();
} catch (PDOException $e) { $medicines = []; $db_error = "Could not fetch medicines."; }

$pageTitle = "Medicine Catalog";
include 'templates/header.php';
?>

<div class="fade-in bg-slate-50" x-data="{ filtersOpen: false, quickViewOpen: false, quickViewMedicine: {} }">
    <div class="bg-gradient-to-r from-teal-500 to-cyan-600 text-white pt-12 pb-8 shadow-inner">
        <div class="container mx-auto px-4 sm:px-6 text-center">
            <h1 class="text-4xl font-extrabold">Explore Our Medicines</h1>
            <p class="mt-2 text-lg text-teal-100">Showing <span id="results-count" class="font-bold"><?= count($medicines) ?></span> of <span id="total-results" class="font-bold"><?= $total_results ?></span> products.</p>
        </div>
    </div>
    <div class="container mx-auto px-4 sm:px-6 py-8">
        <div class="sticky top-[74px] md:top-[68px] z-20 mb-8" @click.away="filtersOpen = false">
            <div class="md:hidden"><button @click="filtersOpen = !filtersOpen" class="w-full flex items-center justify-center gap-2 p-3 bg-white border rounded-lg shadow-sm"><i class="fas fa-filter"></i><span class="font-semibold">Filters & Sort</span></button></div>
            <div x-show="filtersOpen" class="md:hidden absolute top-full left-0 right-0 mt-2 bg-white p-4 rounded-lg shadow-lg border" x-transition style="display: none;"><?php include 'templates/_catalog_filters.php'; ?></div>
            <div class="hidden md:block bg-white p-4 rounded-lg shadow-md"><?php include 'templates/_catalog_filters.php'; ?></div>
        </div>
        <div id="medicine-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
            <?php if (isset($db_error)): ?><p class="col-span-full text-center text-red-500 bg-red-100 p-4 rounded-md"><?= e($db_error); ?></p>
            <?php elseif (empty($medicines)): ?><div class="col-span-full text-center py-16"><i class="fas fa-prescription-bottle-alt text-6xl text-gray-300"></i><h3 class="mt-4 text-xl font-semibold text-gray-700">No Medicines Found</h3><p class="mt-1 text-gray-500">Try adjusting your filters.</p><a href="catalog.php" class="mt-6 btn-primary">Reset Filters</a></div>
            <?php else: foreach ($medicines as $med) { include 'templates/_medicine_card.php'; } endif; ?>
        </div>
        <div id="load-more-container" class="text-center mt-12 <?= ($total_results <= $limit) ? 'hidden' : '' ?>"><button id="load-more-btn" class="btn-primary py-3 px-8 text-lg"><span class="btn-text">Load More</span><span class="btn-loader hidden"><i class="fas fa-spinner fa-spin"></i> Loading...</span></button></div>
    </div>
    <div x-show="quickViewOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;"><div @click="quickViewOpen = false" x-show="quickViewOpen" x-transition class="absolute inset-0 bg-gray-900 bg-opacity-75"></div><div x-show="quickViewOpen" x-transition class="relative bg-white rounded-lg shadow-xl w-full max-w-3xl flex flex-col md:flex-row overflow-hidden"><div class="w-full md:w-1/3 bg-gray-100 p-4 flex items-center justify-center"><img :src="quickViewMedicine.image_path || 'assets/images/default_med.png'" :alt="quickViewMedicine.name" class="max-h-60 object-contain"></div><div class="w-full md:w-2/3 p-6 flex flex-col"><button @click="quickViewOpen = false" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600"><i class="fas fa-times text-2xl"></i></button><h2 class="text-2xl font-bold text-slate-800" x-text="quickViewMedicine.name"></h2><p class="text-sm text-gray-500 mt-1">By <span x-text="quickViewMedicine.manufacturer"></span></p><p class="mt-4 text-gray-600 text-sm flex-grow" x-text="quickViewMedicine.description ? quickViewMedicine.description.substring(0, 150) + '...' : 'No description available.'"></p><div class="mt-6 pt-4 border-t flex justify-between items-center"><template x-if="quickViewMedicine.total_stock > 0"><p class="text-3xl font-extrabold text-teal-600" x-text="'৳' + parseFloat(quickViewMedicine.price).toFixed(2)"></p></template><template x-if="!(quickViewMedicine.total_stock > 0)"><p class="text-xl font-bold text-red-500">Out of Stock</p></template><template x-if="quickViewMedicine.total_stock > 0"><button class="add-to-cart-btn btn-primary" :data-id="quickViewMedicine.id" :data-name="quickViewMedicine.name" :data-price="quickViewMedicine.price"><i class="fas fa-cart-plus mr-2"></i> Add to Cart</button></template></div></div></div></div>
</div>
<?php include 'templates/footer.php'; ?>