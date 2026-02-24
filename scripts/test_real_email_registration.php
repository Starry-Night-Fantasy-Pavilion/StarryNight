<?php

// Real email registration test script
// This script tests the actual registration process with a real email address

echo "真实邮箱注册流程测试脚本\n";

// 1. 设置测试数据
$testEmail = 'wss304343w1@2925.com';
$testPassword = 'password123';
$testUsername = 'testuser_' . time();

echo "测试数据:\n";
echo " - 邮箱: $testEmail\n";
echo " - 用户名: $testUsername\n";
echo " - 密码: $testPassword\n";

// 2. 启动会话并加载框架
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 加载 .env 配置
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

// 引入 Composer 自动加载
require_once __DIR__ . '/../vendor/autoload.php';

// 加载核心辅助函数
require_once __DIR__ . '/../app/helpers.php';

// 注册全局异常处理器
use app\services\ErrorHandler;
ErrorHandler::register();

// 3. 模拟发送验证码
echo "\n步骤 1: 模拟发送注册验证码...\n";
$_POST['method'] = 'email';
$_POST['target'] = $testEmail;

// 模拟 AuthController 的 sendRegisterCode 方法
$authController = new \app\frontend\controller\AuthController();

// 捕获输出
ob_start();
try {
    $authController->sendRegisterCode();
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    if ($response && isset($response['success']) && $response['success'] === true) {
        echo "SUCCESS: 验证码发送成功\n";
        echo "消息: " . ($response['message'] ?? '无') . "\n";
        
        // 从 Session 中获取验证码
        $sessionData = $_SESSION['register_verify'] ?? null;
        if ($sessionData && isset($sessionData['code'])) {
            $verifyCode = $sessionData['code'];
            echo "获取到的验证码: $verifyCode\n";
        } else {
            echo "ERROR: 无法从 Session 中获取验证码\n";
            exit(1);
        }
    } else {
        echo "FAILURE: 验证码发送失败\n";
        echo "错误: " . ($response['message'] ?? '未知错误') . "\n";
        exit(1);
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "ERROR: 发送验证码时发生异常: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. 模拟注册提交
echo "\n步骤 2: 模拟提交注册表单...\n";
$_POST['register_method'] = 'email';
$_POST['username'] = $testUsername;
$_POST['email'] = $testEmail;
$_POST['password'] = $testPassword;
$_POST['password_confirm'] = $testPassword;
$_POST['verify_code'] = $verifyCode;

// 捕获重定向
ob_start();
try {
    $authController->register();
    $output = ob_get_clean();
    
    // 如果没有重定向，检查输出内容
    if (!empty($output)) {
        echo "注册输出: " . $output . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "ERROR: 注册过程中发生异常: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. 验证用户是否成功创建
echo "\n步骤 3: 验证用户是否成功创建...\n";
try {
    $pdo = \app\services\Database::pdo();
    $table = \app\services\Database::prefix() . 'users';
    
    $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `email` = :email LIMIT 1");
    $stmt->execute([':email' => $testEmail]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "SUCCESS: 用户创建成功\n";
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
        echo "FAILURE: 用户创建失败，数据库中未找到该用户\n";
    }
} catch (Exception $e) {
    echo "ERROR: 验证用户创建时发生异常: " . $e->getMessage() . "\n";
}

echo "\n测试完成\n";
echo "注意: 测试用户不会被自动删除，请手动清理\n";
?>