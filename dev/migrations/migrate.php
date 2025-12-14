<?php
// Database migration script to add last_login column
require_once __DIR__ . '/../../api/config/database.php';

echo "<h2>Database Migration: Add last_login column</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        die("<p style='color:red;'>Database connection failed!</p>");
    }
    
    // Check if column already exists
    $checkQuery = "SHOW COLUMNS FROM users LIKE 'last_login'";
    $stmt = $db->query($checkQuery);
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:orange;'>Column 'last_login' already exists. No changes needed.</p>";
    } else {
        // Add the column
        $alterQuery = "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER password";
        $db->exec($alterQuery);
        
        echo "<p style='color:green;'>✓ Successfully added 'last_login' column to users table!</p>";
        echo "<p>You can now use the account statistics feature.</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='pages/profile.html'>Go to Profile Page</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
