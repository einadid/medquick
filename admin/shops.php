<?php
$pageTitle = 'Shop Management';
require_once '../includes/header.php';
requireRole('admin');

// Handle shop operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }

    // ADD SHOP
    if (isset($_POST['add_shop'])) {
        $data = [
            'name' => $_POST['name'],
            'address' => $_POST['address'],
            'city' => $_POST['city'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'],
            'status' => 'active'
        ];

        $shopId = Database::getInstance()->insert('shops', $data);
        logAudit($_SESSION['user_id'], 'shop_created', "Shop #$shopId created");
        setFlash('success', 'Shop added successfully');
        redirect('/admin/shops.php');
    }

    // UPDATE SHOP
    if (isset($_POST['update_shop'])) {
        $shopId = $_POST['shop_id'];
        $data = [
            'name' => $_POST['name'],
            'address' => $_POST['address'],
            'city' => $_POST['city'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email']
        ];

        Database::getInstance()->update('shops', $data, 'id = :id', ['id' => $shopId]);
        logAudit($_SESSION['user_id'], 'shop_updated', "Shop #$shopId updated");
        setFlash('success', 'Shop updated successfully');
        redirect('/admin/shops.php');
    }

    // DELETE SHOP
    if (isset($_POST['delete_shop'])) {
        $shopId = $_POST['shop_id'];

        // Check if shop has assigned users
        $assignedUsers = Database::getInstance()->fetchOne("
            SELECT COUNT(*) as count FROM users WHERE shop_id = ?
        ", [$shopId])['count'];

        // Check if shop has stock
        $hasStock = Database::getInstance()->fetchOne("
            SELECT COUNT(*) as count FROM shop_medicines WHERE shop_id = ?
        ", [$shopId])['count'];

        if ($assignedUsers > 0) {
            setFlash('error', "Cannot delete shop. $assignedUsers users are assigned to this shop.");
        } elseif ($hasStock > 0) {
            setFlash('error', "Cannot delete shop. Shop has $hasStock medicine items in stock.");
        } else {
            Database::getInstance()->delete('shops', 'id = :id', ['id' => $shopId]);
            logAudit($_SESSION['user_id'], 'shop_deleted', "Shop #$shopId deleted");
            setFlash('success', 'Shop deleted successfully');
        }

        redirect('/admin/shops.php');
    }
}

// Get all shops
$shops = Database::getInstance()->fetchAll("SELECT * FROM shops ORDER BY name");
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold mb-4">Shop Management</h2>
    <button onclick="showAddModal()" class="px-6 py-3 bg-green-600 text-white font-bold">+ ADD NEW SHOP</button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($shops as $shop): ?>
    <div class="bg-white border-2 border-gray-300 p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-xl font-bold"><?php echo clean($shop['name']); ?></h3>
                <div class="text-sm text-gray-600"><?php echo clean($shop['city']); ?></div>
            </div>
            <span class="px-2 py-1 text-xs <?php echo $shop['status'] === 'active' ? 'bg-green-100' : 'bg-red-100'; ?>">
                <?php echo strtoupper($shop['status']); ?>
            </span>
        </div>

        <table class="w-full text-sm mb-4">
            <tr><td class="py-1 text-gray-600">Address:</td><td class="py-1"><?php echo clean($shop['address']); ?></td></tr>
            <tr><td class="py-1 text-gray-600">Phone:</td><td class="py-1"><?php echo clean($shop['phone']); ?></td></tr>
            <tr><td class="py-1 text-gray-600">Email:</td><td class="py-1"><?php echo clean($shop['email']); ?></td></tr>
        </table>

        <div class="grid grid-cols-2 gap-2">
            <button onclick='editShop(<?php echo json_encode($shop); ?>)' class="p-2 bg-blue-600 text-white font-bold">EDIT</button>
            <button onclick='deleteShop(<?php echo $shop['id']; ?>, "<?php echo addslashes($shop['name']); ?>")' class="p-2 bg-red-600 text-white font-bold">DELETE</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ADD SHOP MODAL -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border-4 border-gray-300 p-6 max-w-2xl w-full m-4">
        <h3 class="text-xl font-bold mb-4">Add New Shop</h3>
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="add_shop" value="1">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2 font-bold">Shop Name *</label>
                    <input type="text" name="name" required class="w-full p-2 border-2 border-gray-400">
                </div>
                <div>
                    <label class="block mb-2 font-bold">City *</label>
                    <input type="text" name="city" required class="w-full p-2 border-2 border-gray-400">
                </div>
                <div class="md:col-span-2">
                    <label class="block mb-2 font-bold">Address *</label>
                    <textarea name="address" required rows="2" class="w-full p-2 border-2 border-gray-400"></textarea>
                </div>
                <div>
                    <label class="block mb-2 font-bold">Phone *</label>
                    <input type="text" name="phone" required class="w-full p-2 border-2 border-gray-400">
                </div>
                <div>
                    <label class="block mb-2 font-bold">Email *</label>
                    <input type="email" name="email" required class="w-full p-2 border-2 border-gray-400">
                </div>
            </div>

            <div class="flex gap-2 mt-4">
                <button type="submit" class="flex-1 p-3 bg-green-600 text-white font-bold">ADD SHOP</button>
                <button type="button" onclick="closeAddModal()" class="flex-1 p-3 bg-gray-400 text-white font-bold">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT SHOP MODAL -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border-4 border-gray-300 p-6 max-w-2xl w-full m-4">
        <h3 class="text-xl font-bold mb-4">Edit Shop</h3>
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="update_shop" value="1">
            <input type="hidden" name="shop_id" id="editId">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2 font-bold">Shop Name *</label>
                    <input type="text" name="name" id="editName" required class="w-full p-2 border-2 border-gray-400">
                </div>
                <div>
                    <label class="block mb-2 font-bold">City *</label>
                    <input type="text" name="city" id="editCity" required class="w-full p-2 border-2 border-gray-400">
                </div>
                <div class="md:col-span-2">
                    <label class="block mb-2 font-bold">Address *</label>
                    <textarea name="address" id="editAddress" required rows="2" class="w-full p-2 border-2 border-gray-400"></textarea>
                </div>
                <div>
                    <label class="block mb-2 font-bold">Phone *</label>
                    <input type="text" name="phone" id="editPhone" required class="w-full p-2 border-2 border-gray-400">
                </div>
                <div>
                    <label class="block mb-2 font-bold">Email *</label>
                    <input type="email" name="email" id="editEmail" required class="w-full p-2 border-2 border-gray-400">
                </div>
            </div>

            <div class="flex gap-2 mt-4">
                <button type="submit" class="flex-1 p-3 bg-green-600 text-white font-bold">UPDATE SHOP</button>
                <button type="button" onclick="closeEditModal()" class="flex-1 p-3 bg-gray-400 text-white font-bold">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE SHOP MODAL -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border-4 border-red-500 p-6 max-w-md w-full m-4">
        <h3 class="text-xl font-bold mb-4 text-red-700">⚠️ Delete Shop</h3>

        <p class="mb-4">Are you sure you want to delete <strong id="deleteShopName"></strong>?</p>

        <div class="bg-yellow-50 border border-yellow-400 p-3 mb-4 text-sm">
            <strong>Warning:</strong> This action cannot be undone.
        </div>

        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="delete_shop" value="1">
            <input type="hidden" name="shop_id" id="deleteShopId">

            <div class="flex gap-2">
                <button type="submit" class="flex-1 p-3 bg-red-600 text-white font-bold">YES, DELETE</button>
                <button type="button" onclick="closeDeleteModal()" class="flex-1 p-3 bg-gray-400 text-white font-bold">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() { document.getElementById('addModal').classList.remove('hidden'); }
function closeAddModal() { document.getElementById('addModal').classList.add('hidden'); }

function editShop(shop) {
    document.getElementById('editId').value = shop.id;
    document.getElementById('editName').value = shop.name;
    document.getElementById('editCity').value = shop.city;
    document.getElementById('editAddress').value = shop.address;
    document.getElementById('editPhone').value = shop.phone;
    document.getElementById('editEmail').value = shop.email;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }

function deleteShop(id, name) {
    document.getElementById('deleteShopId').value = id;
    document.getElementById('deleteShopName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
}
function closeDeleteModal() { document.getElementById('deleteModal').classList.add('hidden'); }
</script>

<?php require_once '../includes/footer.php'; ?>
