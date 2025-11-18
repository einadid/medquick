<?php
$pageTitle = 'Medicine Management';
require_once '../includes/header.php';
require_once '../classes/Medicine.php';
requireRole('admin');

$medicineClass = new Medicine();

// Handle medicine operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    if (isset($_POST['add_medicine'])) {
        $result = $medicineClass->createMedicine($_POST, $_FILES['image'] ?? null);
        
        if ($result['success']) {
            logAudit($_SESSION['user_id'], 'medicine_created', "Medicine #{$result['id']} created");
            setFlash('success', 'Medicine added successfully');
        } else {
            setFlash('error', $result['message']);
        }
        redirect('/admin/medicines.php');
    }
    
    if (isset($_POST['update_medicine'])) {
        $medicineId = $_POST['medicine_id'];
        $result = $medicineClass->updateMedicine($medicineId, $_POST, $_FILES['image'] ?? null);
        
        if ($result['success']) {
            logAudit($_SESSION['user_id'], 'medicine_updated', "Medicine #$medicineId updated");
            setFlash('success', 'Medicine updated successfully');
        } else {
            setFlash('error', $result['message']);
        }
        redirect('/admin/medicines.php');
    }
    
    if (isset($_POST['delete_medicine'])) {
        $medicineId = $_POST['medicine_id'];
        Database::getInstance()->query("UPDATE medicines SET status = 'inactive' WHERE id = ?", [$medicineId]);
        logAudit($_SESSION['user_id'], 'medicine_deleted', "Medicine #$medicineId deactivated");
        setFlash('success', 'Medicine deactivated');
        redirect('/admin/medicines.php');
    }
}

