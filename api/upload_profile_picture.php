<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/redis.php';
require_once 'config/mongodb.php';

// Validate session token
if (!isset($_POST['sessionToken'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Session token required."));
    exit();
}

$sessionToken = $_POST['sessionToken'];

// Verify session with Redis
$redis_config = new Redis_Config();
$redis = $redis_config->getConnection();

if (!$redis) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Redis connection failed."));
    exit();
}

$sessionData = $redis->get($sessionToken);

if (!$sessionData) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Invalid session."));
    exit();
}

$session = json_decode($sessionData, true);
$userId = $session['userId'];

// Check if file was uploaded
if (!isset($_FILES['profilePicture']) || $_FILES['profilePicture']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "No file uploaded or upload error."));
    exit();
}

$file = $_FILES['profilePicture'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Invalid file type. Only JPG, PNG, GIF, and WebP allowed."));
    exit();
}

// Validate file size (max 5MB)
$maxSize = 5 * 1024 * 1024; // 5MB in bytes
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "File too large. Maximum size is 5MB."));
    exit();
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$newFilename = 'user_' . $userId . '_' . time() . '.' . $extension;
$uploadDir = __DIR__ . '/../uploads/profile_pictures/';
$uploadPath = $uploadDir . $newFilename;

// Delete old profile picture if exists
$mongodb = new MongoDB_Config();
$collection = $mongodb->getCollection('profiles');

if ($collection) {
    try {
        $existingProfile = $collection->findOne(['userId' => (int)$userId]);
        if ($existingProfile && isset($existingProfile['profile_picture'])) {
            $oldFile = $uploadDir . basename($existingProfile['profile_picture']);
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
    } catch (Exception $e) {
        // Continue even if deletion fails
    }
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Failed to save file."));
    exit();
}

// Save file path to MongoDB
$relativePath = 'uploads/profile_pictures/' . $newFilename;

if (!$collection) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "MongoDB connection failed."));
    exit();
}

try {
    // Update profile with picture path
    $result = $collection->updateOne(
        ['userId' => (int)$userId],
        [
            '$set' => [
                'profile_picture' => $relativePath,
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]
        ],
        ['upsert' => true]
    );
    
    http_response_code(200);
    echo json_encode(array(
        "success" => true, 
        "message" => "Profile picture uploaded successfully!",
        "profile_picture" => $relativePath
    ));
} catch(Exception $e) {
    // Delete uploaded file if database update fails
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
}
?>
