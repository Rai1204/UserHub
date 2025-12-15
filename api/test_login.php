<?php
/**
 * Test login API - diagnostic endpoint
 */
header("Content-Type: text/html; charset=UTF-8");

echo "<h2>Login API Diagnostic</h2>";

// Test database connection
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<p>✅ Database connected</p>";
    
    // List users
    $query = "SELECT id, username, email, created_at FROM users LIMIT 5";
    $stmt = $db->query($query);
    echo "<h3>Users in database:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Created</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>❌ Database connection failed</p>";
}

// Test Redis connection
require_once 'config/redis.php';
$redis_config = new Redis_Config();
$redis = $redis_config->getConnection();

if ($redis) {
    echo "<p>✅ Redis connected</p>";
} else {
    echo "<p>❌ Redis connection failed</p>";
}

echo "<hr>";
echo "<p><a href='../pages/login.html'>Back to Login</a></p>";
?>
