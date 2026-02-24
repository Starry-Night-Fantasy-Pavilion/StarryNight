<?php

// This script is intended to test the email registration process.
// It will simulate a user registering with an email address and verify the outcome.

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load .env configuration
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
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
        $env[$name] = $value;
    }
    define('ENV_SETTINGS', $env);
}

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load core helper functions
require_once __DIR__ . '/../app/helpers.php';

// Register global exception handler
use app\services\ErrorHandler;
ErrorHandler::register();

// 1. 设置测试数据
$testEmail = 'testuser_' . time() . '@example.com';
$testPassword = 'password123';
$testUsername = 'testuser_' . time();

// 2. 模拟发送验证码
$_POST['method'] = 'email';
$_POST['target'] = $testEmail;

// 直接在 Session 中设置验证码，跳过邮件发送
$verifyCode = (string)random_int(100000, 999999);
$expireMinutes = 10;
$expiresAt = time() + $expireMinutes * 60;
$_SESSION['register_verify'] = [
    'method' => 'email',
    'target' => $testEmail,
    'code' => $verifyCode,
    'expires_at' => $expiresAt,
];

// 3. 模拟注册提交
$_POST['register_method'] = 'email';
$_POST['username'] = $testUsername;
$_POST['email'] = $testEmail;
$_POST['password'] = $testPassword;
$_POST['password_confirm'] = $testPassword;
$_POST['verify_code'] = $verifyCode;

// 模拟 AuthController 的 register 方法
$authController = new \app\frontend\controller\AuthController();

// 由于 register 方法会重定向，我们需要捕获重定向头
ob_start();
try {
    $authController->register();
    $output = ob_get_clean();
    
    // 如果没有重定向，检查输出内容
    if (!empty($output)) {
        // 暂存输出，稍后显示
        $registerOutput = $output;
    }
} catch (Exception $e) {
    ob_end_clean();
    $registerError = $e->getMessage();
}

// 4. 验证用户是否成功创建
try {
    $pdo = \app\services\Database::pdo();
    $table = \app\services\Database::prefix() . 'users';
    
    $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `email` = :email LIMIT 1");
    $stmt->execute([':email' => $testEmail]);
    $user = $stmt->fetch();
    
    if ($user) {
        $userCreated = true;
        $userId = $user['id'];
        $passwordValid = password_verify($testPassword, $user['password']);
    } else {
        $userCreated = false;
    }
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// 5. 清理测试数据
try {
    $pdo = \app\services\Database::pdo();
    $table = \app\services\Database::prefix() . 'users';
    
    $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE `email` = :email");
    $stmt->execute([':email' => $testEmail]);
    
    if ($stmt->rowCount() > 0) {
        $dataCleaned = true;
    } else {
        $dataCleaned = false;
    }
} catch (Exception $e) {
    $cleanError = $e->getMessage();
}

// 现在可以安全地输出所有结果
echo "邮箱注册流程测试脚本\n";

echo "\n测试数据:\n";
echo " - 邮箱: $testEmail\n";
echo " - 用户名: $testUsername\n";
echo " - 密码: $testPassword\n";

echo "\n步骤 1: 模拟发送注册验证码...\n";
echo "SUCCESS: 验证码已生成\n";
echo "验证码: $verifyCode\n";

echo "\n步骤 2: 模拟提交注册表单...\n";
if (isset($registerError)) {
    echo "ERROR: 注册过程中发生异常: $registerError\n";
} elseif (isset($registerOutput)) {
    echo "注册输出: $registerOutput\n";
} else {
    echo "SUCCESS: 注册表单提交成功\n";
}

echo "\n步骤 3: 验证用户是否成功创建...\n";
if (isset($dbError)) {
    echo "ERROR: 验证用户创建时发生异常: $dbError\n";
} elseif ($userCreated) {
    echo "SUCCESS: 用户创建成功\n";
    echo "用户ID: $userId\n";
    echo "用户名: $user[username]\n";
    echo "邮箱: $user[email]\n";
    echo "注册时间: $user[created_at]\n";
    
    if ($passwordValid) {
        echo "SUCCESS: 密码哈希验证通过\n";
    } else {
        echo "ERROR: 密码哈希验证失败\n";
    }
} else {
    echo "FAILURE: 用户创建失败，数据库中未找到该用户\n";
}

echo "\n步骤 4: 清理测试数据...\n";
if (isset($cleanError)) {
    echo "ERROR: 清理测试数据时发生异常: $cleanError\n";
} elseif ($dataCleaned) {
    echo "SUCCESS: 测试数据清理成功\n";
} else {
    echo "WARNING: 未找到需要清理的测试数据\n";
}

echo "\n测试完成\n";
?>
