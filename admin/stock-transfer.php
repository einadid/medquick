<?php
$pageTitle = 'Stock Transfer';
require_once '../includes/header.php';
requireRole('admin');

// Handle stock transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_stock'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $fromShopId = $_POST['from_shop'];
    $toShopId = $_POST['to_shop'];
    $medicineId = $_POST['medicine_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Get source stock
    $sourceStock = Database::getInstance()->fetchOne("
        SELECT * FROM shop_medicines 
        WHERE shop_id = ? AND medicine_id = ? 
        ORDER BY expiry_date ASC LIMIT 1
    ", [$fromShopId, $medicineId]);
    
    if (!$sourceStock || $sourceStock['stock'] < $quantity) {
        setFlash('error', 'Insufficient stock in source shop');
        redirect('/admin/stock-transfer.php');
    }
    
    // Begin transaction
    Database::getInstance()->getConnection()->beginTransaction();
    
    try {
        // Reduce from source
        Database::getInstance()->query("
            UPDATE shop_medicines 
            SET stock = stock - ? 
            WHERE shop_id = ? AND medicine_id = ?
        ", [$quantity, $fromShopId, $medicineId]);
        
        // Check if exists in destination
        $destStock = Database::getInstance()->fetchOne("
            SELECT * FROM shop_medicines 
            WHERE shop_id = ? AND medicine_id = ? AND batch_number = ?
        ", [$toShopId, $medicineId, $sourceStock['batch_number']]);
        
        if ($destStock) {
            // Update existing
            Database::getInstance()->query("
                UPDATE shop_medicines 
                SET stock = stock + ? 
                WHERE id = ?
            ", [$quantity, $destStock['id']]);
        } else {
            // Insert new
            Database::getInstance()->insert('shop_medicines', [
                'shop_id' => $toShopId,
                'medicine_id' => $medicineId,
                'stock' => $quantity,
                'buying_price' => $sourceStock['buying_price'],
                'selling_price' => $sourceStock['selling_price'],
                'expiry_date' => $sourceStock['expiry_date'],
                'batch_number' => $sourceStock['batch_number']
            ]);
        }
        
        Database::getInstance()->getConnection()->commit();
        logAudit($_SESSION['user_id'], 'stock_transferred', "Transferred $quantity units of medicine #$medicineId from shop #$fromShopId to #$toShopId");
        setFlash('success', 'Stock transferred successfully');
        
    } catch (Exception $e) {
        Database::getInstance()->getConnection()->rollBack();
        setFlash('error', 'Transfer failed: ' . $e->getMessage());
    }
    
    redirect('/admin/stock-transfer.php');
}

// Get shops
$shops = Database::getInstance()->fetchAll("SELECT * FROM shops WHERE status = 'active' ORDER BY name");

// Get medicines
$medicines = Database::getInstance()->fetchAll("SELECT * FROM medicines WHERE status = 'active' ORDER BY name");
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">Stock Transfer Between Shops</h2>
    <p class="text-gray-600">Transfer medicine inventory from one shop to another</p>
</div>

<div class="bg-white border-2 border-gray-300 p-6">
    <form method="POST">
        <?php echo csrfField(); ?>
        <input type="hidden" name="transfer_stock" value="1">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block mb-2 font-bold">From Shop *</label>
                <select name="from_shop" id="fromShop" required class="w-full p-2 border-2 border-gray-400">
                    <option value="">Select Source Shop</option>
                    <?php foreach ($shops as $shop): ?>
                        <option value="<?php echo $shop['id']; ?>"><?php echo clean($shop['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block mb-2 font-bold">To Shop *</label>
                <select name="to_shop" required class="w-full p-2 border-2 border-gray-400">
                    <option value="">Select Destination Shop</option>
                    <?php foreach ($shops as $shop): ?>
                        <option value="<?php echo $shop['id']; ?>"><?php echo clean($shop['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block mb-2 font-bold">Medicine *</label>
                <select name="medicine_id" id="medicineSelect" required class="w-full p-2 border-2 border-gray-400">
                    <option value="">Select Medicine</option>
                    <?php foreach ($medicines as $med): ?>
                        <option value="<?php echo $med['id']; ?>">
                            <?php echo clean($med['name']); ?> - <?php echo clean($med['generic_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block mb-2 font-bold">Quantity *</label>
                <input type="number" name="quantity" min="1" required class="w-full p-2 border-2 border-gray-400">
            </div>
        </div>
        
        <div id="stockInfo" class="mt-4 p-4 bg-blue-50 border border-blue-300 hidden">
            <div class="font-bold mb-2">Available Stock Information:</div>
            <div id="stockDetails"></div>
        </div>
        
        <button type="submit" class="mt-4 w-full p-3 bg-green-600 text-white font-bold">
            TRANSFER STOCK
        </button>
    </form>
</div>

<script>
document.getElementById('fromShop').addEventListener('change', checkStock);
document.getElementById('medicineSelect').addEventListener('change', checkStock);

function checkStock() {
    const shopId = document.getElementById('fromShop').value;
    const medicineId = document.getElementById('medicineSelect').value;
    
    if (shopId && medicineId) {
        fetch('<?php echo SITE_URL; ?>/ajax/check-stock.php?shop_id=' + shopId + '&medicine_id=' + medicineId)
            .then(response => response.json())
            .then(data => {
                if (data.stock > 0) {
                    document.getElementById('stockDetails').innerHTML = 
                        '<div>Available: <strong>' + data.stock + ' units</strong></div>' +
                        '<div>Batch: ' + data.batch_number + '</div>' +
                        '<div>Expiry: ' + data.expiry_date + '</div>';
                    document.getElementById('stockInfo').classList.remove('hidden');
                } else {
                    document.getElementById('stockDetails').innerHTML = 
                        '<div class="text-red-600">No stock available in selected shop</div>';
                    document.getElementById('stockInfo').classList.remove('hidden');
                }
            });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>