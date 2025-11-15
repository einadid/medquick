<?php
// FILE: search_medicines.php (Final & Robust Version)
header('Content-Type: application/json');

// Enable error reporting for debugging ONLY. Remove in production.
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'config/database.php'; // Ensure DB connection is made

    $query = trim($_GET['q'] ?? '');
    $response = [];

    // Only run query if the search term is long enough
    if (strlen($query) >= 2) {
        $searchTerm = "%" . $query . "%";
        $stmt = $pdo->prepare(
            "SELECT id, name, manufacturer, image_path 
             FROM medicines 
             WHERE name LIKE ? OR manufacturer LIKE ?
             LIMIT 7"
        );
        $stmt->execute([$searchTerm, $searchTerm]);
        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Always return a valid JSON structure
    echo json_encode(['success' => true, 'data' => $response]);

} catch (Throwable $e) {
    // Catch any error (DB connection, SQL syntax, etc.)
    http_response_code(500); // Internal Server Error
    error_log("SEARCH_API_ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Search service is currently unavailable.',
        'error_detail' => $e->getMessage() // For debugging purposes
    ]);
}
?>