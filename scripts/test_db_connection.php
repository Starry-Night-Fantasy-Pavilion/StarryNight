<?php
require_once __DIR__ . '/../app/services/Database.php';
use app\services\Database;

try {
    $pdo = Database::pdo();
    $prefix = Database::prefix();
    $stmt = $pdo->query("SELECT * FROM `{$prefix}users` LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Found user: " . $user['username'] . " (ID: " . $user['id'] . ")
";
    } else {
        echo "No users found in database. Creating a test user...
";
        $pdo->exec("INSERT INTO `{$prefix}users` (username, password, email, nickname, status, created_at) VALUES ('testuser', 'password', 'test@example.com', 'Test User', 'active', NOW())");
        $userId = $pdo->lastInsertId();
        echo "Created test user with ID: $userId
";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "
";
}
