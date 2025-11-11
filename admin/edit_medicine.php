<?php
session_start();
require '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get medicine ID from URL
$medicine_id = $_GET['id'] ?? 0;

// Fetch medicine details
$stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
$stmt->execute([$medicine_id]);
$medicine = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$medicine) {
    die("Medicine not found!");
}

// Update medicine
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_medicine'])) {
    $name = $_POST['name'];
    $manufacturer = $_POST['manufacturer'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $reorder_level = $_POST['reorder_level'];
    $expiry_date = $_POST['expiry_date'];

    try {
        $stmt = $pdo->prepare("
            UPDATE medicines 
            SET name = ?, manufacturer = ?, category = ?, price = ?, 
                quantity = ?, reorder_level = ?, expiry_date = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $manufacturer, $category, $price, $quantity, $reorder_level, $expiry_date, $medicine_id]);
        $success = "মেডিসিন আপডেট করা হয়েছে!";
        
        // Refresh medicine data
        $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
        $stmt->execute([$medicine_id]);
        $medicine = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "আপডেট করতে সমস্যা: " . $e->getMessage();
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">মেডিসিন এডিট করুন</h1>
    
    <a href="inventory.php" class="inline-block mb-4 text-blue-600 hover:underline">
        &larr; ইনভেন্টরিতে ফিরে যান
    </a>

    <div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl mx-auto">
        <h2 class="text-xl font-bold mb-4">মেডিসিনের তথ্য</h2>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2">মেডিসিনের নাম*</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($medicine['name']); ?>" 
                           class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">প্রস্তুতকারক*</label>
                    <input type="text" name="manufacturer" value="<?php echo htmlspecialchars($medicine['manufacturer']); ?>" 
                           class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">ক্যাটাগরি*</label>
                    <select name="category" class="w-full px-3 py-2 border rounded-lg" required>
                        <option value="Tablet" <?php echo $medicine['category'] == 'Tablet' ? 'selected' : ''; ?>>ট্যাবলেট</option>
                        <option value="Syrup" <?php echo $medicine['category'] == 'Syrup' ? 'selected' : ''; ?>>সিরাপ</option>
                        <option value="Capsule" <?php echo $medicine['category'] == 'Capsule' ? 'selected' : ''; ?>>ক্যাপসুল</option>
                        <option value="Injection" <?php echo $medicine['category'] == 'Injection' ? 'selected' : ''; ?>>ইনজেকশন</option>
                        <option value="Ointment" <?php echo $medicine['category'] == 'Ointment' ? 'selected' : ''; ?>>মলম</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">দাম (৳)*</label>
                    <input type="number" step="0.01" name="price" value="<?php echo $medicine['price']; ?>" 
                           class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">পরিমাণ*</label>
                    <input type="number" name="quantity" value="<?php echo $medicine['quantity']; ?>" 
                           class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">রিওর্ডার লেভেল*</label>
                    <input type="number" name="reorder_level" value="<?php echo $medicine['reorder_level']; ?>" 
                           class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">এক্সপায়ারি তারিখ*</label>
                    <input type="date" name="expiry_date" value="<?php echo $medicine['expiry_date']; ?>" 
                           class="w-full px-3 py-2 border rounded-lg" required>
                </div>
            </div>
            
            <div class="mt-6 flex justify-between">
                <button type="submit" name="update_medicine" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    আপডেট করুন
                </button>
                
                <a href="delete_medicine.php?id=<?php echo $medicine['id']; ?>" 
                   class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700"
                   onclick="return confirm('আপনি কি নিশ্চিত?')">
                    ডিলিট করুন
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>