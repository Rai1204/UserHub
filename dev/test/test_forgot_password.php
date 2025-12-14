<?php
// Test password reset code sending
$testEmail = 'raiantony12@gmail.com';

echo "Testing password reset code for: $testEmail\n";
echo "==========================================\n\n";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [];

// Create temporary input
$input = json_encode(['email' => $testEmail]);
file_put_contents('php://temp', $input);

// Capture output
ob_start();

// Mock file_get_contents for php://input
$GLOBALS['mock_input'] = $input;

// Include the API file
$originalFileGetContents = 'file_get_contents';
eval('
function file_get_contents($filename) {
    if ($filename === "php://input") {
        return $GLOBALS["mock_input"];
    }
    return \\file_get_contents($filename);
}
');

try {
    include 'api/send_password_reset_code.php';
    $output = ob_get_clean();
    echo $output . "\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
