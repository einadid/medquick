<?php
$pageTitle = 'Medicine Details';
require_once '../includes/header.php';

$medicineId = $_GET['id'] ?? 0;

// Get medicine details
$medicine = Database::getInstance()->fetchOne("
    SELECT m.*, c.name as category_name
    FROM medicines m
    JOIN categories c ON m.category_id = c.id
    WHERE m.id = ? AND m.status = 'active'
", [$medicineId]);

if (!$medicine) {
    die('Medicine not found');
}

// Get shop-wise availability
$shopPrices = Database::getInstance()->fetchAll("
    SELECT sm.*, s.name as shop_name, s.city, s.address
    FROM shop_medicines sm
    JOIN shops s ON sm.shop_id = s.id
    WHERE sm.medicine_id = ? AND sm.stock > 0
    ORDER BY sm.selling_price ASC
", [$medicineId]);
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <a href="medicines.php" class="text-blue-600 mb-4 inline-block">← Back to Catalog</a>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Medicine Info -->
        <div>
            <h1 class="text-3xl font-bold mb-2"><?php echo clean($medicine['name']); ?></h1>
            <div class="text-xl text-gray-600 mb-4"><?php echo clean($medicine['generic_name']); ?></div>
            
            <table class="w-full border-2 border-gray-300 mb-4">
                <tr class="border-b">
                    <td class="p-2 font-bold bg-gray-100">Category</td>
                    <td class="p-2"><?php echo clean($medicine['category_name']); ?></td>
                </tr>
                <tr class="border-b">
                    <td class="p-2 font-bold bg-gray-100">Dosage Form</td>
                    <td class="p-2"><?php echo clean($medicine['dosage_form']); ?></td>
                </tr>
                <tr class="border-b">
                    <td class="p-2 font-bold bg-gray-100">Strength</td>
                    <td class="p-2"><?php echo clean($medicine['strength']); ?></td>
                </tr>
                <tr class="border-b">
                    <td class="p-2 font-bold bg-gray-100">Manufacturer</td>
                    <td class="p-2"><?php echo clean($medicine['manufacturer']); ?></td>
                </tr>
            </table>
            
            <?php if ($medicine['description']): ?>
            <div class="mb-4">
                <h3 class="font-bold mb-2">Description</h3>
                <p class="text-gray-700"><?php echo nl2br(clean($medicine['description'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Shop Availability -->
        <div>
            <h2 class="text-2xl font-bold mb-4">Available at Shops</h2>
            
            <?php if (empty($shopPrices)): ?>
                <div class="border-2 border-gray-300 p-4 text-center text-gray-600">
                    Currently out of stock in all shops
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($shopPrices as $sp): ?>
                    <div class="border-2 border-gray-300 p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <div class="font-bold text-lg"><?php echo clean($sp['shop_name']); ?></div>
                                <div class="text-sm text-gray-600"><?php echo clean($sp['city']); ?></div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-green-600"><?php echo formatPrice($sp['selling_price']); ?></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="text-sm">
                                <span class="font-semibold">Stock:</span> <?php echo $sp['stock']; ?> units
                            </div>
                            <?php if ($sp['expiry_date']): ?>
                            <div class="text-sm">
                                <span class="font-semibold">Expiry:</span> 
                                <?php 
                                $expiryDate = strtotime($sp['expiry_date']);
                                $daysUntilExpiry = floor(($expiryDate - time()) / 86400);
                                $expiryClass = $daysUntilExpiry < 90 ? 'text-red-600' : 'text-gray-600';
                                ?>
                                <span class="<?php echo $expiryClass; ?>">
                                    <?php echo date('M d, Y', $expiryDate); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="text-sm">
                                <span class="font-semibold">Batch:</span> <?php echo clean($sp['batch_number']); ?>
                            </div>
                        </div>
                        
                        <?php if (isLoggedIn() && hasRole('customer')): ?>
                        <form method="POST" action="<?php echo SITE_URL; ?>/ajax/add-to-cart.php" class="add-to-cart-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="shop_medicine_id" value="<?php echo $sp['id']; ?>">
                            
                            <div class="flex gap-2">
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $sp['stock']; ?>" 
                                       class="w-20 p-2 border-2 border-gray-400">
                                <button type="submit" class="flex-1 p-2 bg-blue-600 text-white font-bold">
                                    ADD TO CART
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="block w-full p-2 bg-gray-400 text-white text-center font-bold">
                            LOGIN TO PURCHASE
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>