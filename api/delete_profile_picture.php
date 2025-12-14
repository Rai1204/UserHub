<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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

// Get MongoDB connection
$mongodb = new MongoDB_Config();
$collection = $mongodb->getCollection('profiles');

if (!$collection) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "MongoDB connection failed."));
    exit();
}

try {
    // Get existing profile to find picture path
    $existingProfile = $collection->findOne(['userId' => (int)$userId]);
    
    if ($existingProfile && isset($existingProfile['profile_picture'])) {
        $uploadDir = __DIR__ . '/../uploads/profile_pictures/';
        $filePath = $uploadDir . basename($existingProfile['profile_picture']);
        
        // Delete physical file if exists
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Remove profile_picture from MongoDB
        $result = $collection->updateOne(
            ['userId' => (int)$userId],
            [
                '$unset' => ['profile_picture' => ''],
                '$set' => ['updated_at' => new MongoDB\BSON\UTCDateTime()]
            ]
        );
        
        http_response_code(200);
        echo json_encode(array(
            "success" => true, 
            "message" => "Profile picture removed successfully!"
        ));
    } else {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "No profile picture found."));
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
}
?>
