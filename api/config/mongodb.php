<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// MongoDB Configuration
class MongoDB_Config {
    private $uri;
    private $db_name;
    private $client;
    private $db;

    public function __construct() {
        // Check for MongoDB Atlas URI (external) or use local
        $this->uri = getenv('MONGODB_URI') ?: 'mongodb://localhost:27017';
        $this->db_name = getenv('MONGODB_DB') ?: 'user_management';
    }

    public function getDatabase() {
        try {
            $this->client = new MongoDB\Client($this->uri);
            $this->db = $this->client->{$this->db_name};
            return $this->db;
        } catch(Exception $e) {
            echo "MongoDB Connection Error: " . $e->getMessage();
            return null;
        }
    }

    public function getCollection($collectionName) {
        $db = $this->getDatabase();
        if ($db) {
            return $db->{$collectionName};
        }
        return null;
    }
}
