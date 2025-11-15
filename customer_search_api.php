<?php
// FILE: customer_search_api.php (Upgraded for Membership ID Search)
require_once 'src/session.php';
require_once 'config/database.php';
header('Content-Type: application/json');

if (!has_role(ROLE_SALESMAN) && !has_role(ROLE_ADMIN)) { http_response_code(403); exit; }

$query = $_GET['q'] ?? '';
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Prepare search terms
$name_query = "%" . $query . "%";
$email_query = "%" . $query . "%";
$member_id_query = $query . '%'; // Search for emails that START with the member ID

try {
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, points_balance 
        FROM users 
        WHERE role = 'customer' 
          AND id != 1 -- Exclude Walk-in Customer
          AND (full_name LIKE ? OR email LIKE ? OR email LIKE ?)
        LIMIT 10
    ");
    $stmt->execute([$name_query, $email_query, $member_id_query]);
    echo json_encode($stmt->fetchAll());

} catch (PDOException $e) {
    error_log("Customer Search API Error: " . $e->getMessage());
    echo json_encode([]);
}
?>