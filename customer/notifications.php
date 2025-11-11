<?php
session_start();
require '../includes/db_connect.php';

// Check if user is customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: ../login.php');
    exit();
}

// Fetch notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Mark all as read
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_as_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    header('Location: notifications.php');
    exit();
}

// Get unread count for the bell icon
$unread_count = $pdo->query("
    SELECT COUNT(*) as count FROM notifications 
    WHERE user_id = {$_SESSION['user_id']} AND is_read = 0
")->fetch()['count'];
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">নোটিফিকেশন</h1>
        
        <form method="POST" action="">
            <button type="submit" name="mark_as_read" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                সব পড়া হিসেবে চিহ্নিত করুন
            </button>
        </form>
    </div>
    
    <?php if (count($notifications) > 0): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?php foreach ($notifications as $notification): ?>
            <div class="border-b p-4 hover:bg-gray-50 <?php echo !$notification['is_read'] ? 'bg-blue-50' : ''; ?>">
                <div class="flex items-start">
                    <div class="mr-4 mt-1">
                        <i class="fas 
                            <?php 
                            $icons = [
                                'order' => 'fa-shopping-bag',
                                'payment' => 'fa-credit-card',
                                'shipping' => 'fa-truck',
                                'promotion' => 'fa-percentage'
                            ];
                            echo $icons[$notification['type']] ?? 'fa-bell';
                            ?> 
                            text-xl text-blue-500"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-semibold"><?php echo htmlspecialchars($notification['title']); ?></h3>
                        <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                        <p class="text-sm text-gray-500 mt-2">
                            <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                        </p>
                    </div>
                    <?php if (!$notification['is_read']): ?>
                        <span class="ml-4 bg-blue-500 text-white text-xs px-2 py-1 rounded-full">নতুন</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="far fa-bell text-5xl text-gray-300 mb-4"></i>
            <h2 class="text-xl font-bold mb-2">কোন নোটিফিকেশন নেই</h2>
            <p class="text-gray-600">আপনার জন্য কোন নতুন নোটিফিকেশন পাওয়া যায়নি</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>