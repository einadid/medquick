<?php
// Strict error reporting for development
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

header('Content-Type: application/json');

require_once 'config/database.php';

$response = [];

if (isset($_GET['q'])) {
    $query = trim($_GET['q']);
    
    if (strlen($query) >= 2) {
        try {
            // Like কোয়েরি ব্যবহার করে সার্চ করা হচ্ছে
            $searchTerm = "%" . $query . "%";
            $stmt = $pdo->prepare(
                "SELECT id, name, manufacturer, category FROM medicines 
                 WHERE name LIKE ? OR manufacturer LIKE ? OR category LIKE ?
                 LIMIT 10"
            );
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $response = $stmt->fetchAll();

        } catch (PDOException $e) {
            // সাধারণত production-এ error message পাঠানো হয় না
            // error_log("Search API error: " . $e->getMessage());
            // response остается খালি array
        }
    }
}

echo json_encode($response);