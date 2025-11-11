<?php
session_start();
require '../includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    // Fetch sales data
    $stmt = $pdo->prepare("
        SELECT 
            DATE(s.sale_date) as sale_day,
            m.name as medicine_name,
            s.quantity,
            s.unit_price,
            s.total_amount,
            s.customer_name,
            s.customer_phone
        FROM sales s
        JOIN medicines m ON s.medicine_id = m.id
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
        ORDER BY s.sale_date DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $sales_data = $stmt->fetchAll();
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="sales_report_'.date('Y-m-d').'.xls"');
    
    // Excel content
    echo "<table border='1'>";
    echo "<tr>
            <th>তারিখ</th>
            <th>মেডিসিনের নাম</th>
            <th>পরিমাণ</th>
            <th>দর</th>
            <th>মোট</th>
            <th>ক্রেতার নাম</th>
            <th>ক্রেতার ফোন</th>
          </tr>";
    
    $total_amount = 0;
    foreach ($sales_data as $sale) {
        echo "<tr>";
        echo "<td>" . date('d/m/Y', strtotime($sale['sale_day'])) . "</td>";
        echo "<td>" . $sale['medicine_name'] . "</td>";
        echo "<td>" . $sale['quantity'] . "</td>";
        echo "<td>৳" . number_format($sale['unit_price'], 2) . "</td>";
        echo "<td>৳" . number_format($sale['total_amount'], 2) . "</td>";
        echo "<td>" . $sale['customer_name'] . "</td>";
        echo "<td>" . $sale['customer_phone'] . "</td>";
        echo "</tr>";
        
        $total_amount += $sale['total_amount'];
    }
    
    // Total row
    echo "<tr style='font-weight:bold;'>";
    echo "<td colspan='4' style='text-align:right;'>সর্বমোট:</td>";
    echo "<td>৳" . number_format($total_amount, 2) . "</td>";
    echo "<td colspan='2'></td>";
    echo "</tr>";
    
    echo "</table>";
    exit();
    
} catch (PDOException $e) {
    die("Error generating report: " . $e->getMessage());
}
?>