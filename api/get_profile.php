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

// Get GitHub username from MySQL if user logged in via GitHub
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();
$githubUsername = null;

if ($db) {
    $userQuery = "SELECT username FROM users WHERE id = ? AND github_id IS NOT NULL";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$userId]);
    if ($userStmt->rowCount() > 0) {
        $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
        $githubUsername = $userRow['username'];
    }
}

// Get profile from MongoDB
$mongodb = new MongoDB_Config();
$collection = $mongodb->getCollection('profiles');

if (!$collection) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "MongoDB connection failed."));
    exit();
}

try {
    $profile = $collection->findOne(['userId' => (int)$userId]);
    
    // If user logged in with GitHub and hasn't set GitHub link, use their GitHub username
    $githubLink = "";
    if ($profile && isset($profile['github']) && !empty($profile['github'])) {
        $githubLink = $profile['github'];
    } elseif ($githubUsername) {
        $githubLink = "https://github.com/" . $githubUsername;
    }
    
    if ($profile) {
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "profile" => array(
                "fullname" => isset($profile['fullname']) ? $profile['fullname'] : "",
                "age" => isset($profile['age']) ? $profile['age'] : "",
                "dob" => isset($profile['dob']) ? $profile['dob'] : "",
                "contact" => isset($profile['contact']) ? $profile['contact'] : "",
                "address" => isset($profile['address']) ? $profile['address'] : "",
                "bio" => isset($profile['bio']) ? $profile['bio'] : "",
                "profile_picture" => isset($profile['profile_picture']) ? $profile['profile_picture'] : "",
                "linkedin" => isset($profile['linkedin']) ? $profile['linkedin'] : "",
                "twitter" => isset($profile['twitter']) ? $profile['twitter'] : "",
                "github" => $githubLink,
                "website" => isset($profile['website']) ? $profile['website'] : ""
            )
        ));
    } else {
        // No profile yet, but still return GitHub link if available
        http_response_code(200);
        echo json_encode(array(
            "success" => true, 
            "profile" => null,
            "defaultGithub" => $githubUsername ? "https://github.com/" . $githubUsername : ""
        ));
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
}
