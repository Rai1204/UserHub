<?php
// Test .env configuration
require_once 'api/config/env.php';

echo "=== Environment Variables Test ===\n\n";

$gmailUsername = getenv('GMAIL_USERNAME');
$gmailPassword = getenv('GMAIL_APP_PASSWORD');

echo "GMAIL_USERNAME: " . ($gmailUsername ? "✓ Loaded (" . substr($gmailUsername, 0, 3) . "***)" : "✗ NOT FOUND") . "\n";
echo "GMAIL_APP_PASSWORD: " . ($gmailPassword ? "✓ Loaded (" . strlen($gmailPassword) . " characters)" : "✗ NOT FOUND") . "\n\n";

if (empty($gmailUsername) || empty($gmailPassword)) {
    echo "❌ ERROR: Gmail credentials are missing!\n\n";
    echo "Please edit the .env file and add:\n";
    echo "GMAIL_USERNAME=your-email@gmail.com\n";
    echo "GMAIL_APP_PASSWORD=your-16-char-app-password\n";
} else {
    echo "✅ SUCCESS: All credentials are loaded!\n";
    echo "\nYou can now test registration with email verification.\n";
}
?>
