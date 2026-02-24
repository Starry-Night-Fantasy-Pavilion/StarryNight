<?php

$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    die(".env 文件不存在: {$envFile}\n");
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
    
    $stmt = $pdo->prepare("SELECT id, username, password, email FROM `{$table}` WHERE id = 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "管理员账号信息：\n";
        echo "ID: {$admin['id']}\n";
        echo "用户名: {$admin['username']}\n";
        echo "邮箱: {$admin['email']}\n";
        echo "密码哈希: {$admin['password']}\n";
        
        $testPassword = 'admin123';
        if (password_verify($testPassword, $admin['password'])) {
            echo "\n测试密码 'admin123' 验证成功！\n";
        } else {
            echo "\n测试密码 'admin123' 验证失败！\n";
            echo "可能需要使用安装时设置的密码\n";
        }
    } else {
        echo "没有找到管理员账号\n";
    }
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
}