<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// Connect to Redis
$redis = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => '127.0.0.1',
    'port'   => 6379,
]);

echo "<h2>Redis Data Viewer</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }</style>";

try {
    // Get all keys
    $keys = $redis->keys('*');
    
    if (empty($keys)) {
        echo "<p>No sessions found in Redis.</p>";
    } else {
        echo "<p><strong>Total Sessions:</strong> " . count($keys) . "</p>";
        
        foreach ($keys as $key) {
            echo "<hr>";
            echo "<h3>Session Token: <code>" . htmlspecialchars($key) . "</code></h3>";
            
            // Get value
            $value = $redis->get($key);
            $data = json_decode($value, true);
            
            // Get TTL
            $ttl = $redis->ttl($key);
            $hours = floor($ttl / 3600);
            $minutes = floor(($ttl % 3600) / 60);
            
            echo "<strong>Expires in:</strong> {$hours}h {$minutes}m<br>";
            echo "<strong>Data:</strong>";
            echo "<pre>" . print_r($data, true) . "</pre>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
