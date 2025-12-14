<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';
require_once 'config/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (empty($data->email)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Email is required."));
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

// Get database connection
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Database connection failed."));
    exit();
}

// Check if email already exists
$query = "SELECT id FROM users WHERE email = ?";
$stmt = $db->prepare($query);
$stmt->execute([$data->email]);

if ($stmt->rowCount() > 0) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Email already registered."));
    exit();
}

// Generate 6-digit verification code
$verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// Store verification code in database
$query = "INSERT INTO verification_codes (email, code, expires_at, created_at) VALUES (?, ?, ?, NOW()) 
          ON DUPLICATE KEY UPDATE code = ?, expires_at = ?, created_at = NOW()";
$stmt = $db->prepare($query);

try {
    $stmt->execute([$data->email, $verificationCode, $expiresAt, $verificationCode, $expiresAt]);
    
    // Send verification email
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration (loaded from .env file)
        $gmailUsername = getenv('GMAIL_USERNAME');
        $gmailPassword = getenv('GMAIL_APP_PASSWORD');
        
        // Check if credentials are loaded
        if (empty($gmailUsername) || empty($gmailPassword)) {
            throw new Exception('Gmail credentials not found in .env file. Please configure GMAIL_USERNAME and GMAIL_APP_PASSWORD.');
        }
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $gmailUsername;
        $mail->Password = $gmailPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 0;
        
        // Additional SMTP options for better compatibility
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Email content
        $mail->setFrom($gmailUsername, 'UserHub');
        $mail->addAddress($data->email);
        $mail->Subject = 'Your Verification Code - UserHub';
        
        $mail->isHTML(true);
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h2 style='color: #10b981; margin-bottom: 10px;'>Welcome to UserHub!</h2>
                    <p style='color: #6b7280; margin: 0;'>Complete your registration</p>
                </div>
                
                <div style='background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; padding: 30px; text-align: center; margin: 30px 0;'>
                    <p style='color: white; margin: 0 0 15px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 2px;'>Your Verification Code</p>
                    <div style='background: white; border-radius: 8px; padding: 20px; display: inline-block;'>
                        <span style='font-size: 36px; font-weight: bold; color: #10b981; letter-spacing: 8px; font-family: monospace;'>$verificationCode</span>
                    </div>
                </div>
                
                <div style='background: #f9fafb; border-radius: 8px; padding: 20px; margin: 20px 0;'>
                    <p style='color: #374151; margin: 0 0 10px 0;'><strong>How to complete registration:</strong></p>
                    <ol style='color: #6b7280; margin: 0; padding-left: 20px;'>
                        <li style='margin-bottom: 8px;'>Return to the registration page</li>
                        <li style='margin-bottom: 8px;'>Enter your details</li>
                        <li>Enter this 6-digit code when prompted</li>
                    </ol>
                </div>
                
                <p style='color: #ef4444; font-size: 14px; text-align: center; margin: 20px 0;'>⏱️ This code will expire in 15 minutes</p>
                
                <hr style='border: 1px solid #e5e7eb; margin: 30px 0;'>
                <p style='color: #9ca3af; font-size: 12px; text-align: center; margin: 0;'>If you didn't request this code, please ignore this email.</p>
            </div>
        ";

        $mail->send();
        
        http_response_code(200);
        echo json_encode(array(
            "success" => true, 
            "message" => "Verification code sent to your email. Please check your inbox.",
            "email" => $data->email
        ));
        
    } catch (Exception $e) {
        // Get detailed error information
        $errorMessage = $e->getMessage();
        $mailerError = $mail->ErrorInfo;
        
        // More detailed error information for debugging
        $errorDetails = array(
            "exception_message" => $errorMessage,
            "mailer_error" => $mailerError,
            "smtp_host" => $mail->Host,
            "smtp_port" => $mail->Port,
            "username_set" => !empty($gmailUsername),
            "password_set" => !empty($gmailPassword),
            "password_length" => strlen($gmailPassword)
        );
        
        // Check if it's a recipient/address issue
        if (stripos($errorMessage, 'recipient') !== false || 
            stripos($errorMessage, 'address') !== false ||
            stripos($mailerError, 'recipient') !== false ||
            stripos($mailerError, 'Recipient address rejected') !== false ||
            stripos($mailerError, 'mailbox unavailable') !== false) {
            $userMessage = "Email address not found. Please check and try again.";
        } else {
            $userMessage = "Failed to send verification email: " . $mailerError;
        }
        
        http_response_code(400);
        echo json_encode(array(
            "success" => false, 
            "message" => $userMessage,
            "debug" => $errorDetails
        ));
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error: " . $e->getMessage()));
}
?>
