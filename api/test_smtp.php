<?php
/**
 * Test SMTP connectivity from Render
 */

header("Content-Type: application/json");
set_time_limit(30);

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config/env.php';

loadEnv(__DIR__ . '/../.env');

use PHPMailer\PHPMailer\PHPMailer;

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Test 1: Check environment variables
$gmailUsername = getenv('GMAIL_USERNAME');
$gmailPassword = getenv('GMAIL_APP_PASSWORD');

$results['tests']['env_vars'] = [
    'username' => !empty($gmailUsername) ? $gmailUsername : 'NOT SET',
    'password' => !empty($gmailPassword) ? 'SET (' . strlen($gmailPassword) . ' chars)' : 'NOT SET'
];

// Test 2: Check if SMTP host is reachable
$results['tests']['smtp_host'] = [
    'host' => 'smtp.gmail.com',
    'port' => 587
];

$startTime = microtime(true);
$socket = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);
$endTime = microtime(true);

if ($socket) {
    $results['tests']['smtp_host']['reachable'] = true;
    $results['tests']['smtp_host']['time'] = round(($endTime - $startTime) * 1000, 2) . 'ms';
    fclose($socket);
} else {
    $results['tests']['smtp_host']['reachable'] = false;
    $results['tests']['smtp_host']['error'] = "$errstr ($errno)";
}

// Test 3: Try PHPMailer SMTP connection
if (!empty($gmailUsername) && !empty($gmailPassword)) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $gmailUsername;
        $mail->Password = $gmailPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 10;
        $mail->SMTPDebug = 0;
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Just test connection, don't send
        $mail->smtpConnect();
        
        $results['tests']['phpmailer'] = [
            'success' => true,
            'message' => 'SMTP connection successful'
        ];
        
        $mail->smtpClose();
        
    } catch (Exception $e) {
        $results['tests']['phpmailer'] = [
            'success' => false,
            'error' => $e->getMessage(),
            'mailer_error' => $mail->ErrorInfo
        ];
    }
} else {
    $results['tests']['phpmailer'] = [
        'success' => false,
        'error' => 'Gmail credentials not configured'
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT);
