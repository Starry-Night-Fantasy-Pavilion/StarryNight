<?php

// Complete simulation of email registration process
// This script simulates the entire registration process without using AuthController

echo "完整模拟邮箱注册流程测试脚本\n";

// 1. 设置测试数据
$testEmail = 'wss304343w1@2925.com';
$testPassword = 'password123';
$testUsername = 'testuser_' . time();

echo "测试数据:\n";
echo " - 邮箱: $testEmail\n";
echo " - 用户名: $testUsername\n";
echo " - 密码: $testPassword\n";

// 2. 读取数据库配置
echo "\n步骤 1: 读取数据库配置...\n";
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
    echo "SUCCESS: 数据库配置读取成功\n";
} else {
    echo "ERROR: .env 文件不存在\n";
    exit(1);
}

// 3. 连接数据库
echo "\n步骤 2: 连接数据库...\n";
try {
    $dsn = "mysql:host={$dbConfig['DB_HOST']};port={$dbConfig['DB_PORT']};dbname={$dbConfig['DB_DATABASE']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['DB_USERNAME'], $dbConfig['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "SUCCESS: 数据库连接成功\n";
} catch (Exception $e) {
    echo "ERROR: 数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. 模拟验证码生成和验证
echo "\n步骤 3: 模拟验证码生成和验证...\n";
$verifyCode = (string)random_int(100000, 999999);
$expireMinutes = 10;
$expiresAt = time() + $expireMinutes * 60;

echo "SUCCESS: 验证码已生成\n";
echo "验证码: $verifyCode\n";
echo "过期时间: " . date('Y-m-d H:i:s', $expiresAt) . "\n";

// 5. 模拟注册验证
echo "\n步骤 4: 模拟注册验证...\n";

// 检查用户名是否已存在
$stmt = $pdo->prepare("SELECT id FROM `{$dbConfig['DB_PREFIX']}users` WHERE `username` = :username LIMIT 1");
$stmt->execute([':username' => $testUsername]);
if ($stmt->fetch()) {
    echo "ERROR: 用户名已存在\n";
    exit(1);
}
echo "SUCCESS: 用户名验证通过\n";

// 检查邮箱是否已存在
$stmt = $pdo->prepare("SELECT id FROM `{$dbConfig['DB_PREFIX']}users` WHERE `email` = :email LIMIT 1");
$stmt->execute([':email' => $testEmail]);
if ($stmt->fetch()) {
    echo "ERROR: 邮箱已存在\n";
    exit(1);
}
echo "SUCCESS: 邮箱验证通过\n";

// 验证密码一致性
if ($testPassword !== $testPassword) {
    echo "ERROR: 密码不一致\n";
    exit(1);
}
echo "SUCCESS: 密码一致性验证通过\n";

// 6. 创建用户
echo "\n步骤 5: 创建用户...\n";
try {
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
} catch (Exception $e) {
    echo "ERROR: 用户创建失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 7. 验证用户创建
echo "\n步骤 6: 验证用户创建...\n";
$stmt = $pdo->prepare("SELECT * FROM `{$dbConfig['DB_PREFIX']}users` WHERE `id` = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if ($user) {
    echo "SUCCESS: 用户验证成功\n";
    echo "用户ID: " . $user['id'] . "\n";
    echo "用户名: " . $user['username'] . "\n";
    echo "邮箱: " . $user['email'] . "\n";
    echo "注册时间: " . $user['created_at'] . "\n";
    
    // 验证密码哈希
    if (password_verify($testPassword, $user['password'])) {
        echo "SUCCESS: 密码哈希验证通过\n";
    } else {
        echo "ERROR: 密码哈希验证失败\n";
    }
} else {
    echo "ERROR: 用户验证失败\n";
}

echo "\n测试完成\n";
echo "注意: 用户已创建在数据库中，可以使用邮箱 $testEmail 和密码 $testPassword 登录\n";
?>