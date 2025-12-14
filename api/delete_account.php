<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';
require_once 'config/redis.php';
require_once 'config/mongodb.php';

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

try {
    // Get MongoDB connection
    $mongodb = new MongoDB_Config();
    $collection = $mongodb->getCollection('profiles');
    
    // Step 1: Get profile picture path to delete file
    if ($collection) {
        $profile = $collection->findOne(['userId' => (int)$userId]);
        if ($profile && isset($profile['profile_picture']) && !empty($profile['profile_picture'])) {
            $filePath = '../' . $profile['profile_picture'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Step 2: Delete profile from MongoDB
        $collection->deleteOne(['userId' => (int)$userId]);
    }
    
    // Step 3: Delete user from MySQL
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$userId]);
    
    // Step 4: Delete session from Redis
    $redis->del($data->sessionToken);
    
    http_response_code(200);
    echo json_encode(array(
        "success" => true, 
        "message" => "Account deleted successfully. Your email is now available for new registrations."
    ));
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error deleting account: " . $e->getMessage()));
}
?>
