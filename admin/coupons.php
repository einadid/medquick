<?php
session_start();
require '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Add new coupon
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount_type = $_POST['discount_type'];
    $discount_value = $_POST['discount_value'];
    $min_order_amount = $_POST['min_order_amount'] ?? 0;
    $max_discount = $_POST['max_discount'] ?? null;
    $usage_limit = $_POST['usage_limit'] ?? null;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, 
                               max_discount, usage_limit, start_date, end_date, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $code,
            $discount_type,
            $discount_value,
            $min_order_amount,
            $max_discount,
            $usage_limit,
            $start_date,
            $end_date,
            $is_active
        ]);
        
        $_SESSION['success'] = "কুপন সফলভাবে যোগ করা হয়েছে!";
        header('Location: coupons.php');
        exit();
    } catch (PDOException $e) {
        $error = "কুপন যোগ করতে সমস্যা: " . $e->getMessage();
    }
}

// Fetch all coupons
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">কুপন ম্যানেজমেন্ট</h1>
    
    <!-- Add Coupon Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">নতুন কুপন যোগ করুন</h2>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">কুপন কোড*</label>
                    <input type="text" name="code" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">ডিসকাউন্ট টাইপ*</label>
                    <select name="discount_type" class="w-full px-3 py-2 border rounded-lg" required>
                        <option value="percentage">শতকরা (%)</option>
                        <option value="fixed">নির্দিষ্ট অংক (৳)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">ডিসকাউন্ট মান*</label>
                    <input type="number" step="0.01" name="discount_value" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">ন্যূনতম অর্ডার মূল্য (৳)</label>
                    <input type="number" step="0.01" name="min_order_amount" class="w-full px-3 py-2 border rounded-lg" value="0">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">সর্বোচ্চ ডিসকাউন্ট (৳)</label>
                    <input type="number" step="0.01" name="max_discount" class="w-full px-3 py-2 border rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">শুধুমাত্র শতকরা ডিসকাউন্টের জন্য প্রযোজ্য</p>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">ব্যবহার সীমা</label>
                    <input type="number" name="usage_limit" class="w-full px-3 py-2 border rounded-lg" placeholder="অসীম">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">শুরুর তারিখ*</label>
                    <input type="datetime-local" name="start_date" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">শেষ তারিখ*</label>
                    <input type="datetime-local" name="end_date" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
            </div>
            
            <div class="mt-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" class="form-checkbox" checked>
                    <span class="ml-2 text-sm text-gray-700">সক্রিয়</span>
                </label>
            </div>
            
            <div class="mt-6">
                <button type="submit" name="add_coupon" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    কুপন যোগ করুন
                </button>
            </div>
        </form>
    </div>
    
    <!-- Coupons List -->
    <div class="bg-white rounded-lg shadow-md">
        <h2 class="text-xl font-bold p-4 border-b">সকল কুপন</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 text-left">কুপন কোড</th>
                        <th class="py-2 px-4 text-left">ডিসকাউন্ট</th>
                        <th class="py-2 px-4 text-left">ন্যূনতম অর্ডার</th>
                        <th class="py-2 px-4 text-left">মেয়াদ</th>
                        <th class="py-2 px-4 text-left">ব্যবহার</th>
                        <th class="py-2 px-4 text-left">স্ট্যাটাস</th>
                        <th class="py-2 px-4 text-right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): 
                        $now = new DateTime();
                        $start_date = new DateTime($coupon['start_date']);
                        $end_date = new DateTime($coupon['end_date']);
                        
                        $is_expired = $now > $end_date;
                        $is_upcoming = $now < $start_date;
                        $is_active = $coupon['is_active'] && !$is_expired && !$is_upcoming;
                    ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-mono"><?php echo htmlspecialchars($coupon['code']); ?></td>
                        <td class="py-3 px-4">
                            <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                <?php echo $coupon['discount_value']; ?>%
                                <?php if ($coupon['max_discount']): ?>
                                    <br><span class="text-xs text-gray-500">সর্বোচ্চ ৳<?php echo $coupon['max_discount']; ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                ৳<?php echo $coupon['discount_value']; ?>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">৳<?php echo number_format($coupon['min_order_amount'], 2); ?></td>
                        <td class="py-3 px-4">
                            <div class="text-sm">
                                <div><?php echo date('d/m/Y', strtotime($coupon['start_date'])); ?></div>
                                <div>থেকে</div>
                                <div><?php echo date('d/m/Y', strtotime($coupon['end_date'])); ?></div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <?php echo $coupon['used_count']; ?> / 
                            <?php echo $coupon['usage_limit'] ?: '∞'; ?>
                        </td>
                        <td class="py-3 px-4">
                            <?php if ($is_expired): ?>
                                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">মেয়াদোত্তীর্ণ</span>
                            <?php elseif ($is_upcoming): ?>
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">আসন্ন</span>
                            <?php elseif ($coupon['is_active']): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">সক্রিয়</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">নিষ্ক্রিয়</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="edit_coupon.php?id=<?php echo $coupon['id']; ?>" 
                               class="text-blue-600 hover:text-blue-800 mr-2">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete_coupon.php?id=<?php echo $coupon['id']; ?>" 
                               class="text-red-600 hover:text-red-800"
                               onclick="return confirm('আপনি কি এই কুপনটি মুছে ফেলতে চান?')">
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