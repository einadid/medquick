<?php
session_start();
require '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch inventory report
$stmt = $pdo->query("
    SELECT 
        m.id,
        m.name,
        m.category,
        m.manufacturer,
        m.quantity as current_stock,
        m.reorder_level,
        m.expiry_date,
        (SELECT SUM(oi.quantity) 
         FROM order_items oi 
         JOIN orders o ON oi.order_id = o.id 
         WHERE oi.medicine_id = m.id 
         AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as sold_last_30_days,
        (SELECT SUM(oi.quantity) 
         FROM order_items oi 
         JOIN orders o ON oi.order_id = o.id 
         WHERE oi.medicine_id = m.id) as total_sold
    FROM medicines m
    ORDER BY m.quantity ASC, m.expiry_date ASC
");
$inventory = $stmt->fetchAll();

// Export to Excel
if (isset($_GET['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="inventory_report_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='9'><h2>ইনভেন্টরি রিপোর্ট</h2></th></tr>";
    
    // Headers
    echo "<tr>";
    echo "<th>আইডি</th>";
    echo "<th>নাম</th>";
    echo "<th>ক্যাটাগরি</th>";
    echo "<th>প্রস্তুতকারক</th>";
    echo "<th>বর্তমান স্টক</th>";
    echo "<th>রিওর্ডার লেভেল</th>";
    echo "<th>এক্সপায়ারি তারিখ</th>";
    echo "<th>গত ৩০ দিনে বিক্রি</th>";
    echo "<th>মোট বিক্রি</th>";
    echo "</tr>";
    
    // Data
    foreach ($inventory as $item) {
        $stock_class = $item['current_stock'] <= $item['reorder_level'] ? 'bg-orange-100' : '';
        $expiry_class = strtotime($item['expiry_date']) < strtotime('+30 days') ? 'bg-red-100' : '';
        
        echo "<tr>";
        echo "<td>{$item['id']}</td>";
        echo "<td>{$item['name']}</td>";
        echo "<td>{$item['category']}</td>";
        echo "<td>{$item['manufacturer']}</td>";
        echo "<td class='$stock_class'>{$item['current_stock']}</td>";
        echo "<td>{$item['reorder_level']}</td>";
        echo "<td class='$expiry_class'>" . date('d/m/Y', strtotime($item['expiry_date'])) . "</td>";
        echo "<td>" . ($item['sold_last_30_days'] ?: '0') . "</td>";
        echo "<td>" . ($item['total_sold'] ?: '0') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    exit();
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">ইনভেন্টরি রিপোর্ট</h1>
        
        <div>
            <a href="?export_excel=1" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center">
                <i class="fas fa-file-excel mr-2"></i> এক্সেলে এক্সপোর্ট
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 text-left">আইডি</th>
                        <th class="py-2 px-4 text-left">নাম</th>
                        <th class="py-2 px-4 text-left">ক্যাটাগরি</th>
                        <th class="py-2 px-4 text-left">প্রস্তুতকারক</th>
                        <th class="py-2 px-4 text-center">বর্তমান স্টক</th>
                        <th class="py-2 px-4 text-center">রিওর্ডার লেভেল</th>
                        <th class="py-2 px-4 text-center">এক্সপায়ারি তারিখ</th>
                        <th class="py-2 px-4 text-center">গত ৩০ দিনে বিক্রি</th>
                        <th class="py-2 px-4 text-center">মোট বিক্রি</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item): 
                        $stock_class = $item['current_stock'] <= $item['reorder_level'] ? 'bg-orange-100 text-orange-800' : '';
                        $expiry_class = strtotime($item['expiry_date']) < strtotime('+30 days') ? 'bg-red-100 text-red-800' : '';
                    ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="py-2 px-4"><?php echo $item['id']; ?></td>
                        <td class="py-2 px-4"><?php echo htmlspecialchars($item['name']); ?></td>
                        <td class="py-2 px-4"><?php echo htmlspecialchars($item['category']); ?></td>
                        <td class="py-2 px-4"><?php echo htmlspecialchars($item['manufacturer']); ?></td>
                        <td class="py-2 px-4 text-center <?php echo $stock_class; ?>"><?php echo $item['current_stock']; ?></td>
                        <td class="py-2 px-4 text-center"><?php echo $item['reorder_level']; ?></td>
                        <td class="py-2 px-4 text-center <?php echo $expiry_class; ?>">
                            <?php echo date('d/m/Y', strtotime($item['expiry_date'])); ?>
                        </td>
                        <td class="py-2 px-4 text-center"><?php echo $item['sold_last_30_days'] ?: '0'; ?></td>
                        <td class="py-2 px-4 text-center"><?php echo $item['total_sold'] ?: '0'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>