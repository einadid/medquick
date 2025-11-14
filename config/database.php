<?php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change for production
define('DB_PASS', '');     // Change for production
define('DB_NAME', 'quickmed_db');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In production, log this error and show a generic message.
    die("Database Connection Error: " . $e->getMessage());
}