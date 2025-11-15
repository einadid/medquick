<?php
// FILE: templates/_medicine_card.php
// PURPOSE: A reusable template part for displaying a single medicine card.
// Expects a $med variable to be available.
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden group transition-all duration-300 hover:shadow-2xl hover:-translate-y-1.5">
    <div class="relative">
        <a href="medicine_details.php?id=<?= e($med['id']) ?>">
            <img src="<?= e($med['image_path'] ?? 'assets/images/default_med.png'); ?>" alt="<?= e($med['name']); ?>" class="w-full h-40 object-contain p-2 transition-transform duration-300 group-hover:scale-105" loading="lazy">
        </a>
        <?php if ($med['total_stock'] <= 0): ?>
            <div class="absolute top-2 left-2 bg-red-100 text-red-700 text-xs font-bold px-2 py-1 rounded-full">Out of Stock</div>
        <?php endif; ?>
        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <button @click="quickViewOpen = true; quickViewMedicine = <?= e(json_encode($med)) ?>" class="w-9 h-9 bg-white/80 backdrop-blur rounded-full flex items-center justify-center text-gray-600 hover:bg-white hover:text-teal-600 shadow-md" title="Quick View">
                <i class="fas fa-eye"></i>
            </button>
        </div>
    </div>
    <div class="p-4 flex flex-col flex-grow">
        <h3 class="font-semibold text-sm flex-grow"><a href="medicine_details.php?id=<?= e($med['id']) ?>" class="hover:text-teal-600"><?= e($med['name']); ?></a></h3>
        <p class="text-xs text-gray-500 mt-1 mb-3"><?= e($med['manufacturer']); ?></p>
        <div class="flex justify-between items-center mt-auto">
            <?php if ($med['total_stock'] > 0): ?>
                <p class="text-lg font-bold text-teal-600">৳<?= e(number_format($med['price'], 2)) ?></p>
                <button class="add-to-cart-btn bg-teal-100 text-teal-700 hover:bg-teal-600 hover:text-white text-xs font-bold px-3 py-1.5 rounded-full transition-colors" data-id="<?= e($med['id']); ?>" data-name="<?= e($med['name']); ?>" data-price="<?= e($med['price']); ?>">Add</button>
            <?php else: ?>
                <a href="medicine_details.php?id=<?= e($med['id']) ?>" class="text-sm font-semibold text-gray-500 hover:text-teal-600">Details</a>
            <?php endif; ?>
        </div>
    </div>
</div>