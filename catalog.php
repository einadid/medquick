<?php
require_once 'src/session.php';
require_once 'config/database.php';

// ডেটাবেস থেকে সব মেডিসিন নিয়ে আসা
try {
    // Pagination এর জন্য প্রস্তুতি (আপাতত সব দেখানো হচ্ছে)
    $stmt = $pdo->query("SELECT * FROM medicines ORDER BY name ASC");
    $medicines = $stmt->fetchAll();
} catch (PDOException $e) {
    $medicines = [];
    $error = "Could not fetch medicines from the database.";
}

$pageTitle = "Medicine Catalog";
include 'templates/header.php';
?>

<div class="container mx-auto p-8">
    <h1 class="text-4xl font-bold mb-8 text-center">Our Medicine Catalog</h1>

    <!-- Success message from medicine_add.php -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 text-center" role="alert">
            <p><?= e($_SESSION['success_message']); ?></p>
        </div>
        <?php unset($_SESSION['success_message']); // একবার দেখানোর পর মুছে ফেলা ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <p class="text-red-500 text-center"><?= e($error); ?></p>
    <?php elseif (empty($medicines)): ?>
        <p class="text-gray-500 text-center">No medicines found in the catalog yet.</p>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
            <?php foreach ($medicines as $med): ?>
                <div class="bg-white border rounded-lg shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                    <a href="medicine_details.php?id=<?= e($med['id']); ?>">
                        <img src="<?= e($med['image_path'] ?? 'assets/images/default_med.png'); ?>" alt="<?= e($med['name']); ?>" class="w-full h-40 object-contain p-2" loading="lazy">
                    </a>
                    <div class="p-4">
                        <h3 class="font-bold text-md truncate" title="<?= e($med['name']); ?>"><?= e($med['name']); ?></h3>
                        <p class="text-sm text-gray-600 mb-2"><?= e($med['manufacturer']); ?></p>
                        <p class="text-xs bg-gray-200 text-gray-800 inline-block px-2 py-1 rounded-full"><?= e($med['category']); ?></p>
                        
                        <?php if (has_role(ROLE_CUSTOMER)): ?>
                            <button 
                                class="add-to-cart-btn w-full bg-blue-500 text-white text-sm py-2 rounded mt-4 hover:bg-blue-600"
                                data-id="<?= e($med['id']); ?>"
                                data-name="<?= e($med['name']); ?>"
                                data-price="15.50" <!-- Placeholder: Price আসবে inventory থেকে -->
                            >
                                Add to Cart
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php 
// main.js script টি ফুটারের আগে যুক্ত করা প্রয়োজন যদি না থাকে
// echo '<script src="assets/js/main.js"></script>';
include 'templates/footer.php'; 
?>