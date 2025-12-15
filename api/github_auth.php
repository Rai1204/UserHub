<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';
require_once 'config/redis.php';

// GitHub OAuth Configuration
$clientId = 'Ov23lix7T3YzZyPrmdvX';
$clientSecret = 'a29972b25e9dcc8b725e651ac0f1460dd8ef8fde';
$redirectUri = 'http://localhost/user-management/api/github_callback.php';

// Get authorization code from query params
$code = isset($_GET['code']) ? $_GET['code'] : null;

if (!$code) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "No authorization code provided."));
    exit();
}

try {
    // Exchange code for access token
    $tokenUrl = 'https://github.com/login/oauth/access_token';
    $postData = array(
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'code' => $code,
        'redirect_uri' => $redirectUri
    );
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $tokenData = json_decode($response, true);
    
    if (!isset($tokenData['access_token'])) {
        throw new Exception("Failed to obtain access token from GitHub.");
    }
    
    $accessToken = $tokenData['access_token'];
    
    // Get user info from GitHub
    $userUrl = 'https://api.github.com/user';
    $ch = curl_init($userUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $accessToken,
        'User-Agent: User-Management-App'
    ));
    
    $userResponse = curl_exec($ch);
    curl_close($ch);
    
    $userData = json_decode($userResponse, true);
    
    if (!isset($userData['id'])) {
        throw new Exception("Failed to fetch user data from GitHub.");
    }
    
    // Get user email (might be private)
    $emailUrl = 'https://api.github.com/user/emails';
    $ch = curl_init($emailUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $accessToken,
        'User-Agent: User-Management-App'
    ));
    
    $emailResponse = curl_exec($ch);
    curl_close($ch);
    
    $emails = json_decode($emailResponse, true);
    $primaryEmail = null;
    
    if (is_array($emails)) {
        foreach ($emails as $email) {
            if ($email['primary'] && $email['verified']) {
                $primaryEmail = $email['email'];
                break;
            }
        }
    }
    
    if (!$primaryEmail && isset($userData['email'])) {
        $primaryEmail = $userData['email'];
    }
    
    if (!$primaryEmail) {
        throw new Exception("Could not retrieve email from GitHub. Please make sure your email is verified and public.");
    }
    
    $githubId = $userData['id'];
    $username = $userData['login'];
    $name = isset($userData['name']) ? $userData['name'] : $username;
    $avatarUrl = isset($userData['avatar_url']) ? $userData['avatar_url'] : null;
    
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed.");
    }
    
    // Check if user exists with this GitHub ID
    $query = "SELECT id, username, email, password, created_at, last_login FROM users WHERE github_id = :github_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':github_id', $githubId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // User exists, log them in
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user has a password, if not set it to username
        if (empty($user['password'])) {
            $hashedPassword = password_hash($user['username'], PASSWORD_BCRYPT);
            $setPasswordQuery = "UPDATE users SET password = :password WHERE id = :id";
            $setPasswordStmt = $db->prepare($setPasswordQuery);
            $setPasswordStmt->bindParam(':password', $hashedPassword);
            $setPasswordStmt->bindParam(':id', $user['id']);
            $setPasswordStmt->execute();
        }
        
        // Update last login
        $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':id', $user['id']);
        $updateStmt->execute();
        
    } else {
        // Check if email already exists (user might have registered with email/password)
        $emailQuery = "SELECT id, username, email, github_id, password, created_at, last_login FROM users WHERE email = :email";
        $emailStmt = $db->prepare($emailQuery);
        $emailStmt->bindParam(':email', $primaryEmail);
        $emailStmt->execute();
        
        if ($emailStmt->rowCount() > 0) {
            // Email exists, link GitHub account
            $user = $emailStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user['github_id']) {
                throw new Exception("This email is already linked to another GitHub account.");
            }
            
            // Link GitHub account and set password if user doesn't have one
            if (empty($user['password'])) {
                $hashedPassword = password_hash($user['username'], PASSWORD_BCRYPT);
                $linkQuery = "UPDATE users SET github_id = :github_id, password = :password, last_login = NOW() WHERE id = :id";
                $linkStmt = $db->prepare($linkQuery);
                $linkStmt->bindParam(':github_id', $githubId);
                $linkStmt->bindParam(':password', $hashedPassword);
                $linkStmt->bindParam(':id', $user['id']);
            } else {
                $linkQuery = "UPDATE users SET github_id = :github_id, last_login = NOW() WHERE id = :id";
                $linkStmt = $db->prepare($linkQuery);
                $linkStmt->bindParam(':github_id', $githubId);
                $linkStmt->bindParam(':id', $user['id']);
            }
            $linkStmt->execute();
            
            // Re-fetch user with updated last_login
            $refreshQuery = "SELECT id, username, email, created_at, last_login FROM users WHERE id = ?";
            $refreshStmt = $db->prepare($refreshQuery);
            $refreshStmt->execute([$user['id']]);
            $user = $refreshStmt->fetch(PDO::FETCH_ASSOC);
            
        } else {
            // New user, create account with password same as username
            $hashedPassword = password_hash($username, PASSWORD_BCRYPT);
            
            $insertQuery = "INSERT INTO users (username, email, password, github_id, created_at, last_login) VALUES (:username, :email, :password, :github_id, NOW(), NOW())";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(':username', $username);
            $insertStmt->bindParam(':email', $primaryEmail);
            $insertStmt->bindParam(':password', $hashedPassword);
            $insertStmt->bindParam(':github_id', $githubId);
            $insertStmt->execute();
            
            $userId = $db->lastInsertId();
            
            // Fetch the newly created user with all fields
            $newUserQuery = "SELECT id, username, email, created_at, last_login FROM users WHERE id = ?";
            $newUserStmt = $db->prepare($newUserQuery);
            $newUserStmt->execute([$userId]);
            $user = $newUserStmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    // Create session token
    $redis_config = new Redis_Config();
    $redis = $redis_config->getConnection();
    
    if (!$redis) {
        throw new Exception("Redis connection failed.");
    }
    
    $sessionToken = bin2hex(random_bytes(32));
    $sessionData = json_encode(array(
        'userId' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email']
    ));
    
    $redis->setex($sessionToken, 86400, $sessionData); // 24 hours
    
    // Return success with redirect
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "sessionToken" => $sessionToken,
        "userId" => $user['id'],
        "username" => $user['username'],
        "email" => $user['email'],
        "created_at" => $user['created_at'],
        "last_login" => $user['last_login'],
        "message" => "GitHub login successful!"
    ));
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => $e->getMessage()));
}
?>
