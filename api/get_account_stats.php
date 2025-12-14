<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';
require_once 'config/redis.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate session token
if (empty($data->sessionToken)) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Session token required."));
    exit();
}

// Verify session with Redis
$redis_config = new Redis_Config();
$redis = $redis_config->getConnection();

if (!$redis) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Redis connection failed."));
    exit();
}

$sessionData = $redis->get($data->sessionToken);

if (!$sessionData) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Invalid session."));
    exit();
}

$session = json_decode($sessionData, true);
$userId = $session['userId'];

// Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Database connection failed."));
    exit();
}

// Get user account statistics
$query = "SELECT created_at, last_login FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$userId]);

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If timestamps are NULL, set them now
    if (empty($row['created_at']) || empty($row['last_login'])) {
        $updateQuery = "UPDATE users SET created_at = COALESCE(created_at, NOW()), last_login = COALESCE(last_login, NOW()) WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$userId]);
        
        // Re-fetch the updated data
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "stats" => array(
            "created_at" => $row['created_at'],
            "last_login" => $row['last_login']
        )
    ));
} else {
    http_response_code(404);
    echo json_encode(array("success" => false, "message" => "User not found."));
}
?>
