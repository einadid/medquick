<?php
session_start();
require '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Add new medicine
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_medicine'])) {
    $name = $_POST['name'];
    $manufacturer = $_POST['manufacturer'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $reorder_level = $_POST['reorder_level'];
    $expiry_date = $_POST['expiry_date'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO medicines (name, manufacturer, category, price, quantity, reorder_level, expiry_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $manufacturer, $category, $price, $quantity, $reorder_level, $expiry_date]);
        $success = "মেডিসিন সফলভাবে যোগ করা হয়েছে!";
    } catch (PDOException $e) {
        $error = "মেডিসিন যোগ করতে সমস্যা হয়েছে: " . $e->getMessage();
    }
}

// Fetch all medicines
$stmt = $pdo->query("SELECT * FROM medicines ORDER BY name");
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">মেডিসিন ইনভেন্টরি ম্যানেজমেন্ট</h1>

    <!-- Add Medicine Form -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4">নতুন মেডিসিন যোগ করুন</h2>
        
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
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">মেডিসিনের নাম*</label>
                    <input type="text" name="name" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">প্রস্তুতকারক*</label>
                    <input type="text" name="manufacturer" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">ক্যাটাগরি*</label>
                    <select name="category" class="w-full px-3 py-2 border rounded-lg" required>
                        <option value="">সিলেক্ট করুন</option>
                        <option value="Tablet">ট্যাবলেট</option>
                        <option value="Syrup">সিরাপ</option>
                        <option value="Capsule">ক্যাপসুল</option>
                        <option value="Injection">ইনজেকশন</option>
                        <option value="Ointment">মলম</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">দাম (৳)*</label>
                    <input type="number" step="0.01" name="price" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">পরিমাণ*</label>
                    <input type="number" name="quantity" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">রিওর্ডার লেভেল*</label>
                    <input type="number" name="reorder_level" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">এক্সপায়ারি তারিখ*</label>
                    <input type="date" name="expiry_date" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
            </div>
            
            <div class="mt-6">
                <button type="submit" name="add_medicine" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    মেডিসিন যোগ করুন
                </button>
            </div>
        </form>
    </div>

    <!-- Medicine List -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">সকল মেডিসিনের তালিকা</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 border">ID</th>
                        <th class="py-2 px-4 border">নাম</th>
                        <th class="py-2 px-4 border">প্রস্তুতকারক</th>
                        <th class="py-2 px-4 border">ক্যাটাগরি</th>
                        <th class="py-2 px-4 border">দাম (৳)</th>
                        <th class="py-2 px-4 border">পরিমাণ</th>
                        <th class="py-2 px-4 border">রিওর্ডার লেভেল</th>
                        <th class="py-2 px-4 border">এক্সপায়ারি তারিখ</th>
                        <th class="py-2 px-4 border">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $medicine): 
                        $expiry_class = '';
                        $today = new DateTime();
                        $expiry_date = new DateTime($medicine['expiry_date']);
                        $days_until_expiry = $today->diff($expiry_date)->days;
                        
                        if ($expiry_date < $today) {
                            $expiry_class = 'bg-red-100 text-red-800';
                        } elseif ($days_until_expiry <= 30) {
                            $expiry_class = 'bg-yellow-100 text-yellow-800';
                        }
                        
                        $stock_class = $medicine['quantity'] <= $medicine['reorder_level'] ? 'bg-orange-100 text-orange-800' : '';
                    ?>
                    <tr>
                        <td class="py-2 px-4 border text-center"><?php echo $medicine['id']; ?></td>
                        <td class="py-2 px-4 border"><?php echo htmlspecialchars($medicine['name']); ?></td>
                        <td class="py-2 px-4 border"><?php echo htmlspecialchars($medicine['manufacturer']); ?></td>
                        <td class="py-2 px-4 border"><?php echo htmlspecialchars($medicine['category']); ?></td>
                        <td class="py-2 px-4 border text-right">৳<?php echo number_format($medicine['price'], 2); ?></td>
                        <td class="py-2 px-4 border text-center <?php echo $stock_class; ?>">
                            <?php echo $medicine['quantity']; ?>
                        </td>
                        <td class="py-2 px-4 border text-center"><?php echo $medicine['reorder_level']; ?></td>
                        <td class="py-2 px-4 border text-center <?php echo $expiry_class; ?>">
                            <?php echo date('d/m/Y', strtotime($medicine['expiry_date'])); ?>
                        </td>
                        <td class="py-2 px-4 border text-center">
                            <a href="edit_medicine.php?id=<?php echo $medicine['id']; ?>" 
                               class="text-blue-600 hover:text-blue-800 mr-2">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete_medicine.php?id=<?php echo $medicine['id']; ?>" 
                               class="text-red-600 hover:text-red-800"
                               onclick="return confirm('আপনি কি নিশ্চিত?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>