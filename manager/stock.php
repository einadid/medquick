<?php
$pageTitle = 'Stock Management';
require_once '../includes/header.php';
requireRole('shop_manager');

$user = getCurrentUser();
$shopId = $user['shop_id'];

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $shopMedicineId = $_POST['shop_medicine_id'];
    $stock = $_POST['stock'];
    $buyingPrice = $_POST['buying_price'];
    $sellingPrice = $_POST['selling_price'];
    $expiryDate = $_POST['expiry_date'];
    $batchNumber = $_POST['batch_number'];
    
    Database::getInstance()->query("
        UPDATE shop_medicines 
        SET stock = ?, buying_price = ?, selling_price = ?, expiry_date = ?, batch_number = ?
        WHERE id = ? AND shop_id = ?
    ", [$stock, $buyingPrice, $sellingPrice, $expiryDate, $batchNumber, $shopMedicineId, $shopId]);
    
    logAudit($_SESSION['user_id'], 'stock_updated', "Stock updated for shop_medicine #$shopMedicineId");
    setFlash('success', 'Stock updated successfully');
    redirect('/manager/stock.php');
}

// Handle new stock entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $medicineId = $_POST['medicine_id'];
    $stock = $_POST['stock'];
    $buyingPrice = $_POST['buying_price'];
    $sellingPrice = $_POST['selling_price'];
    $expiryDate = $_POST['expiry_date'];
    $batchNumber = $_POST['batch_number'];
    
    // Check if already exists
    $existing = Database::getInstance()->fetchOne("
        SELECT id FROM shop_medicines 
        WHERE shop_id = ? AND medicine_id = ? AND batch_number = ?
    ", [$shopId, $medicineId, $batchNumber]);
    
    if ($existing) {
        setFlash('error', 'This batch already exists. Please update existing stock.');
    } else {
        Database::getInstance()->query("
            INSERT INTO shop_medicines (shop_id, medicine_id, stock, buying_price, selling_price, expiry_date, batch_number)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [$shopId, $medicineId, $stock, $buyingPrice, $sellingPrice, $expiryDate, $batchNumber]);
        
        logAudit($_SESSION['user_id'], 'stock_added', "New stock added for medicine #$medicineId");
        setFlash('success', 'Stock added successfully');
    }
    
    redirect('/manager/stock.php');
}

