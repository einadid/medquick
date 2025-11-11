<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickMed - Pharmacy Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">

    <!-- Navbar -->
    <nav class="bg-blue-600 text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <a href="../index.php" class="text-2xl font-bold">QuickMed</a>

            <!-- Navigation Links -->
            <div class="hidden md:flex space-x-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <a href="../admin/index.php" class="hover:bg-blue-700 px-3 py-2 rounded">ড্যাশবোর্ড</a>
                        <a href="../admin/inventory.php" class="hover:bg-blue-700 px-3 py-2 rounded">ইনভেন্টরি</a>
                        <a href="../admin/sales.php" class="hover:bg-blue-700 px-3 py-2 rounded">বিক্রয়</a>
                        <a href="../admin/reports.php" class="hover:bg-blue-700 px-3 py-2 rounded">রিপোর্ট</a>
                    <?php elseif($_SESSION['role'] == 'salesman'): ?>
                        <a href="../salesman/index.php" class="hover:bg-blue-700 px-3 py-2 rounded">ড্যাশবোর্ড</a>
                    <?php else: ?>
                        <a href="../customer/index.php" class="hover:bg-blue-700 px-3 py-2 rounded">শপ</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- User Section -->
            <div class="flex items-center space-x-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span>স্বাগতম, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-700 px-4 py-2 rounded">লগআউট</a>
                <?php else: ?>
                    <a href="login.php" class="hover:underline">লগইন</a>
                    <a href="signup.php" class="bg-green-500 hover:bg-green-700 px-4 py-2 rounded">সাইন আপ</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto p-4">
        <!-- এখানে তোমার মূল পেজের কন্টেন্ট আসবে -->
         <!-- Notification Bell -->
<div class="relative ml-4" x-data="{ open: false }">
    <button @click="open = !open" class="text-gray-600 hover:text-blue-600 focus:outline-none">
        <i class="far fa-bell text-xl"></i>
        <?php
$unread_count = 0; // ডিফল্ট মান
if (isset($notifications)) {
    $unread_count = count(array_filter($notifications, function($n) {
        return !$n['read']; // ধরে নিচ্ছি 'read' ফিল্ড আছে
    }));
}
?>
<!-- ... existing code ... -->
<?php if(isset($_SESSION['user_id'])): ?>
    <?php if($_SESSION['role'] == 'admin'): ?>
        <!-- Admin links -->
        <a href="../admin/index.php" class="hover:bg-blue-700 px-3 py-2 rounded">ড্যাশবোর্ড</a>
        <a href="../admin/inventory.php" class="hover:bg-blue-700 px-3 py-2 rounded">ইনভেন্টরি</a>
        <a href="../admin/sales.php" class="hover:bg-blue-700 px-3 py-2 rounded">বিক্রয়</a>
        <a href="../admin/reports.php" class="hover:bg-blue-700 px-3 py-2 rounded">রিপোর্ট</a>
    <?php elseif($_SESSION['role'] == 'salesman'): ?>
        <!-- Salesman links -->
        <a href="../salesman/index.php" class="hover:bg-blue-700 px-3 py-2 rounded">ড্যাশবোর্ড</a>
        <a href="../salesman/new_sale.php" class="hover:bg-blue-700 px-3 py-2 rounded">নতুন বিক্রয়</a>
        <a href="../salesman/inventory.php" class="hover:bg-blue-700 px-3 py-2 rounded">স্টক</a>
        <a href="../salesman/sales_report.php" class="hover:bg-blue-700 px-3 py-2 rounded">বিক্রয় রিপোর্ট</a>
    <?php else: ?>
        <!-- Customer links -->
        <a href="../customer/index.php" class="hover:bg-blue-700 px-3 py-2 rounded">শপ</a>
        <a href="../customer/orders.php" class="hover:bg-blue-700 px-3 py-2 rounded">আমার অর্ডার</a>
    <?php endif; ?>
<?php endif; ?>
<!-- ... existing code ... -->


    </button>
    
    <!-- Notification Dropdown -->
    <div x-show="open" @click.away="open = false" 
         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-50" 
         style="display: none;">
        <div class="p-4 border-b">
            <h3 class="font-bold">নোটিফিকেশন</h3>
        </div>
        
        <div class="max-h-96 overflow-y-auto">
            <?php foreach ($notifications as $notification): ?>
            <a href="notifications.php" class="block border-b hover:bg-gray-50">
                <div class="p-4">
                    <h4 class="font-semibold"><?php echo htmlspecialchars($notification['title']); ?></h4>
                    <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($notification['message']); ?></p>
                    <p class="text-xs text-gray-500 mt-1">
                        <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                    </p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div class="p-2 border-t">
            <a href="notifications.php" class="block text-center text-blue-600 hover:underline py-1">
                সব নোটিফিকেশন দেখুন
            </a>
        </div>
    </div>
</div>
    </main>

</body>
</html>
