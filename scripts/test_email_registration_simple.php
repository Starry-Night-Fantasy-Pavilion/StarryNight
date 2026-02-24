<?php

// Simplified email registration test script
// This script tests the core registration logic without full framework dependencies

echo "邮箱注册流程测试脚本（简化版）\n";

// 1. 设置测试数据
$testEmail = 'testuser_' . time() . '@example.com';
$testPassword = 'password123';
$testUsername = 'testuser_' . time();

echo "测试数据:\n";
echo " - 邮箱: $testEmail\n";
echo " - 用户名: $testUsername\n";
echo " - 密码: $testPassword\n";

// 2. 直接测试数据库连接和用户创建
echo "\n步骤 1: 测试数据库连接...\n";
try {
    // 加载数据库配置
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
    
    echo "SUCCESS: 数据库连接成功\n";
    
    // 3. 测试用户创建
    echo "\n步骤 2: 测试用户创建...\n";
    
    // 检查用户是否已存在
    $stmt = $pdo->prepare("SELECT id FROM `{$dbConfig['DB_PREFIX']}users` WHERE `email` = :email LIMIT 1");
    $stmt->execute([':email' => $testEmail]);
    if ($stmt->fetch()) {
        echo "ERROR: 用户已存在\n";
        exit(1);
    }
    
    // 创建用户
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
    $sql = "INSERT INTO `{$dbConfig['DB_PREFIX']}users` (username, email, password, created_at) VALUES (:username, :email, :password, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username' => $testUsername,
        ':email' => $testEmail,
        ':password' => $hashedPassword,
    ]);
    
    $userId = $pdo->lastInsertId();
    echo "SUCCESS: 用户创建成功，ID: $userId\n";
    
    // 4. 验证用户创建
    echo "\n步骤 3: 验证用户创建...\n";
    $stmt = $pdo->prepare("SELECT * FROM `{$dbConfig['DB_PREFIX']}users` WHERE `id` = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "SUCCESS: 用户验证成功\n";
        echo "用户ID: " . $user['id'] . "\n";
        echo "用户名: " . $user['username'] . "\n";
        echo "邮箱: " . $user['email'] . "\n";
        
        // 验证密码哈希
        if (password_verify($testPassword, $user['password'])) {
            echo "SUCCESS: 密码哈希验证通过\n";
        } else {
            echo "ERROR: 密码哈希验证失败\n";
        }
    } else {
        echo "ERROR: 用户验证失败\n";
    }
    
    // 5. 清理测试数据
    echo "\n步骤 4: 清理测试数据...\n";
    $stmt = $pdo->prepare("DELETE FROM `{$dbConfig['DB_PREFIX']}users` WHERE `id` = :id");
    $stmt->execute([':id' => $userId]);
    
    if ($stmt->rowCount() > 0) {
        echo "SUCCESS: 测试数据清理成功\n";
    } else {
        echo "WARNING: 未找到需要清理的测试数据\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "堆栈跟踪: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n测试完成\n";
?>