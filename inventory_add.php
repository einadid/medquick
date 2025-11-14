<?php
require_once 'src/session.php';
require_once 'config/database.php';
require_once 'config/constants.php';

// শুধুমাত্র Shop Admin এই পেজ অ্যাক্সেস করতে পারবে
if (!has_role(ROLE_SHOP_ADMIN)) {
    redirect('dashboard.php');
}

$shop_id = $_SESSION['shop_id'];
$errors = [];
$success_message = '';

$batch_id = $pdo->lastInsertId();
log_audit($pdo, 'STOCK_ADDED', "Batch ID: {$batch_id}, Med ID: {$medicine_id}, Qty: {$quantity}");

// সব মেডিসিনের তালিকা ড্রপডাউনের জন্য লোড করা হচ্ছে
try {
    $medicines_stmt = $pdo->query("SELECT id, name, manufacturer FROM medicines ORDER BY name ASC");
    $all_medicines = $medicines_stmt->fetchAll();
} catch (PDOException $e) {
    $all_medicines = [];
    $errors[] = "Could not load medicine list.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);

    $medicine_id = (int)$_POST['medicine_id'];
    $batch_number = trim($_POST['batch_number']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $expiry_date = $_POST['expiry_date'];

    // Validation
    if (empty($medicine_id)) { $errors[] = 'Please select a medicine.'; }
    if (empty($batch_number)) { $errors[] = 'Batch number is required.'; }
    if ($quantity <= 0) { $errors[] = 'Quantity must be a positive number.'; }
    if ($price <= 0) { $errors[] = 'Price must be a positive number.'; }
    if (empty($expiry_date)) { $errors[] = 'Expiry date is required.'; }
    
    // Check if expiry date is in the future
    if (!empty($expiry_date) && strtotime($expiry_date) < time()) {
        $errors[] = 'Expiry date cannot be in the past.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO inventory_batches (medicine_id, shop_id, batch_number, quantity, price, expiry_date) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$medicine_id, $shop_id, $batch_number, $quantity, $price, $expiry_date]);

            $success_message = "Stock added to your inventory successfully!";
            // ফর্মটি রিসেট করার জন্য ভেরিয়েবলগুলো খালি করে দেওয়া যেতে পারে

        } catch (PDOException $e) {
            // error_log($e->getMessage());
            // ইউনিক কী কনস্ট্রেইন্ট ভায়োলেশন চেক করা যেতে পারে (e.g., একই ব্যাচ নম্বর)
            if ($e->getCode() == 23000) {
                $errors[] = "This batch number might already exist for this medicine in your shop.";
            } else {
                $errors[] = 'Database error: Could not add stock.';
            }
        }
    }
}

$pageTitle = "Add to Inventory";
include 'templates/header.php';
?>

<div class="container mx-auto p-8 max-w-2xl">
    <h1 class="text-3xl font-bold mb-6">Add Stock to Your Inventory</h1>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-center">
            <p><?= e($success_message); ?></p>
        </div>
    <?php endif; ?>

    <form action="inventory_add.php" method="POST" class="space-y-6 bg-white p-6 rounded-lg shadow-md">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']); ?>">

        <div>
            <label for="medicine_id" class="block text-sm font-medium text-gray-700">Select Medicine</label>
            <select id="medicine_id" name="medicine_id" required class="w-full mt-1 p-2 border border-gray-300 rounded-md">
                <option value="">-- Choose a medicine --</option>
                <?php foreach ($all_medicines as $med): ?>
                    <option value="<?= e($med['id']); ?>">
                        <?= e($med['name']); ?> (<?= e($med['manufacturer']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="batch_number" class="block text-sm font-medium text-gray-700">Batch Number</label>
                <input type="text" id="batch_number" name="batch_number" required class="w-full mt-1 p-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                <input type="number" id="quantity" name="quantity" required min="1" class="w-full mt-1 p-2 border border-gray-300 rounded-md">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="price" class="block text-sm font-medium text-gray-700">Price per Unit (৳)</label>
                <input type="number" id="price" name="price" required step="0.01" min="0.01" class="w-full mt-1 p-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label for="expiry_date" class="block text-sm font-medium text-gray-700">Expiry Date</label>
                <input type="date" id="expiry_date" name="expiry_date" required class="w-full mt-1 p-2 border border-gray-300 rounded-md" min="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div>
            <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">Add Stock</button>
        </div>
    </form>
</div>

<?php include 'templates/footer.php'; ?>