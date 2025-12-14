<?php
// Fix existing GitHub users' created_at and last_login timestamps
// Access: http://localhost/user-management/api/fix_github_timestamps.php

header("Content-Type: text/html; charset=UTF-8");

require_once __DIR__ . '/../../api/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed.");
    }
    
    echo "<h2>Fix GitHub Users' Timestamps</h2>";
    
    // Find all GitHub users with NULL created_at or last_login
    $findQuery = "SELECT id, username, email, github_id, created_at, last_login FROM users WHERE github_id IS NOT NULL AND (created_at IS NULL OR last_login IS NULL)";
    $findStmt = $db->query($findQuery);
    
    if ($findStmt->rowCount() === 0) {
        echo "<p style='color: green;'>✓ All GitHub users have proper timestamps. No fix needed!</p>";
        
        // Show all GitHub users
        echo "<h3>All GitHub Users:</h3>";
        $allQuery = "SELECT id, username, email, created_at, last_login FROM users WHERE github_id IS NOT NULL";
        $allStmt = $db->query($allQuery);
        
        echo "<table border='1' cellpadding='10' cellspacing='0'>";
        echo "<tr><th>Username</th><th>Email</th><th>Created At</th><th>Last Login</th></tr>";
        while ($user = $allStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($user['last_login'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit();
    }
    
    echo "<p>Found " . $findStmt->rowCount() . " GitHub user(s) with missing timestamps.</p>";
    
    echo "<table border='1' cellpadding='10' cellspacing='0'>";
    echo "<tr><th>Username</th><th>Email</th><th>Before Created</th><th>Before Last Login</th><th>Status</th></tr>";
    
    while ($user = $findStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['created_at'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($user['last_login'] ?? 'NULL') . "</td>";
        
        // Update the user
        $updateQuery = "UPDATE users SET created_at = COALESCE(created_at, NOW()), last_login = COALESCE(last_login, NOW()) WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$user['id']]);
        
        echo "<td style='color: green;'>✓ FIXED</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3 style='color: green; margin-top: 20px;'>✓ All GitHub users fixed!</h3>";
    echo "<p><a href='../pages/profile.html'>Go to Profile Page</a></p>";
    
    // Show updated data
    echo "<h3>Updated GitHub Users:</h3>";
    $allQuery = "SELECT id, username, email, created_at, last_login FROM users WHERE github_id IS NOT NULL";
    $allStmt = $db->query($allQuery);
    
    echo "<table border='1' cellpadding='10' cellspacing='0'>";
    echo "<tr><th>Username</th><th>Email</th><th>Created At</th><th>Last Login</th></tr>";
    while ($user = $allStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
        echo "<td>" . htmlspecialchars($user['last_login']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}
?>
