<?php
// Debug script to check user password status
// Access: http://localhost/user-management/api/debug_password.php?email=YOUR_EMAIL

header("Content-Type: text/html; charset=UTF-8");

require_once __DIR__ . '/../../api/config/database.php';

if (!isset($_GET['email'])) {
    echo "Usage: debug_password.php?email=YOUR_EMAIL";
    exit();
}

$email = $_GET['email'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed.");
    }
    
    $query = "SELECT id, username, email, github_id, password FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 0) {
        echo "No user found with email: " . htmlspecialchars($email);
        exit();
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>User Debug Info</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>" . htmlspecialchars($user['id']) . "</td></tr>";
    echo "<tr><td>Username</td><td>" . htmlspecialchars($user['username']) . "</td></tr>";
    echo "<tr><td>Email</td><td>" . htmlspecialchars($user['email']) . "</td></tr>";
    echo "<tr><td>GitHub ID</td><td>" . htmlspecialchars($user['github_id'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>Has Password</td><td>" . (!empty($user['password']) ? 'YES' : 'NO') . "</td></tr>";
    echo "<tr><td>Password Hash (first 50 chars)</td><td>" . htmlspecialchars(substr($user['password'], 0, 50)) . "...</td></tr>";
    echo "</table>";
    
    // Test password verification
    echo "<h3>Password Verification Test</h3>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='email' value='" . htmlspecialchars($email) . "'>";
    echo "<label>Test Password: <input type='text' name='test_password' required></label><br><br>";
    echo "<button type='submit'>Test Verify</button>";
    echo "</form>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_password'])) {
        $testPassword = $_POST['test_password'];
        $isValid = password_verify($testPassword, $user['password']);
        
        echo "<div style='margin-top: 20px; padding: 10px; background: " . ($isValid ? '#d4edda' : '#f8d7da') . "; border: 1px solid " . ($isValid ? '#c3e6cb' : '#f5c6cb') . ";'>";
        echo "<strong>Test Result:</strong> ";
        echo $isValid ? "✓ Password MATCHES" : "✗ Password DOES NOT MATCH";
        echo "<br>Tested password: " . htmlspecialchars($testPassword);
        echo "</div>";
    }
    
    echo "<h3>Expected Values</h3>";
    echo "<ul>";
    echo "<li>If you logged in with GitHub, your current password should be: <strong>" . htmlspecialchars($user['username']) . "</strong></li>";
    echo "<li>Try entering exactly: <code>" . htmlspecialchars($user['username']) . "</code> as your current password</li>";
    echo "</ul>";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
