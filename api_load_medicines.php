<?php
// FILE: api_load_medicines.php
// PURPOSE: AJAX endpoint to fetch more medicines for pagination.

require_once 'src/session.php';
require_once 'config/database.php';

header('Content-Type: application/json');

// Get filters from the request
$search_term = trim($_GET['search'] ?? '');
$filter_category = trim($_GET['category'] ?? '');
$filter_manufacturer = trim($_GET['manufacturer'] ?? '');
$filter_availability = trim($_GET['availability'] ?? 'all');
$sort_order = trim($_GET['sort'] ?? 'name_asc');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Number of items per page
$offset = ($page - 1) * $limit;

// --- SQL Query Logic (same as catalog.php but with LIMIT and OFFSET) ---
$params = [];
$sql = "SELECT m.id, m.name, m.manufacturer, m.description, m.image_path, MIN(ib.price) as price, SUM(ib.quantity) as total_stock FROM medicines m LEFT JOIN inventory_batches ib ON m.id = ib.medicine_id AND ib.quantity > 0 AND ib.expiry_date > CURDATE() WHERE 1=1";

if (!empty($search_term)) { $sql .= " AND (m.name LIKE ? OR m.manufacturer LIKE ?)"; $params[] = "%$search_term%"; $params[] = "%$search_term%"; }
if (!empty($filter_category)) { $sql .= " AND m.category = ?"; $params[] = $filter_category; }
if (!empty($filter_manufacturer)) { $sql .= " AND m.manufacturer = ?"; $params[] = $filter_manufacturer; }

$sql .= " GROUP BY m.id, m.name, m.manufacturer, m.description, m.image_path";

if ($filter_availability === 'in_stock') { $sql .= " HAVING total_stock > 0"; } 
elseif ($filter_availability === 'out_of_stock') { $sql .= " HAVING total_stock IS NULL OR total_stock = 0"; }

switch ($sort_order) {
    case 'price_asc': $sql .= " ORDER BY CASE WHEN price IS NULL THEN 1 ELSE 0 END, price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY CASE WHEN price IS NULL THEN 1 ELSE 0 END, price DESC"; break;
    case 'name_desc': $sql .= " ORDER BY m.name DESC"; break;
    default: $sql .= " ORDER BY m.name ASC"; break;
}

// Add pagination
$sql .= " LIMIT $limit OFFSET $offset";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $medicines = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'medicines' => $medicines]);

} catch (PDOException $e) {
    error_log("API Load Medicines Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>