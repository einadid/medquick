<?php
session_start();
require '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Default date range (current month)
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'sales';

// Generate report
$report_data = [];
$report_title = '';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['generate_report'])) {
    switch ($report_type) {
        case 'sales':
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(order_date) as date,
                    COUNT(*) as orders,
                    SUM(total_amount) as revenue,
                    AVG(total_amount) as avg_order_value
                FROM orders 
                WHERE order_date BETWEEN ? AND ? AND status != 'cancelled'
                GROUP BY DATE(order_date)
                ORDER BY date
            ");
            $report_title = "বিক্রয় রিপোর্ট: " . date('d/m/Y', strtotime($start_date)) . " - " . date('d/m/Y', strtotime($end_date));
            break;
            
        case 'products':
            $stmt = $pdo->prepare("
                SELECT 
                    m.id,
                    m.name,
                    m.category,
                    SUM(oi.quantity) as quantity_sold,
                    SUM(oi.total_price) as revenue
                FROM order_items oi
                JOIN medicines m ON oi.medicine_id = m.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.order_date BETWEEN ? AND ? AND o.status = 'delivered'
                GROUP BY m.id
                ORDER BY revenue DESC
            ");
            $report_title = "পণ্য রিপোর্ট: " . date('d/m/Y', strtotime($start_date)) . " - " . date('d/m/Y', strtotime($end_date));
            break;
            
        case 'customers':
            $stmt = $pdo->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    COUNT(o.id) as orders,
                    SUM(o.total_amount) as total_spent,
                    MAX(o.order_date) as last_order
                FROM users u
                LEFT JOIN orders o ON u.id = o.customer_id
                WHERE (o.order_date BETWEEN ? AND ? OR o.id IS NULL)
                AND u.role = 'customer'
                GROUP BY u.id
                HAVING orders > 0
                ORDER BY total_spent DESC
            ");
            $report_title = "গ্রাহক রিপোর্ট: " . date('d/m/Y', strtotime($start_date)) . " - " . date('d/m/Y', strtotime($end_date));
            break;
    }
    
    $stmt->execute([$start_date, $end_date]);
    $report_data = $stmt->fetchAll();
}

// Export to Excel
if (isset($_GET['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='" . (count($report_data[0] ?? []) + 1) . "'><h2>$report_title</h2></th></tr>";
    
    if (!empty($report_data)) {
        // Headers
        echo "<tr>";
        foreach (array_keys($report_data[0]) as $header) {
            echo "<th>" . ucfirst(str_replace('_', ' ', $header)) . "</th>";
        }
        echo "</tr>";
        
        // Data
        foreach ($report_data as $row) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>" . htmlspecialchars($cell) . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td>কোন ডেটা পাওয়া যায়নি</td></tr>";
    }
    
    echo "</table>";
    exit();
}
?>

<?php include '../includes/header.php'; ?>

<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-6">রিপোর্ট জেনারেটর</h1>
    
    <!-- Report Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" action="">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">রিপোর্ট টাইপ</label>
                    <select name="report_type" class="w-full px-3 py-2 border rounded-lg">
                        <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>বিক্রয় রিপোর্ট</option>
                        <option value="products" <?php echo $report_type == 'products' ? 'selected' : ''; ?>>পণ্য রিপোর্ট</option>
                        <option value="customers" <?php echo $report_type == 'customers' ? 'selected' : ''; ?>>গ্রাহক রিপোর্ট</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">শুরুর তারিখ</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="w-full px-3 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">শেষ তারিখ</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="w-full px-3 py-2 border rounded-lg">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" name="generate_report" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 w-full">
                        রিপোর্ট জেনারেট করুন
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Report Results -->
    <?php if (!empty($report_data)): ?>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold"><?php echo $report_title; ?></h2>
                
                <div class="flex space-x-2">
                    <a href="?<?php echo http_build_query($_GET + ['export_excel' => 1]); ?>" 
                       class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center">
                        <i class="fas fa-file-excel mr-2"></i> এক্সেলে এক্সপোর্ট
                    </a>
                    <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                        <i class="fas fa-print mr-2"></i> প্রিন্ট
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr class="bg-gray-100">
                            <?php foreach (array_keys($report_data[0]) as $header): ?>
                                <th class="py-2 px-4 text-left"><?php echo ucfirst(str_replace('_', ' ', $header)); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <?php foreach ($row as $cell): ?>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($cell); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    
                    <!-- Add summary row for sales report -->
                    <?php if ($report_type == 'sales' && !empty($report_data)): ?>
                        <tfoot>
                            <tr class="bg-gray-50 font-bold">
                                <td class="py-2 px-4">সর্বমোট</td>
                                <td class="py-2 px-4"><?php echo array_sum(array_column($report_data, 'orders')); ?></td>
                                <td class="py-2 px-4">৳<?php echo number_format(array_sum(array_column($report_data, 'revenue')), 2); ?></td>
                                <td class="py-2 px-4">৳<?php echo number_format(array_sum(array_column($report_data, 'revenue')) / array_sum(array_column($report_data, 'orders')), 2); ?></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['generate_report'])): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-info-circle text-4xl text-gray-300 mb-4"></i>
            <h2 class="text-xl font-bold mb-2">কোন ডেটা পাওয়া যায়নি</h2>
            <p class="text-gray-600">নির্বাচিত সময়সীমায় কোন ডেটা পাওয়া যায়নি</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>