<?php

$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    die(".env 文件不存在\n");
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    list($name, $value) = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value);
    if (preg_match('/^"(.*)"$/', $value, $matches)) {
        $value = $matches[1];
    } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
        $value = $matches[1];
    }
    $env[$name] = $value;
}

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}",
        $env['DB_USERNAME'],
        $env['DB_PASSWORD']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $prefix = $env['DB_PREFIX'] ?? '';
    $table = $prefix . 'admin_admins';
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}`");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    echo "管理员账号数量: {$count}\n";
    
    if ($count == 0) {
        echo "没有管理员账号，正在创建默认管理员...\n";
        
        $username = 'admin';
        $password = 'admin123';
        $email = 'admin@example.com';
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO `{$table}` (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $email]);
        
        echo "默认管理员账号创建成功！\n";
        echo "用户名: {$username}\n";
        echo "密码: {$password}\n";
        echo "邮箱: {$email}\n";
        echo "\n请登录后立即修改密码！\n";
    } else {
        echo "当前管理员账号列表：\n";
        $stmt = $pdo->prepare("SELECT id, username, email FROM `{$table}`");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($admins as $admin) {
            echo "ID: {$admin['id']}, 用户名: {$admin['username']}, 邮箱: {$admin['email']}\n";
        }
    }
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
}