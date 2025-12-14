<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// Redis Configuration
class Redis_Config {
    private $host = "127.0.0.1";
    private $port = 6379;
    private $redis;

    public function getConnection() {
        try {
            $this->redis = new Predis\Client([
                'scheme' => 'tcp',
                'host'   => $this->host,
                'port'   => $this->port,
            ]);
            
            // Test connection
            $this->redis->ping();
            
            return $this->redis;
        } catch(Exception $e) {
            echo "Redis Connection Error: " . $e->getMessage();
            return null;
        }
    }
}
