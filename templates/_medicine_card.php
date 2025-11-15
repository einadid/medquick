<?php
// FILE: templates/_medicine_card.php (Final Professional Version)
// PURPOSE: A reusable template for displaying a single medicine card.
// It expects a $med variable (containing medicine details) to be available in the scope where it's included.
?>
<div class="bg-white rounded-lg shadow-md overflow-hidden group transition-all duration-300 hover:shadow-xl hover:-translate-y-1.5 flex flex-col h-full">
    
    <!-- Image Section with Overlay Actions -->
    <div class="relative">
        <a href="medicine_details.php?id=<?= e($med['id']) ?>" class="block aspect-square p-4">
            <img src="<?= e($med['image_path'] ?? 'assets/images/default_med.png'); ?>" 
                 alt="<?= e($med['name']); ?>" 
                 class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105" 
                 loading="lazy">
        </a>
        
        <!-- Out of Stock Badge -->
        <?php if (!isset($med['total_stock']) || $med['total_stock'] <= 0): ?>
            <div class="absolute top-2 left-2 bg-red-100 text-red-700 text-xs font-bold px-2 py-1 rounded-full">Out of Stock</div>
        <?php endif; ?>

        <!-- Quick View Button (appears on hover) -->
        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <button @click="quickViewOpen = true; quickViewMedicine = <?= htmlspecialchars(json_encode($med)) ?>" 
                    class="w-9 h-9 bg-white/80 backdrop-blur rounded-full flex items-center justify-center text-gray-600 hover:bg-white hover:text-teal-600 shadow-md" 
                    title="Quick View">
                <i class="fas fa-eye"></i>
            </button>
        </div>
    </div>

    <!-- Details Section -->
    <div class="p-4 border-t bg-slate-50/50 flex flex-col flex-grow">
        <h3 class="font-semibold text-sm flex-grow">
            <a href="medicine_details.php?id=<?= e($med['id']) ?>" class="hover:text-teal-600" title="<?= e($med['name']); ?>">
                <?= e($med['name']); ?>
            </a>
        </h3>
        <p class="text-xs text-gray-500 mt-1 mb-3"><?= e($med['manufacturer']); ?></p>
        
        <!-- Price and Add to Cart Button -->
        <div class="flex justify-between items-center mt-auto">
            <?php if (isset($med['total_stock']) && $med['total_stock'] > 0): ?>
                <p class="text-lg font-bold text-teal-600">৳<?= e(number_format($med['price'], 2)) ?></p>
                
                <?php if (!is_logged_in() || has_role(ROLE_CUSTOMER)): ?>
                    <button class="add-to-cart-btn bg-teal-100 text-teal-700 hover:bg-teal-600 hover:text-white text-xs font-bold px-3 py-1.5 rounded-full transition-colors"
                            data-id="<?= e($med['id']); ?>" 
                            data-name="<?= e($med['name']); ?>" 
                            data-price="<?= e($med['price']); ?>">
                        Add
                    </button>
                <?php endif; ?>

            <?php else: ?>
                <a href="medicine_details.php?id=<?= e($med['id']) ?>" class="text-sm font-semibold text-gray-500 hover:text-teal-600">View Details</a>
            <?php endif; ?>
        </div>
    </div>
</div>