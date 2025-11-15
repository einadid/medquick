<?php
// FILE: medicine_add.php (Upgraded with Description field)
// PURPOSE: Allows Admins/ShopAdmins to add new medicines with a description.

require_once 'src/session.php';
require_once 'config/database.php';

// Security check
if (!is_logged_in() || (!has_role(ROLE_ADMIN) && !has_role(ROLE_SHOP_ADMIN))) {
    redirect('dashboard.php');
}

$errors = [];
$name = $manufacturer = $category = $description = $reorder_level = '';

// Fetch existing manufacturers and categories for datalist suggestions
try {
    $existing_manufacturers = $pdo->query("SELECT DISTINCT manufacturer FROM medicines ORDER BY manufacturer ASC")->fetchAll(PDO::FETCH_COLUMN);
    $existing_categories = $pdo->query("SELECT DISTINCT category FROM medicines ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Medicine Add suggestion fetch error: " . $e->getMessage());
    $existing_manufacturers = [];
    $existing_categories = [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    
    $name = trim($_POST['name']);
    $manufacturer = trim($_POST['manufacturer']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']); // New field
    $reorder_level = (int)$_POST['reorder_level'];

    // Validation
    if (empty($name)) { $errors[] = 'Medicine name is required.'; }
    if (empty($manufacturer)) { $errors[] = 'Manufacturer is required.'; }
    if (empty($category)) { $errors[] = 'Category is required.'; }
    if ($reorder_level <= 0) { $errors[] = 'Re-order level must be a positive number.'; }

    // Image Upload Handling (same as before)
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/medicines/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
        $image_info = getimagesize($_FILES['image']['tmp_name']);
        if ($image_info && in_array($image_info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF])) {
            if ($_FILES['image']['size'] < 2000000) { // 2MB
                $filename = uniqid('med_', true) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = $upload_dir . $filename;
                } else { $errors[] = 'Failed to move uploaded file.'; }
            } else { $errors[] = 'Image size must be less than 2MB.'; }
        } else { $errors[] = 'Invalid image format.'; }
    }

    if (empty($errors)) {
        try {
            // NEW: Updated SQL to include description
            $stmt = $pdo->prepare("INSERT INTO medicines (name, manufacturer, category, description, reorder_level, image_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $manufacturer, $category, $description, $reorder_level, $image_path]);
            
            $medicine_id = $pdo->lastInsertId();
            log_audit($pdo, 'MEDICINE_ADDED', "Medicine ID: {$medicine_id}, Name: {$name}");
            
            $_SESSION['success_message'] = 'Medicine "' . $name . '" added successfully! You can now add stock for it.';
            redirect('inventory_add.php');

        } catch (PDOException $e) {
            error_log("Medicine Add DB insert error: " . $e->getMessage());
            $errors[] = 'Database error: Could not add medicine. It might already exist.';
        }
    }
}

$pageTitle = "Add New Medicine to Catalog";
include 'templates/header.php';
?>

<div class="fade-in bg-slate-50 py-12">
    <div class="container mx-auto px-4 sm:px-6 max-w-2xl">
        <h1 class="text-3xl font-bold text-slate-800 mb-8">Add New Medicine to Catalog</h1>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <ul class="list-disc pl-5"><?php foreach ($errors as $error): ?><li><?= e($error); ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-lg shadow-md border">
            <form action="medicine_add.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Medicine Name</label>
                    <input type="text" id="name" name="name" required class="mt-1 w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500" value="<?= e($name); ?>">
                </div>

                <div>
                    <label for="manufacturer" class="block text-sm font-medium text-gray-700">Manufacturer</label>
                    <input type="text" id="manufacturer" name="manufacturer" required list="manufacturer-list" class="mt-1 w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500" value="<?= e($manufacturer); ?>">
                    <datalist id="manufacturer-list">
                        <?php foreach ($existing_manufacturers as $man): ?><option value="<?= e($man) ?>"><?php endforeach; ?>
                    </datalist>
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <input type="text" id="category" name="category" required list="category-list" class="mt-1 w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500" value="<?= e($category); ?>" placeholder="e.g., Painkiller, Antibiotic">
                    <datalist id="category-list">
                        <?php foreach ($existing_categories as $cat): ?><option value="<?= e($cat) ?>"><?php endforeach; ?>
                    </datalist>
                </div>

                <!-- NEW: Description Field -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                    <textarea id="description" name="description" rows="4" class="mt-1 w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500" placeholder="Enter detailed information about the medicine..."><?= e($description); ?></textarea>
                </div>

                <div>
                    <label for="reorder_level" class="block text-sm font-medium text-gray-700">Re-order Level</label>
                    <input type="number" id="reorder_level" name="reorder_level" required min="1" class="mt-1 w-full p-3 border border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500" placeholder="e.g., 10" value="<?= e($reorder_level); ?>">
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">Medicine Image (Optional)</label>
                    <input type="file" id="image" name="image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                </div>

                <div class="pt-5 border-t">
                    <!-- UPDATED: Themed Button -->
                    <button type="submit" class="w-full btn-primary text-lg">
                        <i class="fas fa-plus-circle mr-2"></i> Add Medicine to Catalog
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>