<?php
require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

// শুধুমাত্র Admin এবং Shop Admin এই পেজ অ্যাক্সেস করতে পারবে
if (!is_logged_in() || (!has_role(ROLE_ADMIN) && !has_role(ROLE_SHOP_ADMIN))) {
    // এখানে একটি "access denied" পেজ দেখানো ভালো, আপাতত ড্যাশবোর্ডে পাঠানো হচ্ছে
    redirect('dashboard.php');
}

$errors = [];
$name = $manufacturer = $category = $reorder_level = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);

    $name = trim($_POST['name']);
    $manufacturer = trim($_POST['manufacturer']);
    $category = trim($_POST['category']);
    $reorder_level = (int)$_POST['reorder_level'];

    // Validation
    if (empty($name)) { $errors[] = 'Medicine name is required.'; }
    if (empty($manufacturer)) { $errors[] = 'Manufacturer is required.'; }
    if (empty($category)) { $errors[] = 'Category is required.'; }
    if ($reorder_level <= 0) { $errors[] = 'Re-order level must be a positive number.'; }

    // Image Upload Handling
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/medicines/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $image_info = getimagesize($_FILES['image']['tmp_name']);
        $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];

        if ($image_info && in_array($image_info[2], $allowed_types)) {
            if ($_FILES['image']['size'] < 2000000) { // 2MB limit
                $filename = uniqid('med_', true) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $destination = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $image_path = $destination;
                } else {
                    $errors[] = 'Failed to move uploaded file.';
                }
            } else {
                $errors[] = 'Image size must be less than 2MB.';
            }
        } else {
            $errors[] = 'Invalid image format. Only JPG, PNG, GIF are allowed.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO medicines (name, manufacturer, category, reorder_level, image_path) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $manufacturer, $category, $reorder_level, $image_path]);

            // একটি 성공 বার্তা সেট করে ক্যাটালগ পেজে রিডাইরেক্ট করা যেতে পারে
            $_SESSION['success_message'] = 'Medicine added successfully!';
            $medicine_id = $pdo->lastInsertId();
            log_audit($pdo, 'MEDICINE_ADDED', "Medicine ID: {$medicine_id}, Name: {$name}");
            redirect('catalog.php');

        } catch (PDOException $e) {
            $errors[] = 'Database error: Could not add medicine.';
            // error_log($e->getMessage());
        }
    }
}

$pageTitle = "Add New Medicine";
include 'templates/header.php';
?>

<div class="container mx-auto p-8 max-w-2xl">
    <h1 class="text-3xl font-bold mb-6">Add a New Medicine to Catalog</h1>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="medicine_add.php" method="POST" enctype="multipart/form-data" class="space-y-6 bg-white p-6 rounded-lg shadow-md">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Medicine Name</label>
            <input type="text" id="name" name="name" required class="w-full mt-1 p-2 border border-gray-300 rounded-md" value="<?= e($name); ?>">
        </div>

        <div>
            <label for="manufacturer" class="block text-sm font-medium text-gray-700">Manufacturer</label>
            <input type="text" id="manufacturer" name="manufacturer" required class="w-full mt-1 p-2 border border-gray-300 rounded-md" value="<?= e($manufacturer); ?>">
        </div>

        <div>
            <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
            <input type="text" id="category" name="category" required class="w-full mt-1 p-2 border border-gray-300 rounded-md" placeholder="e.g., Antibiotic, Painkiller, Vitamin" value="<?= e($category); ?>">
        </div>

        <div>
            <label for="reorder_level" class="block text-sm font-medium text-gray-700">Re-order Level</label>
            <input type="number" id="reorder_level" name="reorder_level" required class="w-full mt-1 p-2 border border-gray-300 rounded-md" placeholder="e.g., 10" value="<?= e($reorder_level); ?>">
            <p class="text-xs text-gray-500 mt-1">Alert will be shown when stock falls below this number.</p>
        </div>

        <div>
            <label for="image" class="block text-sm font-medium text-gray-700">Medicine Image (Optional)</label>
            <input type="file" id="image" name="image" class="w-full mt-1 p-2 border border-gray-300 rounded-md">
        </div>

        <div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Add Medicine</button>
        </div>
    </form>
</div>

<?php include 'templates/footer.php'; ?>