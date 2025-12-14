<?php
/**
 * Direct Registration (No Email Verification)
 * Temporary solution for free tier hosting that blocks SMTP
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';
require_once 'config/redis.php';
require_once __DIR__ . '/../vendor/autoload.php';

$data = json_decode(file_get_contents("php://input"));

// Validate input
if (empty($data->email) || empty($data->username) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "All fields are required."));
    exit();
}

// Validate email format
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Invalid email format."));
    exit();
}

// Validate Gmail only (keep this requirement)
if (!preg_match('/@gmail\.com$/i', $data->email)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Only Gmail addresses (@gmail.com) are allowed."));
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
    // Check if email already exists
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data->email]);
    
    if ($stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(array("success" => false, "message" => "Email already registered."));
        exit();
    }
    
    // Check if username already exists
    $query = "SELECT id FROM users WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$data->username]);
    
    if ($stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(array("success" => false, "message" => "Username already taken."));
        exit();
    }
    
    // Hash password
    $hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);
    
    // Insert user
    $query = "INSERT INTO users (email, username, password, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$data->email, $data->username, $hashedPassword])) {
        // Get user ID
        $userId = $db->lastInsertId();
        
        // Create initial profile in MongoDB
        try {
            require_once 'config/mongodb.php';
            $mongodb = new MongoDB_Config();
            $collection = $mongodb->getCollection('profiles');
            
            if ($collection) {
                $profileData = [
                    'user_id' => (int)$userId,
                    'username' => $data->username,
                    'email' => $data->email,
                    'fullname' => '',
                    'contact' => '',
                    'age' => null,
                    'dob' => '',
                    'address' => '',
                    'bio' => '',
                    'linkedin' => '',
                    'twitter' => '',
                    'github' => '',
                    'website' => '',
                    'profile_picture' => '',
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                $collection->insertOne($profileData);
            }
        } catch (Exception $e) {
            // MongoDB error doesn't stop registration
            error_log("MongoDB profile creation failed: " . $e->getMessage());
        }
        
        http_response_code(201);
        echo json_encode(array(
            "success" => true,
            "message" => "Registration successful! You can now login."
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Registration failed. Please try again."));
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
}
