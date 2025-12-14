<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';
require_once 'config/redis.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (empty($data->email)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Email is required."));
    exit();
}

$email = filter_var($data->email, FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Invalid email format."));
    exit();
}

try {
    // Check if user exists
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed.");
    }
    
    $query = "SELECT id, username, email, created_at, last_login FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "exists" => false, "message" => "No account found with this email."));
        exit();
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Update last login
    $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':id', $user['id']);
    $updateStmt->execute();
    
    // Create session token
    $redis_config = new Redis_Config();
    $redis = $redis_config->getConnection();
    
    if (!$redis) {
        throw new Exception("Redis connection failed.");
    }
    
    $sessionToken = bin2hex(random_bytes(32));
    $sessionData = json_encode(array(
        'userId' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email']
    ));
    
    $redis->setex($sessionToken, 86400, $sessionData); // 24 hours
    
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "exists" => true,
        "sessionToken" => $sessionToken,
        "userId" => $user['id'],
        "username" => $user['username'],
        "email" => $user['email'],
        "created_at" => $user['created_at'],
        "last_login" => $user['last_login'],
        "message" => "User verified successfully."
    ));
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
}
?>
