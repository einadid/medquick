<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch dashboard stats
$stats = [
    'total_medicines' => 0,
    'total_stock' => 0,
    'low_stock' => 0,
    'expiring_soon' => 0,
    'expired' => 0
];

try {
    // Total medicines
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM medicines");
    $stats['total_medicines'] = $stmt->fetch()['count'];

    // Total stock
    $stmt = $pdo->query("SELECT SUM(quantity) as total FROM inventory");
    $stats['total_stock'] = $stmt->fetch()['total'] ?? 0;

    // Low stock (below reorder level)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM medicines m
        JOIN inventory i ON m.id = i.medicine_id
        WHERE i.quantity <= m.reorder_level
    ");
    $stats['low_stock'] = $stmt->fetch()['count'];

    // Expiring soon (within 30 days)
    $thirty_days = date('Y-m-d', strtotime('+30 days'));
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM medicines WHERE expiry_date BETWEEN CURDATE() AND ?");
    $stmt->execute([$thirty_days]);
    $stats['expiring_soon'] = $stmt->fetch()['count'];

    // Expired medicines
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM medicines WHERE expiry_date < CURDATE()");
    $stats['expired'] = $stmt->fetch()['count'];

} catch (PDOException $e) {
    die("Error fetching dashboard stats: " . $e->getMessage());
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-8">অ্যাডমিন ড্যাশবোর্ড</h1>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Total Medicines -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full mr-4">
                    <i class="fas fa-pills text-blue-500 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">মোট মেডিসিন</p>
                    <p class="text-2xl font-bold"><?php echo $stats['total_medicines']; ?></p>
                </div>
            </div>
        </div>

        <!-- Total Stock -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full mr-4">
                    <i class="fas fa-boxes text-green-500 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">মোট স্টক</p>
                    <p class="text-2xl font-bold"><?php echo $stats['total_stock']; ?></p>
                </div>
            </div>
        </div>

        <!-- Low Stock -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-full mr-4">
                    <i class="fas fa-exclamation-triangle text-orange-500 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">কম স্টক</p>
                    <p class="text-2xl font-bold"><?php echo $stats['low_stock']; ?></p>
                </div>
            </div>
        </div>

        <!-- Expiring Soon -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-full mr-4">
                    <i class="fas fa-clock text-yellow-500 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">৩০ দিনের মধ্যে এক্সপায়ার</p>
                    <p class="text-2xl font-bold"><?php echo $stats['expiring_soon']; ?></p>
                </div>
            </div>
        </div>

        <!-- Expired -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-full mr-4">
                    <i class="fas fa-times-circle text-red-500 text-2xl"></i>
                </div>
                <div>
                    <p class="text-gray-600">এক্সপায়ার্ড</p>
                    <p class="text-2xl font-bold"><?php echo $stats['expired']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4">কুইক অ্যাকশনস</h2>
        <div class="flex flex-wrap gap-4">
            <a href="inventory.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>নতুন মেডিসিন যোগ করুন
            </a>
            <a href="sales.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i class="fas fa-cash-register mr-2"></i>নতুন বিক্রি করুন
            </a>
            <a href="reports.php" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                <i class="fas fa-chart-bar mr-2"></i>রিপোর্ট দেখুন
            </a>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">সাম্প্রতিক বিক্রয়</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">ID</th>
                        <th class="py-2 px-4 border-b">তারিখ</th>
                        <th class="py-2 px-4 border-b">পরিমাণ</th>
                        <th class="py-2 px-4 border-b">স্ট্যাটাস</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Sample data - Replace with actual data from database -->
                    <tr>
                        <td class="py-2 px-4 border-b text-center">#1001</td>
                        <td class="py-2 px-4 border-b text-center"><?php echo date('d/m/Y'); ?></td>
                        <td class="py-2 px-4 border-b text-center">৳1,250</td>
                        <td class="py-2 px-4 border-b text-center">
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">সম্পূর্ণ</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>