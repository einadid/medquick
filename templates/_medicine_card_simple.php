<?php
// FILE: templates/_medicine_card_simple.php
// A simplified version of the medicine card for sections like "Quick Re-order".
?>
<a href="medicine_details.php?id=<?= e($item['id']) ?>" class="flex-grow">
    <img src="<?= e($item['image_path'] ?? 'assets/images/default_med.png') ?>" alt="<?= e($item['name']) ?>" class="w-full h-24 object-contain mb-4">
</a>
<h4 class="text-sm font-semibold truncate" title="<?= e($item['name']) ?>"><?= e($item['name']) ?></h4>
<?php if(isset($item['total_stock']) && $item['total_stock'] > 0): ?>
    <p class="text-md font-bold text-teal-600 mt-1">৳<?= e(number_format($item['price'], 2)) ?></p>
    <button class="add-to-cart-btn mt-3 w-full bg-slate-100 text-slate-700 hover:bg-teal-600 hover:text-white text-xs font-bold px-3 py-2 rounded-full transition-colors"
        data-id="<?= e($item['id']); ?>" data-name="<?= e($item['name']); ?>" data-price="<?= e($item['price']); ?>">
        Add to Cart
    </button>
<?php else: ?>
    <p class="text-sm font-semibold text-red-500 mt-2">Out of Stock</p>
<?php endif; ?>