// Get stock with alerts
$stockItems = Database::getInstance()->fetchAll("
    SELECT sm.*, m.name as medicine_name, m.generic_name, m.dosage_form, m.strength,
           DATEDIFF(sm.expiry_date, CURDATE()) as days_until_expiry
    FROM shop_medicines sm
    JOIN medicines m ON sm.medicine_id = m.id
    WHERE sm.shop_id = ?
    ORDER BY sm.stock ASC, sm.expiry_date ASC
", [$shopId]);

// Get all medicines for dropdown
$allMedicines = Database::getInstance()->fetchAll("
    SELECT * FROM medicines WHERE status = 'active' ORDER BY name
");
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold">Stock Management</h2>
</div>

<!-- Add New Stock Button -->
<div class="mb-4">
    <button onclick="showAddModal()" class="px-6 py-3 bg-green-600 text-white font-bold">
        + ADD NEW STOCK
    </button>
</div>

<!-- Stock Alerts -->
<?php
$lowStock = array_filter($stockItems, fn($item) => $item['stock'] < 20);
$expiringSoon = array_filter($stockItems, fn($item) => $item['days_until_expiry'] <= 90 && $item['days_until_expiry'] > 0);
?>

<?php if (!empty($lowStock)): ?>
<div class="bg-red-50 border-2 border-red-400 p-4 mb-4">
    <h3 class="font-bold text-red-700 mb-2">⚠️ Low Stock Alert (<?php echo count($lowStock); ?> items)</h3>
    <div class="text-sm text-red-600">
        <?php foreach (array_slice($lowStock, 0, 5) as $item): ?>
            <div><?php echo clean($item['medicine_name']); ?> - Only <?php echo $item['stock']; ?> left</div>
        <?php endforeach; ?>
        <?php if (count($lowStock) > 5): ?>
            <div class="mt-1">... and <?php echo count($lowStock) - 5; ?> more</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($expiringSoon)): ?>
<div class="bg-yellow-50 border-2 border-yellow-400 p-4 mb-4">
    <h3 class="font-bold text-yellow-700 mb-2">⏰ Expiring Soon (<?php echo count($expiringSoon); ?> items)</h3>
    <div class="text-sm text-yellow-600">
        <?php foreach (array_slice($expiringSoon, 0, 5) as $item): ?>
            <div><?php echo clean($item['medicine_name']); ?> - Expires in <?php echo $item['days_until_expiry']; ?> days</div>
        <?php endforeach; ?>
        <?php if (count($expiringSoon) > 5): ?>
            <div class="mt-1">... and <?php echo count($expiringSoon) - 5; ?> more</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Stock Table -->
<div class="bg-white border-2 border-gray-300 p-6">
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left border">Medicine</th>
                <th class="p-2 text-left border">Batch</th>
                <th class="p-2 text-center border">Stock</th>
                <th class="p-2 text-right border">Buying Price</th>
                <th class="p-2 text-right border">Selling Price</th>
                <th class="p-2 text-left border">Expiry</th>
                <th class="p-2 text-center border">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stockItems as $item): ?>
            <tr class="<?php 
                echo $item['stock'] < 20 ? 'bg-red-50' : 
                    ($item['days_until_expiry'] <= 90 && $item['days_until_expiry'] > 0 ? 'bg-yellow-50' : ''); 
            ?>">
                <td class="p-2 border">
                    <div class="font-bold"><?php echo clean($item['medicine_name']); ?></div>
                    <div class="text-sm text-gray-600">
                        <?php echo clean($item['generic_name']); ?> - 
                        <?php echo clean($item['dosage_form']); ?> 
                        <?php echo clean($item['strength']); ?>
                    </div>
                </td>
                <td class="p-2 border text-sm"><?php echo clean($item['batch_number']); ?></td>
                <td class="p-2 border text-center">
                    <span class="font-bold <?php echo $item['stock'] < 20 ? 'text-red-600' : ''; ?>">
                        <?php echo $item['stock']; ?>
                    </span>
                </td>
                <td class="p-2 border text-right"><?php echo formatPrice($item['buying_price']); ?></td>
                <td class="p-2 border text-right font-bold"><?php echo formatPrice($item['selling_price']); ?></td>
                <td class="p-2 border">
                    <?php if ($item['expiry_date']): ?>
                        <div class="<?php echo $item['days_until_expiry'] <= 90 ? 'text-red-600 font-bold' : ''; ?>">
                            <?php echo date('M d, Y', strtotime($item['expiry_date'])); ?>
                        </div>
                        <div class="text-xs text-gray-600">
                            <?php echo $item['days_until_expiry'] > 0 ? $item['days_until_expiry'] . ' days left' : 'EXPIRED'; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="p-2 border text-center">
                    <button onclick='editStock(<?php echo json_encode($item); ?>)' 
                            class="px-3 py-1 bg-blue-600 text-white text-sm">
                        EDIT
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Stock Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border-4 border-gray-300 p-6 max-w-2xl w-full m-4 max-h-screen overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">Add New Stock</h3>
        
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="add_stock" value="1">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block mb-2 font-bold">Medicine *</label>
                    <select name="medicine_id" required class="w-full p-2 border-2 border-gray-400">
                        <option value="">Select Medicine</option>
                        <?php foreach ($allMedicines as $med): ?>
                            <option value="<?php echo $med['id']; ?>">
                                <?php echo clean($med['name']); ?> - <?php echo clean($med['generic_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Stock Quantity *</label>
                    <input type="number" name="stock" required min="0" class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Batch Number *</label>
                    <input type="text" name="batch_number" required class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Buying Price (BDT) *</label>
                    <input type="number" name="buying_price" required step="0.01" min="0" class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Selling Price (BDT) *</label>
                    <input type="number" name="selling_price" required step="0.01" min="0" class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block mb-2 font-bold">Expiry Date</label>
                    <input type="date" name="expiry_date" class="w-full p-2 border-2 border-gray-400">
                </div>
            </div>
            
            <div class="flex gap-2 mt-4">
                <button type="submit" class="flex-1 p-3 bg-green-600 text-white font-bold">ADD STOCK</button>
                <button type="button" onclick="closeAddModal()" class="flex-1 p-3 bg-gray-400 text-white font-bold">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Stock Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border-4 border-gray-300 p-6 max-w-2xl w-full m-4">
        <h3 class="text-xl font-bold mb-4">Edit Stock</h3>
        
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="update_stock" value="1">
            <input type="hidden" name="shop_medicine_id" id="editId">
            
            <div class="mb-4">
                <div class="font-bold" id="editMedicineName"></div>
                <div class="text-sm text-gray-600" id="editMedicineGeneric"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2 font-bold">Stock Quantity *</label>
                    <input type="number" name="stock" id="editStock" required min="0" class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Batch Number *</label>
                    <input type="text" name="batch_number" id="editBatch" required class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Buying Price (BDT) *</label>
                    <input type="number" name="buying_price" id="editBuyingPrice" required step="0.01" min="0" class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Selling Price (BDT) *</label>
                    <input type="number" name="selling_price" id="editSellingPrice" required step="0.01" min="0" class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block mb-2 font-bold">Expiry Date</label>
                    <input type="date" name="expiry_date" id="editExpiry" class="w-full p-2 border-2 border-gray-400">
                </div>
            </div>
            
            <div class="flex gap-2 mt-4">
                <button type="submit" class="flex-1 p-3 bg-green-600 text-white font-bold">UPDATE STOCK</button>
                <button type="button" onclick="closeEditModal()" class="flex-1 p-3 bg-gray-400 text-white font-bold">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function editStock(item) {
    document.getElementById('editId').value = item.id;
    document.getElementById('editMedicineName').textContent = item.medicine_name;
    document.getElementById('editMedicineGeneric').textContent = item.generic_name;
    document.getElementById('editStock').value = item.stock;
    document.getElementById('editBatch').value = item.batch_number;
    document.getElementById('editBuyingPrice').value = item.buying_price;
    document.getElementById('editSellingPrice').value = item.selling_price;
    document.getElementById('editExpiry').value = item.expiry_date;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?>