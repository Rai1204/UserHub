<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (empty($data->username) || empty($data->email) || empty($data->password) || empty($data->verificationCode)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "All fields including verification code are required."));
    exit();
}

// Validate email format
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Invalid email format."));
    exit();
}

// Validate Gmail only
if (!preg_match('/@gmail\.com$/i', $data->email)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Only Gmail addresses (@gmail.com) are allowed to register."));
    exit();
}

// Validate password strength (minimum 6 characters)
if (strlen($data->password) < 6) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Password must be at least 6 characters long."));
    exit();
}

// Validate password contains at least 1 uppercase letter
if (!preg_match('/[A-Z]/', $data->password)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Password must contain at least 1 uppercase letter."));
    exit();
}

// Validate password contains at least 1 number
if (!preg_match('/[0-9]/', $data->password)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Password must contain at least 1 number."));
    exit();
}

// Validate password contains at least 1 special character
if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $data->password)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Password must contain at least 1 special character (!@#$%^&*(),.?\":{}|<>)."));
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

// Verify the 6-digit code
$query = "SELECT code, expires_at FROM verification_codes WHERE email = ?";
$stmt = $db->prepare($query);
$stmt->execute([$data->email]);

if ($stmt->rowCount() === 0) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "No verification code found. Please request a new code."));
    exit();
}

$codeData = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if code has expired
if (strtotime($codeData['expires_at']) < time()) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Verification code has expired. Please request a new code."));
    exit();
}

// Check if code matches
if ($codeData['code'] !== $data->verificationCode) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Invalid verification code. Please check and try again."));
    exit();
}

// Check if email already exists using prepared statement
$query = "SELECT id FROM users WHERE email = ?";
$stmt = $db->prepare($query);
$stmt->execute([$data->email]);

if ($stmt->rowCount() > 0) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Email already exists."));
    exit();
}

// Check if username already exists using prepared statement
$query = "SELECT id FROM users WHERE username = ?";
$stmt = $db->prepare($query);
$stmt->execute([$data->username]);

if ($stmt->rowCount() > 0) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Username already exists."));
    exit();
}

// Hash password
$hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);

// Insert user using prepared statement
$query = "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())";
$stmt = $db->prepare($query);

try {
    if ($stmt->execute([$data->username, $data->email, $hashedPassword])) {
        
        // Delete the verification code after successful registration
        $deleteQuery = "DELETE FROM verification_codes WHERE email = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->execute([$data->email]);
        
        http_response_code(201);
        echo json_encode(array("success" => true, "message" => "Registration successful! You can now login."));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Unable to register user."));
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
}
