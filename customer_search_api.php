<?php
// FILE: customer_search_api.php
require_once 'src/session.php';
require_once 'config/database.php';
header('Content-Type: application/json');

if (!has_role(ROLE_SALESMAN)) { http_response_code(403); exit; }

$query = "%" . ($_GET['q'] ?? '') . "%";
$stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE role = 'customer' AND (full_name LIKE ? OR email LIKE ?)");
$stmt->execute([$query, $query]);
echo json_encode($stmt->fetchAll());
?>