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

// Prepare profile data
$profileData = array(
    "userId" => (int)$userId,
    "fullname" => isset($data->fullname) ? $data->fullname : "",
    "age" => isset($data->age) ? (int)$data->age : null,
    "dob" => isset($data->dob) ? $data->dob : "",
    "contact" => isset($data->contact) ? $data->contact : "",
    "address" => isset($data->address) ? $data->address : "",
    "bio" => isset($data->bio) ? $data->bio : "",
    "linkedin" => isset($data->linkedin) ? $data->linkedin : "",
    "twitter" => isset($data->twitter) ? $data->twitter : "",
    "github" => isset($data->github) ? $data->github : "",
    "website" => isset($data->website) ? $data->website : "",
    "updated_at" => new MongoDB\BSON\UTCDateTime()
);

// Get MongoDB connection
$mongodb = new MongoDB_Config();
$collection = $mongodb->getCollection('profiles');

if (!$collection) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "MongoDB connection failed."));
    exit();
}

try {
    // Update or insert profile (upsert)
    $result = $collection->updateOne(
        ['userId' => (int)$userId],
        ['$set' => $profileData],
        ['upsert' => true]
    );
    
    http_response_code(200);
    echo json_encode(array("success" => true, "message" => "Profile updated successfully!"));
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
}
