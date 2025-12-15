<?php
/**
 * Router script for PHP built-in server on Render
 * Routes requests to appropriate files
 */

// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove query string
$uri = urldecode($uri);

// Handle static files
$file = __DIR__ . $uri;

// If it's a static file and exists, serve it
if ($uri !== '/' && file_exists($file)) {
    return false; // Let PHP built-in server handle it
}

// Route to index.html for root
if ($uri === '/') {
    readfile(__DIR__ . '/index.html');
    exit;
}

// For API routes, let PHP handle them naturally
if (strpos($uri, '/api/') === 0) {
    return false;
}

// For page routes, serve them directly
if (strpos($uri, '/pages/') === 0 && file_exists($file)) {
    return false;
}

// For assets, serve them directly
if (strpos($uri, '/assets/') === 0 && file_exists($file)) {
    return false;
}

// Default: return 404
http_response_code(404);
echo '404 Not Found';
