<?php

// 直接从 .env 文件读取数据库配置
$envFile = __DIR__ . '/../.env';
$dbConfig = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }
        if (strpos($name, 'DB_') === 0) {
            $dbConfig[$name] = $value;
        }
    }
}

// 连接数据库
$dsn = "mysql:host={$dbConfig['DB_HOST']};port={$dbConfig['DB_PORT']};dbname={$dbConfig['DB_DATABASE']};charset=utf8mb4";
$pdo = new PDO($dsn, $dbConfig['DB_USERNAME'], $dbConfig['DB_PASSWORD'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$table = $dbConfig['DB_PREFIX'] . 'users';
$stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE email = ? LIMIT 1");
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