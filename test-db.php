<?php
// test-db.php in root
require_once 'config.php';
require_once 'includes/db.php';

echo "<h2>Database Connection Test</h2>";

try {
    $db = Database::getInstance()->getConnection();
    echo "✓ Database instance created<br>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    echo "✓ Query executed successfully<br>";
    echo "✓ Found $count users in database<br>";
    
    echo "<hr>";
    echo "<h3>Test getCurrentUser() function</h3>";
    require_once 'includes/functions.php';
    
    // Simulate logged in user
    $_SESSION['user_id'] = 1;
    $user = getCurrentUser();
    
    if ($user) {
        echo "✓ getCurrentUser() works!<br>";
        echo "User: " . $user['full_name'] . "<br>";
        echo "Role: " . $user['role_name'] . "<br>";
    } else {
        echo "✗ getCurrentUser() returned null<br>";
    }
    
    echo "<hr>";
    echo "<h3>All tests passed! ✓</h3>";
    echo "<p>Delete this test file now.</p>";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage();
}