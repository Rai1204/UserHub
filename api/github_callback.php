<?php
// GitHub OAuth callback handler
// This page receives the authorization code from GitHub

$code = isset($_GET['code']) ? $_GET['code'] : null;
$error = isset($_GET['error']) ? $_GET['error'] : null;

if ($error) {
    // User denied access or error occurred
    header('Location: ../pages/login.html?github_error=' . urlencode($error));
    exit();
}

if (!$code) {
    // No code provided
    header('Location: ../pages/login.html?github_error=no_code');
    exit();
}

// Forward to the API to process the code
header('Location: ../pages/github-success.html?code=' . urlencode($code));
exit();
?>
