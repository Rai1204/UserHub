<?php
// Quick fix script to set password for GitHub users
// Access: http://localhost/user-management/api/fix_github_password.php?email=YOUR_EMAIL

header("Content-Type: text/html; charset=UTF-8");

require_once __DIR__ . '/../../api/config/database.php';

if (!isset($_GET['email'])) {
    echo "<h2>Set Password for GitHub User</h2>";
    echo "<form method='get'>";
    echo "<label>Your Email: <input type='email' name='email' required></label><br><br>";
    echo "<button type='submit'>Fix Password</button>";
    echo "</form>";
    exit();
}

$email = $_GET['email'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed.");
    }
    
    // Get user info
    $query = "SELECT id, username, email, github_id, password FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 0) {
        echo "<div style='color: red;'>No user found with email: " . htmlspecialchars($email) . "</div>";
        exit();
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Current User Info</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>Username</td><td>" . htmlspecialchars($user['username']) . "</td></tr>";
    echo "<tr><td>Email</td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
    echo "<tr><td>GitHub ID</td><td>" . htmlspecialchars($user['github_id'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>Has Password</td><td>" . (!empty($user['password']) ? 'YES' : 'NO') . "</td></tr>";
    echo "</table>";
    
    // Set password to username
    $hashedPassword = password_hash($user['username'], PASSWORD_BCRYPT);
    
    $updateQuery = "UPDATE users SET password = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$hashedPassword, $user['id']]);
    
    echo "<div style='margin-top: 20px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb;'>";
    echo "<h3 style='color: #155724;'>✓ Password Successfully Set!</h3>";
    echo "<p><strong>Your current password is now:</strong> <code style='background: #fff; padding: 5px; font-size: 16px;'>" . htmlspecialchars($user['username']) . "</code></p>";
    echo "<p>Use this password to:</p>";
    echo "<ol>";
    echo "<li>Go to your Profile page</li>";
    echo "<li>Find the 'Change Password' section</li>";
    echo "<li>Enter <strong>" . htmlspecialchars($user['username']) . "</strong> as Current Password</li>";
    echo "<li>Enter your new secure password</li>";
    echo "</ol>";
    echo "</div>";
    
    // Test the password
    $testVerify = password_verify($user['username'], $hashedPassword);
    echo "<div style='margin-top: 20px; padding: 10px; background: " . ($testVerify ? '#d4edda' : '#f8d7da') . ";'>";
    echo "<strong>Verification Test:</strong> " . ($testVerify ? "✓ PASSED" : "✗ FAILED");
    echo "</div>";
    
} catch(Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}
?>
