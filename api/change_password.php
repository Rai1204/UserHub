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
if (empty($data->sessionToken) || empty($data->currentPassword) || empty($data->newPassword)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "All fields are required."));
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

// Get current user password
$query = "SELECT password FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$userId]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(array("success" => false, "message" => "User not found."));
    exit();
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify current password
if (!password_verify($data->currentPassword, $row['password'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Current password is incorrect."));
    exit();
}

// Validate new password strength (minimum 6 characters)
if (strlen($data->newPassword) < 6) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "New password must be at least 6 characters long."));
    exit();
}

// Validate password contains at least 1 uppercase letter
if (!preg_match('/[A-Z]/', $data->newPassword)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Password must contain at least 1 uppercase letter."));
    exit();
}

// Validate password contains at least 1 number
if (!preg_match('/[0-9]/', $data->newPassword)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Password must contain at least 1 number."));
    exit();
}

// Validate password contains at least 1 special character
if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $data->newPassword)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Password must contain at least 1 special character (!@#$%^&*(),.?\":{}|<>)."));
    exit();
}

// Check if new password is same as current
if ($data->currentPassword === $data->newPassword) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "New password must be different from current password."));
    exit();
}

// Hash new password
$hashedPassword = password_hash($data->newPassword, PASSWORD_BCRYPT);

// Update password
$updateQuery = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
$updateStmt = $db->prepare($updateQuery);

try {
    $updateStmt->execute([$hashedPassword, $userId]);
    
    http_response_code(200);
    echo json_encode(array("success" => true, "message" => "Password changed successfully!"));
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Failed to update password: " . $e->getMessage()));
}
?>
