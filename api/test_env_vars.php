<?php
/**
 * Test endpoint to verify environment variables are loaded
 * REMOVE THIS FILE AFTER TESTING!
 */

header("Content-Type: application/json");

$envVars = [
    'GMAIL_USERNAME' => getenv('GMAIL_USERNAME'),
    'GMAIL_APP_PASSWORD' => getenv('GMAIL_APP_PASSWORD'),
    'DB_HOST' => getenv('DB_HOST'),
    'MONGODB_URI' => getenv('MONGODB_URI') ? 'SET' : 'NOT SET',
    'REDIS_HOST' => getenv('REDIS_HOST')
];

$response = [
    'environment_variables' => [],
    'all_env' => array_slice($_ENV, 0, 5) // First 5 env vars
];

foreach ($envVars as $key => $value) {
    if ($key === 'GMAIL_APP_PASSWORD' && $value) {
        // Don't expose full password
        $response['environment_variables'][$key] = 'SET (' . strlen($value) . ' chars)';
    } elseif ($value) {
        $response['environment_variables'][$key] = $value;
    } else {
        $response['environment_variables'][$key] = 'NOT SET';
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
