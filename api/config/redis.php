<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// Redis Configuration
class Redis_Config {
    private $host;
    private $port;
    private $password;
    private $redis;

    public function __construct() {
        // Load from environment or use local defaults
        $this->host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $this->port = getenv('REDIS_PORT') ?: 6379;
        $this->password = getenv('REDIS_PASSWORD') ?: null;
    }

    public function getConnection() {
        try {
            $config = [
                'scheme' => 'tcp',
                'host'   => $this->host,
                'port'   => $this->port,
            ];
            
            // Add password if provided (for Redis Cloud)
            if ($this->password) {
                $config['password'] = $this->password;
            }
            
            $this->redis = new Predis\Client($config);
            
            // Test connection
            $this->redis->ping();
            
            return $this->redis;
        } catch(Exception $e) {
            echo "Redis Connection Error: " . $e->getMessage();
            return null;
        }
    }
}
