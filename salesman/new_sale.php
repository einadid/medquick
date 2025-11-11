<?php
session_start();
require '../includes/db_connect.php';

// Check if user is salesman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'salesman') {
    header('Location: ../login.php');
    exit();
}

$shop_id = $_SESSION['shop_id'];

// Fetch medicines for this shop
$stmt = $pdo->prepare("
    SELECT m.id, m.name, m.price, i.quantity 
    FROM medicines m
    JOIN inventory i ON m.id = i.medicine_id
    WHERE i.shop_id = ? 
    AND i.quantity > 0
    ORDER BY m.name
");
$stmt->execute([$shop_id]);
$medicines = $stmt->fetchAll();

// Process new sale
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_sale'])) {
    $medicine_id = $_POST['medicine_id'];
    $quantity = $_POST['quantity'];
    $customer_name = $_POST['customer_name'] ?? 'Walk-in Customer';
    $customer_phone = $_POST['customer_phone'] ?? '';
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get medicine details
        $stmt = $pdo->prepare("
            SELECT m.price, i.quantity 
            FROM medicines m
            JOIN inventory i ON m.id = i.medicine_id
            WHERE m.id = ? 
            AND i.shop_id = ?
            FOR UPDATE
        ");
        $stmt->execute([$medicine_id, $shop_id]);
        $medicine = $stmt->fetch();
        
        if (!$medicine) {
            throw new Exception("মেডিসিন পাওয়া যায়নি!");
        }
        
        // Check stock
        if ($medicine['quantity'] < $quantity) {
            throw new Exception("পর্যাপ্ত স্টক নেই! মজুত: " . $medicine['quantity']);
        }
        
        // Calculate total
        $total = $medicine['price'] * $quantity;
        
        // Insert sale
        $stmt = $pdo->prepare("
            INSERT INTO sales (
                medicine_id, 
                quantity, 
                unit_price, 
                total_amount, 
                salesman_id, 
                shop_id,
                customer_name,
                customer_phone
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $medicine_id,
            $quantity,
            $medicine['price'],
            $total,
            $_SESSION['user_id'],
            $shop_id,
            $customer_name,
            $customer_phone
        ]);
        
        // Update stock
        $new_quantity = $medicine['quantity'] - $quantity;
        $stmt = $pdo->prepare("
            UPDATE inventory 
            SET quantity = ? 
            WHERE medicine_id = ? 
            AND shop_id = ?
        ");
        $stmt->execute([$new_quantity, $medicine_id, $shop_id]);
        
        // Commit transaction
        $pdo->commit();
        
        $success = "বিক্রয় সফলভাবে সম্পন্ন হয়েছে!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">নতুন বিক্রয়</h1>
    
    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $success; ?>
            <a href="new_sale.php" class="text-blue-600 hover:underline ml-4">আরেকটি বিক্রয় করুন</a>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- New Sale Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">বিক্রয় তথ্য</h2>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="medicine_id">মেডিসিন নির্বাচন করুন *</label>
                    <select name="medicine_id" id="medicine_id" class="w-full px-3 py-2 border rounded-lg" required>
                        <option value="">সিলেক্ট করুন</option>
                        <?php foreach ($medicines as $medicine): ?>
                            <option value="<?php echo $medicine['id']; ?>" 
                                    data-price="<?php echo $medicine['price']; ?>"
                                    data-stock="<?php echo $medicine['quantity']; ?>">
                                <?php echo htmlspecialchars($medicine['name']); ?> 
                                - ৳<?php echo number_format($medicine['price'], 2); ?> 
                                (স্টক: <?php echo $medicine['quantity']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="quantity">পরিমাণ *</label>
                    <input type="number" name="quantity" id="quantity" min="1" value="1" 
                           class="w-full px-3 py-2 border rounded-lg" required>
                    <p class="text-sm text-gray-500 mt-1">সর্বোচ্চ: <span id="max_quantity">0</span> পিস</p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="customer_name">গ্রাহকের নাম</label>
                    <input type="text" name="customer_name" id="customer_name" 
                           class="w-full px-3 py-2 border rounded-lg" placeholder="নাম দিন (ঐচ্ছিক)">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="customer_phone">গ্রাহকের ফোন</label>
                    <input type="text" name="customer_phone" id="customer_phone" 
                           class="w-full px-3 py-2 border rounded-lg" placeholder="ফোন নম্বর দিন (ঐচ্ছিক)">
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <h3 class="font-semibold mb-2">বিলিং বিবরণ</h3>
                    <div class="flex justify-between">
                        <span>ইউনিট প্রাইস:</span>
                        <span id="unit_price">৳0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>মোট:</span>
                        <span id="total_amount" class="font-bold">৳0.00</span>
                    </div>
                </div>
                
                <button type="submit" name="add_sale" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-bold">
                    বিক্রয় সম্পন্ন করুন
                </button>
            </form>
        </div>
        
        <!-- Help Section -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">সাহায্য</h2>
                <ul class="space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                        <span>গ্রাহকের নাম না দিলে "Walk-in Customer" হিসেবে সেভ হবে</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                        <span>স্টকে যত পণ্য আছে তার বেশি বিক্রি করা যাবে না</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                        <span>বিক্রয় সম্পন্ন হলে স্টক স্বয়ংক্রিয়ভাবে আপডেট হবে</span>
                    </li>
                </ul>
            </div>
            
            <!-- Recent Sales -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">সাম্প্রতিক বিক্রয়</h2>
                <?php
                $stmt = $pdo->prepare("
                    SELECT s.*, m.name as medicine_name 
                    FROM sales s
                    JOIN medicines m ON s.medicine_id = m.id
                    WHERE s.shop_id = ?
                    ORDER BY s.sale_date DESC
                    LIMIT 5
                ");
                $stmt->execute([$shop_id]);
                $recent_sales = $stmt->fetchAll();
                ?>
                
                <?php if (count($recent_sales) > 0): ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_sales as $sale): ?>
                        <div class="border-b pb-3">
                            <div class="flex justify-between">
                                <span class="font-semibold"><?php echo htmlspecialchars($sale['medicine_name']); ?></span>
                                <span>৳<?php echo number_format($sale['total_amount'], 2); ?></span>
                            </div>
                            <div class="text-sm text-gray-600">
                                <?php echo date('d/m/Y H:i', strtotime($sale['sale_date'])); ?> 
                                | <?php echo $sale['quantity']; ?> পিস
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">কোন বিক্রয় রেকর্ড পাওয়া যায়নি</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Update price and stock info when medicine is selected
document.getElementById('medicine_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const price = selectedOption.dataset.price || 0;
    const stock = selectedOption.dataset.stock || 0;
    
    document.getElementById('unit_price').textContent = '৳' + parseFloat(price).toFixed(2);
    document.getElementById('max_quantity').textContent = stock;
    
    // Update total amount
    updateTotal();
});

// Update total when quantity changes
document.getElementById('quantity').addEventListener('input', function() {
    updateTotal();
});

function updateTotal() {
    const price = parseFloat(document.getElementById('medicine_id').selectedOptions[0]?.dataset.price || 0);
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const maxQuantity = parseInt(document.getElementById('max_quantity').textContent) || 0;
    
    // Don't allow quantity more than stock
    if (quantity > maxQuantity) {
        document.getElementById('quantity').value = maxQuantity;
        return;
    }
    
    const total = price * quantity;
    document.getElementById('total_amount').textContent = '৳' + total.toFixed(2);
}
</script>

<?php include '../includes/footer.php'; ?>