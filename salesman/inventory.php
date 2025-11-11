<?php
session_start();
require '../includes/db_connect.php';

// Check if user is salesman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'salesman') {
    header('Location: ../login.php');
    exit();
}

// Get salesman's shop
$stmt = $pdo->prepare("SELECT shop_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !$user['shop_id']) {
    die("Invalid salesman account!");
}

$shop_id = $user['shop_id'];

// Fetch inventory for this shop
$stmt = $pdo->prepare("
    SELECT m.id, m.name, m.manufacturer, m.category, i.quantity, m.price, m.expiry_date, m.reorder_level
    FROM inventory i
    JOIN medicines m ON i.medicine_id = m.id
    WHERE i.shop_id = ?
    ORDER BY m.name
");
$stmt->execute([$shop_id]);
$inventory = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">ইনভেন্টরি ম্যানেজমেন্ট</h1>
    
    <!-- Inventory Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full mr-4">
                    <i class="fas fa-pills text-blue-500 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">মোট মেডিসিন</p>
                    <p class="text-2xl font-bold"><?php echo count($inventory); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-full mr-4">
                    <i class="fas fa-exclamation-triangle text-orange-500 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">কম স্টক</p>
                    <p class="text-2xl font-bold">
                        <?php 
                        $low_stock_count = 0;
                        foreach ($inventory as $item) {
                            if ($item['quantity'] <= $item['reorder_level']) {
                                $low_stock_count++;
                            }
                        }
                        echo $low_stock_count;
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-full mr-4">
                    <i class="fas fa-clock text-red-500 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">শীঘ্রই এক্সপায়ার</p>
                    <p class="text-2xl font-bold">
                        <?php 
                        $expiring_soon = 0;
                        $thirty_days = date('Y-m-d', strtotime('+30 days'));
                        foreach ($inventory as $item) {
                            if ($item['expiry_date'] <= $thirty_days) {
                                $expiring_soon++;
                            }
                        }
                        echo $expiring_soon;
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Inventory Table -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">সকল মেডিসিনের তালিকা</h2>
        
        <div class="mb-4">
            <input type="text" id="searchInventory" placeholder="মেডিসিন খুঁজুন..." 
                   class="w-full md:w-1/2 px-3 py-2 border rounded-lg">
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border" id="inventoryTable">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 border">ID</th>
                        <th class="py-2 px-4 border">নাম</th>
                        <th class="py-2 px-4 border">প্রস্তুতকারক</th>
                        <th class="py-2 px-4 border">ক্যাটাগরি</th>
                        <th class="py-2 px-4 border">দাম (৳)</th>
                        <th class="py-2 px-4 border">পরিমাণ</th>
                        <th class="py-2 px-4 border">এক্সপায়ারি তারিখ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item): 
                        $expiry_class = '';
                        $stock_class = '';
                        
                        $today = new DateTime();
                        $expiry_date = new DateTime($item['expiry_date']);
                        $days_until_expiry = $today->diff($expiry_date)->days;
                        
                        if ($expiry_date < $today) {
                            $expiry_class = 'bg-red-100 text-red-800';
                        } elseif ($days_until_expiry <= 30) {
                            $expiry_class = 'bg-yellow-100 text-yellow-800';
                        }
                        
                        if ($item['quantity'] <= $item['reorder_level']) {
                            $stock_class = 'bg-orange-100 text-orange-800';
                        }
                    ?>
                    <tr>
                        <td class="py-2 px-4 border text-center"><?php echo $item['id']; ?></td>
                        <td class="py-2 px-4 border"><?php echo htmlspecialchars($item['name']); ?></td>
                        <td class="py-2 px-4 border"><?php echo htmlspecialchars($item['manufacturer']); ?></td>
                        <td class="py-2 px-4 border"><?php echo htmlspecialchars($item['category']); ?></td>
                        <td class="py-2 px-4 border text-right">৳<?php echo number_format($item['price'], 2); ?></td>
                        <td class="py-2 px-4 border text-center <?php echo $stock_class; ?>">
                            <?php echo $item['quantity']; ?>
                        </td>
                        <td class="py-2 px-4 border text-center <?php echo $expiry_class; ?>">
                            <?php echo date('d/m/Y', strtotime($item['expiry_date'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInventory').addEventListener('input', function() {
    const searchText = this.value.toLowerCase();
    const rows = document.querySelectorAll('#inventoryTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchText) ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>