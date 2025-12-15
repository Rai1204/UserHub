<?php
// Load environment variables from .env file

function loadEnv($filePath) {
    // On production (Render), environment variables are already set by the platform
    // Only load from file for local development
    if (!file_exists($filePath)) {
        // File doesn't exist, assume production with env vars from platform
        return;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Set as environment variable if not already set
            // This allows platform env vars to take precedence
            if (!getenv($key) && !isset($_ENV[$key])) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Load .env file from project root (go up two directories from api/config/)
loadEnv(__DIR__ . '/../../.env');
?>
