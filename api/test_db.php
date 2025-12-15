<?php
/**
 * Test database connection and list tables
 * Access: /api/test_db.php
 */

header("Content-Type: application/json");

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode([
            'success' => false,
            'error' => 'Could not connect to database'
        ]);
        exit;
    }
    
    // Get list of tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get connection info
    $info = [
        'success' => true,
        'connection' => 'OK',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'database' => getenv('DB_NAME') ?: 'user_management',
        'tables' => $tables,
        'table_count' => count($tables)
    ];
    
    // If users table exists, get count
    if (in_array('users', $tables)) {
        $stmt = $db->query("SELECT COUNT(*) FROM users");
        $info['users_count'] = $stmt->fetchColumn();
    }
    
    echo json_encode($info, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_PRETTY_PRINT);
}
