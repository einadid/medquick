<?php
session_start();
require '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Process new sale
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_sale'])) {
    $medicine_id = $_POST['medicine_id'];
    $quantity = $_POST['quantity'];
    $customer_name = $_POST['customer_name'];
    $customer_phone = $_POST['customer_phone'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get medicine details
        $stmt = $pdo->prepare("SELECT price, quantity FROM medicines WHERE id = ? FOR UPDATE");
        $stmt->execute([$medicine_id]);
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
            INSERT INTO sales (medicine_id, quantity, unit_price, total_amount, customer_name, customer_phone)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$medicine_id, $quantity, $medicine['price'], $total, $customer_name, $customer_phone]);
        
        // Update stock
        $new_quantity = $medicine['quantity'] - $quantity;
        $stmt = $pdo->prepare("UPDATE medicines SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $medicine_id]);
        
        // Commit transaction
        $pdo->commit();
        
        $success = "বিক্রয় সফলভাবে সম্পন্ন হয়েছে!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Fetch all medicines for dropdown
$medicines = $pdo->query("SELECT id, name, price FROM medicines WHERE quantity > 0 ORDER BY name")->fetchAll();

// Fetch sales history
$sales = $pdo->query("
    SELECT s.*, m.name as medicine_name 
    FROM sales s 
    JOIN medicines m ON s.medicine_id = m.id 
    ORDER BY s.sale_date DESC
")->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">বিক্রয় ব্যবস্থাপনা</h1>

    <!-- New Sale Form -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4">নতুন বিক্রয়</h2>
        
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
                    <label class="block text-gray-700 text-sm font-bold mb-2">মেডিসিন নির্বাচন করুন*</label>
                    <select name="medicine_id" id="medicine_id" class="w-full px-3 py-2 border rounded-lg" required>
                        <option value="">সিলেক্ট করুন</option>
                        <?php foreach ($medicines as $medicine): ?>
                            <option value="<?php echo $medicine['id']; ?>" 
                                    data-price="<?php echo $medicine['price']; ?>">
                                <?php echo htmlspecialchars($medicine['name']); ?> 
                                - ৳<?php echo number_format($medicine['price'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">ইউনিট প্রাইস (৳)</label>
                    <input type="text" id="unit_price" class="w-full px-3 py-2 border rounded-lg bg-gray-100" readonly>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">পরিমাণ*</label>
                    <input type="number" name="quantity" id="quantity" min="1" 
                           class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">মোট মূল্য (৳)</label>
                    <input type="text" id="total_price" class="w-full px-3 py-2 border rounded-lg bg-gray-100" readonly>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">ক্রেতার নাম</label>
                    <input type="text" name="customer_name" class="w-full px-3 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">ক্রেতার ফোন</label>
                    <input type="text" name="customer_phone" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
            
            <div class="mt-6">
                <button type="submit" name="add_sale" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    বিক্রয় সম্পন্ন করুন
                </button>
            </div>
        </form>
    </div>

    <!-- Sales History -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">বিক্রয়ের ইতিহাস</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 border">ID</th>
                        <th class="py-2 px-4 border">তারিখ</th>
                        <th class="py-2 px-4 border">মেডিসিন</th>
                        <th class="py-2 px-4 border">পরিমাণ</th>
                        <th class="py-2 px-4 border">দাম</th>
                        <th class="py-2 px-4 border">মোট</th>
                        <th class="py-2 px-4 border">ক্রেতা</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td class="py-2 px-4 border text-center"><?php echo $sale['id']; ?></td>
                        <td class="py-2 px-4 border"><?php echo date('d/m/Y H:i', strtotime($sale['sale_date'])); ?></td>
                        <td class="py-2 px-4 border"><?php echo htmlspecialchars($sale['medicine_name']); ?></td>
                        <td class="py-2 px-4 border text-center"><?php echo $sale['quantity']; ?></td>
                        <td class="py-2 px-4 border text-right">৳<?php echo number_format($sale['unit_price'], 2); ?></td>
                        <td class="py-2 px-4 border text-right">৳<?php echo number_format($sale['total_amount'], 2); ?></td>
                        <td class="py-2 px-4 border"><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Calculate total price
document.getElementById('medicine_id').addEventListener('change', calculateTotal);
document.getElementById('quantity').addEventListener('input', calculateTotal);

function calculateTotal() {
    const medicineSelect = document.getElementById('medicine_id');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unit_price');
    const totalPriceInput = document.getElementById('total_price');
    
    if (medicineSelect.value && quantityInput.value) {
        const price = parseFloat(medicineSelect.options[medicineSelect.selectedIndex].dataset.price);
        const quantity = parseInt(quantityInput.value);
        
        unitPriceInput.value = '৳' + price.toFixed(2);
        totalPriceInput.value = '৳' + (price * quantity).toFixed(2);
    } else {
        unitPriceInput.value = '';
        totalPriceInput.value = '';
    }
}
</script>

<?php include '../includes/footer.php'; ?>