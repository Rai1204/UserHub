<?php
// Test login with registered credentials
require_once 'api/config/database.php';

$email = 'raiantony12@gmail.com';
$testPassword = 'Rai@antony12'; // Replace with the actual password you used during registration

echo "Testing login for email: $email\n";
echo "=========================================\n\n";

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get user by email
$query = "SELECT id, username, email, password, created_at FROM users WHERE email = ?";
$stmt = $db->prepare($query);
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✓ User found in database\n";
    echo "  ID: " . $row['id'] . "\n";
    echo "  Username: " . $row['username'] . "\n";
    echo "  Email: " . $row['email'] . "\n";
    echo "  Created: " . $row['created_at'] . "\n\n";
    
    echo "Password hash in DB: " . substr($row['password'], 0, 50) . "...\n";
    echo "Hash length: " . strlen($row['password']) . "\n\n";
    
    // Test password verification
    echo "Testing password verification:\n";
    echo "Password being tested: $testPassword\n";
    
    if (password_verify($testPassword, $row['password'])) {
        echo "✅ PASSWORD MATCH - Login should work!\n";
    } else {
        echo "❌ PASSWORD MISMATCH - This is why login fails!\n";
        echo "\nPlease provide the actual password you used during registration.\n";
        echo "You need to test with the exact password you entered when registering.\n";
    }
} else {
    echo "❌ User not found with email: $email\n";
}
?>
