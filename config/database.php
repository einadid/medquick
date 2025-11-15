<?php
// config/database.php
define('DB_HOST', 'sql112.infinityfree.com');
define('DB_USER', 'if0_40419807'); // Change for production
define('DB_PASS', '0123Nadid');     // Change for production
define('DB_NAME', 'if0_40419807_quickmed_db');


try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Set PDO attributes for secure and robust database operations
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Use native prepared statements
} catch (PDOException $e) {
    // For a live site, you should not show detailed errors to the user.
    // Log the error to a file and show a generic friendly message.
    // error_log("Database Connection Error: " . $e->getMessage(), 3, "/path/to/your/error.log");
    die("Error: Could not connect to the service. Please try again later.");
}