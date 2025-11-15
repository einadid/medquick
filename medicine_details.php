<?php
// FILE: medicine_details.php
// PURPOSE: Displays detailed information about a single medicine.

require_once 'src/session.php';
require_once 'config/database.php';

// 1. GET MEDICINE ID from URL and validate it
$medicine_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($medicine_id <= 0) {
    // If no valid ID is provided, redirect to catalog
    redirect('catalog.php');
}

// 2. DATA FETCHING: Get all details for the selected medicine
try {
    // --- Main Medicine Info ---
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
    $stmt->execute([$medicine_id]);
    $medicine = $stmt->fetch();

    // If medicine with that ID doesn't exist, redirect.
    if (!$medicine) {
        redirect('catalog.php');
    }

    // --- Stock and Price Info ---
    // Find the minimum price and total stock across all shops for this medicine.
    $stock_stmt = $pdo->prepare("
        SELECT 
            MIN(price) as price, 
            SUM(quantity) as total_stock
        FROM inventory_batches 
        WHERE medicine_id = ? AND quantity > 0 AND expiry_date > CURDATE()
    ");
    $stock_stmt->execute([$medicine_id]);
    $stock_info = $stock_stmt->fetch();

    $medicine['price'] = $stock_info['price'] ?? 0;
    $medicine['in_stock'] = ($stock_info['total_stock'] ?? 0) > 0;

    // --- Related Products ---
    // Fetch other medicines from the same category.
    $related_stmt = $pdo->prepare("
        SELECT m.id, m.name, m.manufacturer, m.image_path, MIN(ib.price) as price
        FROM medicines m
        JOIN inventory_batches ib ON m.id = ib.medicine_id
        WHERE m.category = ? AND m.id != ? AND ib.quantity > 0 AND ib.expiry_date > CURDATE()
        GROUP BY m.id
        LIMIT 5
    ");
    $related_stmt->execute([$medicine['category'], $medicine_id]);
    $related_medicines = $related_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Medicine Details Page DB Error: " . $e->getMessage());
    // In case of a major DB error, redirecting is safer than showing a broken page.
    redirect('error.php'); // You can create a generic error page later.
}

$pageTitle = e($medicine['name']);
include 'templates/header.php';
?>

<div class="fade-in bg-white">
    <div class="container mx-auto px-4 sm:px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12">
            <!-- Left Side: Image Gallery -->
            <div class="p-4 border rounded-lg bg-gray-50">
                <img src="<?= e($medicine['image_path'] ?? 'assets/images/default_med.png'); ?>" 
                     alt="<?= e($medicine['name']); ?>" 
                     class="w-full h-auto max-h-96 object-contain">
            </div>

            <!-- Right Side: Details and Actions -->
            <div>
                <span class="text-sm font-semibold text-teal-600 bg-teal-100 px-3 py-1 rounded-full"><?= e($medicine['category']); ?></span>
                <h1 class="text-3xl md:text-4xl font-bold text-slate-800 mt-3"><?= e($medicine['name']); ?></h1>
                <p class="text-md text-gray-500 mt-1">By <span class="font-medium text-gray-700"><?= e($medicine['manufacturer']); ?></span></p>

                <div class="mt-6">
                    <?php if ($medicine['in_stock']): ?>
                        <p class="text-4xl font-extrabold text-teal-600">৳<?= e(number_format($medicine['price'], 2)); ?></p>
                        <span class="text-green-600 font-semibold flex items-center gap-2 mt-2">
                            <i class="fas fa-check-circle"></i> In Stock
                        </span>
                    <?php else: ?>
                        <p class="text-2xl font-bold text-red-600">Out of Stock</p>
                        <p class="text-gray-500">This item is currently unavailable.</p>
                    <?php endif; ?>
                </div>

                <?php if ($medicine['in_stock'] && (!is_logged_in() || has_role(ROLE_CUSTOMER))): ?>
                <!-- Add to Cart Section -->
                <div class="mt-8 pt-6 border-t">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center border rounded-lg">
                            <button id="qty-minus" class="w-12 h-12 text-gray-500 hover:bg-gray-100 rounded-l-lg">-</button>
                            <input id="quantity" type="number" value="1" min="1" class="w-16 h-12 text-center border-l border-r focus:outline-none">
                            <button id="qty-plus" class="w-12 h-12 text-gray-500 hover:bg-gray-100 rounded-r-lg">+</button>
                        </div>
                        <button 
                            id="detail-add-to-cart-btn"
                            class="btn-primary flex-grow text-lg"
                            data-id="<?= e($medicine['id']); ?>"
                            data-name="<?= e($medicine['name']); ?>"
                            data-price="<?= e($medicine['price']); ?>">
                            <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Accordion for Details -->
                <div class="mt-8 space-y-4" x-data="{ open: 'description' }">
                    <!-- Description Tab -->
                    <div>
                        <button @click="open = (open === 'description' ? '' : 'description')" class="w-full flex justify-between items-center text-left py-3 border-b-2">
                            <span class="font-semibold text-lg">Description</span>
                            <i class="fas fa-chevron-down transition-transform" :class="{ 'rotate-180': open === 'description' }"></i>
                        </button>
                        <div x-show="open === 'description'" x-transition class="pt-4 text-gray-600">
                            <p>This is a detailed description for <?= e($medicine['name']); ?>. It provides comprehensive information about the product, its composition, and its primary benefits. (This is a placeholder text. Actual description will be fetched from the database in a future update).</p>
                        </div>
                    </div>
                    <!-- Usage Tab -->
                    <div>
                        <button @click="open = (open === 'usage' ? '' : 'usage')" class="w-full flex justify-between items-center text-left py-3 border-b-2">
                            <span class="font-semibold text-lg">How to Use</span>
                            <i class="fas fa-chevron-down transition-transform" :class="{ 'rotate-180': open === 'usage' }"></i>
                        </button>
                        <div x-show="open === 'usage'" x-transition class="pt-4 text-gray-600">
                             <p>Follow the instructions provided by your doctor or pharmacist. Do not take more or less than the prescribed dosage. (This is a placeholder text).</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- ============= RELATED PRODUCTS SECTION ================ -->
<!-- ======================================================= -->
<?php if (!empty($related_medicines)): ?>
<section class="bg-slate-50 py-16 sm:py-20 border-t">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-10 text-slate-800">Related Products</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
            <?php foreach ($related_medicines as $rel_med): ?>
                <div class="bg-white border rounded-lg shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden group">
                    <a href="medicine_details.php?id=<?= e($rel_med['id']) ?>" class="block p-4">
                        <img src="<?= e($rel_med['image_path'] ?? 'assets/images/default_med.png'); ?>" alt="<?= e($rel_med['name']); ?>" class="w-full h-32 object-contain mb-4 transform group-hover:scale-105 transition-transform" loading="lazy">
                    </a>
                    <div class="p-4 border-t bg-slate-50/50">
                        <h3 class="font-semibold text-sm truncate" title="<?= e($rel_med['name']); ?>"><?= e($rel_med['name']); ?></h3>
                        <div class="flex justify-between items-center mt-2">
                            <p class="text-md font-bold text-teal-600">৳<?= e(number_format($rel_med['price'], 2)) ?></p>
                            <a href="medicine_details.php?id=<?= e($rel_med['id']) ?>" class="text-teal-600 hover:text-teal-800 text-xs font-bold">View</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- JavaScript for quantity controls and adding specific quantity to cart -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const qtyInput = document.getElementById('quantity');
    const plusBtn = document.getElementById('qty-plus');
    const minusBtn = document.getElementById('qty-minus');
    const addToCartBtn = document.getElementById('detail-add-to-cart-btn');

    if (plusBtn) {
        plusBtn.addEventListener('click', () => {
            qtyInput.value = parseInt(qtyInput.value) + 1;
        });
    }

    if (minusBtn) {
        minusBtn.addEventListener('click', () => {
            const currentQty = parseInt(qtyInput.value);
            if (currentQty > 1) {
                qtyInput.value = currentQty - 1;
            }
        });
    }

    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', () => {
            const id = addToCartBtn.dataset.id;
            const name = addToCartBtn.dataset.name;
            const price = parseFloat(addToCartBtn.dataset.price);
            const quantityToAdd = parseInt(qtyInput.value);

            let cart = JSON.parse(localStorage.getItem('quickmed_cart')) || {};

            if (cart[id]) {
                cart[id].qty += quantityToAdd;
            } else {
                cart[id] = { name: name, qty: quantityToAdd, price: price };
            }
            
            localStorage.setItem('quickmed_cart', JSON.stringify(cart));
            
            // Update UI
            addToCartBtn.innerHTML = '<i class="fas fa-check mr-2"></i> Added to Cart!';
            addToCartBtn.classList.remove('btn-primary');
            addToCartBtn.classList.add('bg-green-500', 'text-white');
            
            // Update cart count in header
            const count = Object.values(cart).reduce((sum, item) => sum + item.qty, 0);
            document.getElementById('cart-count').textContent = count;
            document.getElementById('cart-count').classList.remove('hidden');

            setTimeout(() => {
                addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart mr-2"></i> Add to Cart';
                addToCartBtn.classList.add('btn-primary');
                addToCartBtn.classList.remove('bg-green-500', 'text-white');
            }, 2000);
        });
    }
});
</script>


<?php
include 'templates/footer.php';
?>