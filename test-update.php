<?php
// test-update.php (delete after testing)
require_once 'config.php';
require_once 'includes/db.php';

try {
    // Test update
    Database::getInstance()->updateById('shops', 1, [
        'name' => 'Test Shop Update'
    ]);
    echo "✓ Update successful!<br>";
    
    // Test update with WHERE
    Database::getInstance()->update(
        'shops',
        ['status' => 'active'],
        'id = :id',
        ['id' => 1]
    );
    echo "✓ Update with WHERE successful!<br>";
    
    echo "<h3>All tests passed!</h3>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage();
}