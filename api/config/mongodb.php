<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// MongoDB Configuration
class MongoDB_Config {
    private $host = "localhost";
    private $port = "27017";
    private $db_name = "user_management";
    private $client;
    private $db;

    public function getDatabase() {
        try {
            $this->client = new MongoDB\Client("mongodb://" . $this->host . ":" . $this->port);
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