// Get all medicines
$medicines = Database::getInstance()->fetchAll("
    SELECT m.*, c.name as category_name
    FROM medicines m
    JOIN categories c ON m.category_id = c.id
    ORDER BY m.name
");

// Get categories
$categories = Database::getInstance()->fetchAll("SELECT * FROM categories ORDER BY name");
?>

<div class="bg-white border-2 border-gray-300 p-6 mb-4">
    <h2 class="text-2xl font-bold mb-4">Medicine Management</h2>
    <button onclick="showAddModal()" class="px-6 py-3 bg-green-600 text-white font-bold">
        + ADD NEW MEDICINE
    </button>
</div>

<div class="bg-white border-2 border-gray-300 p-6">
    <table class="w-full border-2 border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left border">Image</th>
                <th class="p-2 text-left border">Name</th>
                <th class="p-2 text-left border">Generic Name</th>
                <th class="p-2 text-left border">Category</th>
                <th class="p-2 text-left border">Form</th>
                <th class="p-2 text-left border">Status</th>
                <th class="p-2 text-center border">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($medicines as $med): ?>
            <tr>
                <td class="p-2 border">
                    <img src="<?php echo $medicineClass->getImageUrl($med['image']); ?>" 
                         alt="<?php echo clean($med['name']); ?>"
                         class="w-16 h-16 object-cover border">
                </td>
                <td class="p-2 border font-bold"><?php echo clean($med['name']); ?></td>
                <td class="p-2 border"><?php echo clean($med['generic_name']); ?></td>
                <td class="p-2 border text-sm"><?php echo clean($med['category_name']); ?></td>
                <td class="p-2 border text-sm"><?php echo clean($med['dosage_form']); ?></td>
                <td class="p-2 border">
                    <span class="px-2 py-1 text-xs <?php echo $med['status'] === 'active' ? 'bg-green-100' : 'bg-red-100'; ?>">
                        <?php echo strtoupper($med['status']); ?>
                    </span>
                </td>
                <td class="p-2 border text-center">
                    <button onclick='editMedicine(<?php echo json_encode($med); ?>)' 
                            class="px-3 py-1 bg-blue-600 text-white text-sm">
                        EDIT
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Medicine Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border-4 border-gray-300 p-6 max-w-2xl w-full m-4 max-h-screen overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">Add New Medicine</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <input type="hidden" name="add_medicine" value="1">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block mb-2 font-bold">Medicine Image</label>
                    <input type="file" name="image" accept="image/*" class="w-full p-2 border-2 border-gray-400">
                    <div class="text-sm text-gray-600 mt-1">Optional. JPG, PNG, GIF, WEBP (Max 5MB). Default image will be used if not uploaded.</div>
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Medicine Name *</label>
                    <input type="text" name="name" required class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Generic Name *</label>
                    <input type="text" name="generic_name" required class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Category *</label>
                    <select name="category_id" required class="w-full p-2 border-2 border-gray-400">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo clean($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Manufacturer *</label>
                    <input type="text" name="manufacturer" required class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Dosage Form *</label>
                    <select name="dosage_form" required class="w-full p-2 border-2 border-gray-400">
                        <option value="Tablet">Tablet</option>
                        <option value="Capsule">Capsule</option>
                        <option value="Syrup">Syrup</option>
                        <option value="Injection">Injection</option>
                        <option value="Cream">Cream</option>
                        <option value="Inhaler">Inhaler</option>
                        <option value="Drops">Drops</option>
                    </select>
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Strength *</label>
                    <input type="text" name="strength" required placeholder="e.g. 500mg" class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block mb-2 font-bold">Description</label>
                    <textarea name="description" rows="3" class="w-full p-2 border-2 border-gray-400"></textarea>
                </div>
            </div>
            
            <div class="flex gap-2 mt-4">
                <button type="submit" class="flex-1 p-3 bg-green-600 text-white font-bold">ADD MEDICINE</button>
                <button type="button" onclick="closeAddModal()" class="flex-1 p-3 bg-gray-400 text-white font-bold">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Medicine Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border-4 border-gray-300 p-6 max-w-2xl w-full m-4 max-h-screen overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">Edit Medicine</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <input type="hidden" name="update_medicine" value="1">
            <input type="hidden" name="medicine_id" id="editId">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block mb-2 font-bold">Current Image</label>
                    <img id="editCurrentImage" src="" alt="Current" class="w-32 h-32 object-cover border mb-2">
                    <label class="block mb-2 font-bold">Change Image (Optional)</label>
                    <input type="file" name="image" accept="image/*" class="w-full p-2 border-2 border-gray-400">
                    <div class="text-sm text-gray-600 mt-1">Leave empty to keep current image</div>
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Medicine Name *</label>
                    <input type="text" name="name" id="editName" required class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Generic Name *</label>
                    <input type="text" name="generic_name" id="editGeneric" required class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Category *</label>
                    <select name="category_id" id="editCategory" required class="w-full p-2 border-2 border-gray-400">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo clean($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Manufacturer *</label>
                    <input type="text" name="manufacturer" id="editManufacturer" required class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Dosage Form *</label>
                    <select name="dosage_form" id="editDosageForm" required class="w-full p-2 border-2 border-gray-400">
                        <option value="Tablet">Tablet</option>
                        <option value="Capsule">Capsule</option>
                        <option value="Syrup">Syrup</option>
                        <option value="Injection">Injection</option>
                        <option value="Cream">Cream</option>
                        <option value="Inhaler">Inhaler</option>
                        <option value="Drops">Drops</option>
                    </select>
                </div>
                
                <div>
                    <label class="block mb-2 font-bold">Strength *</label>
                    <input type="text" name="strength" id="editStrength" required class="w-full p-2 border-2 border-gray-400">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block mb-2 font-bold">Description</label>
                    <textarea name="description" id="editDescription" rows="3" class="w-full p-2 border-2 border-gray-400"></textarea>
                </div>
            </div>
            
            <div class="flex gap-2 mt-4">
                <button type="submit" class="flex-1 p-3 bg-green-600 text-white font-bold">UPDATE MEDICINE</button>
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

function editMedicine(med) {
    document.getElementById('editId').value = med.id;
    document.getElementById('editName').value = med.name;
    document.getElementById('editGeneric').value = med.generic_name;
    document.getElementById('editCategory').value = med.category_id;
    document.getElementById('editManufacturer').value = med.manufacturer;
    document.getElementById('editDosageForm').value = med.dosage_form;
    document.getElementById('editStrength').value = med.strength;
    document.getElementById('editDescription').value = med.description || '';
    
    // Set current image
    const imageUrl = '<?php echo UPLOAD_URL; ?>' + (med.image || 'medicines/default-medicine.png');
    document.getElementById('editCurrentImage').src = imageUrl;
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php require_once '../includes/footer.php'; ?>