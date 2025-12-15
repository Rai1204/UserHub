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
if (empty($data->email) || empty($data->code)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Email and verification code are required."));
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

try {
    // Verify the code
    $query = "SELECT code, expires_at FROM password_reset_codes WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data->email]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "No password reset request found. Please request a new code."));
        exit();
    }
    
    $codeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if code has expired
    if (strtotime($codeData['expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Password reset code has expired. Please request a new code."));
        exit();
    }
    
    // Check if code matches
    if ($codeData['code'] !== $data->code) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Invalid verification code. Please check and try again."));
        exit();
    }
    
    // Get user details
    $userQuery = "SELECT id, username, email, created_at, last_login FROM users WHERE email = ?";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$data->email]);
    
    if ($userStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "User not found."));
        exit();
    }
    
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate session token
    $sessionToken = bin2hex(random_bytes(32));
    
    // Store session in Redis
    $redis_config = new Redis_Config();
    $redis = $redis_config->getConnection();
    
    if ($redis) {
        // Store session data in Redis (expires in 24 hours)
        $sessionData = json_encode(array(
            "userId" => $user['id'],
            "username" => $user['username'],
            "email" => $user['email'],
            "loginTime" => time()
        ));
        
        $redis->setex($sessionToken, 86400, $sessionData); // 24 hours
    }
    
    // Update last login
    $updateLoginQuery = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
    $updateStmt = $db->prepare($updateLoginQuery);
    $updateStmt->execute([$user['id']]);
    
    // Delete the used code
    $deleteQuery = "DELETE FROM password_reset_codes WHERE email = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->execute([$data->email]);
    
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Verification successful! You are now logged in.",
        "sessionToken" => $sessionToken,
        "userId" => $user['id'],
        "username" => $user['username'],
        "email" => $user['email'],
        "created_at" => $user['created_at'],
        "last_login" => $user['last_login']
    ));
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
}
?>
