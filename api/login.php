<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';
require_once 'config/redis.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Email and password are required."));
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Database connection failed."));
    exit();
}

// Get user by email using prepared statement
$query = "SELECT id, username, email, password, created_at, last_login FROM users WHERE email = ?";
$stmt = $db->prepare($query);
$stmt->execute([$data->email]);

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify password
    if (password_verify($data->password, $row['password'])) {
        // Update last login time
        $updateLoginQuery = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
        $updateStmt = $db->prepare($updateLoginQuery);
        $updateStmt->execute([$row['id']]);
        
        // Generate session token
        $sessionToken = bin2hex(random_bytes(32));
        
        // Store session in Redis
        $redis_config = new Redis_Config();
        $redis = $redis_config->getConnection();
        
        if ($redis) {
            // Store session data in Redis (expires in 24 hours)
            $sessionData = json_encode(array(
                "userId" => $row['id'],
                "username" => $row['username'],
                "email" => $row['email'],
                "loginTime" => time()
            ));
            
            $redis->setex($sessionToken, 86400, $sessionData); // 24 hours = 86400 seconds
        }
        
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "Login successful!",
            "sessionToken" => $sessionToken,
            "userId" => $row['id'],
            "username" => $row['username'],
            "email" => $row['email'],
            "created_at" => $row['created_at'],
            "last_login" => $row['last_login']
        ));
    } else {
        http_response_code(401);
        echo json_encode(array("success" => false, "message" => "Invalid credentials."));
    }
} else {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "User not found."));
}
