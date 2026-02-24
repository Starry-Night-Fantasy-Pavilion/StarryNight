<?php

require_once 'vendor/autoload.php';
require_once 'app/helpers.php';
use app\services\Database;

$pdo = Database::pdo();
$table = Database::prefix() . 'users';
$stmt = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE email = ? LIMIT 1');
$stmt->execute(['wss304343w1@2925.com']);
$user = $stmt->fetch();

if ($user) {
    echo "用户创建成功:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "用户名: " . $user['username'] . "\n";
    echo "邮箱: " . $user['email'] . "\n";
    echo "注册时间: " . $user['created_at'] . "\n";
} else {
    echo "用户未找到\n";
}
?>