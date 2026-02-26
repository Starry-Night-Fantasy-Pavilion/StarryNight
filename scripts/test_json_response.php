<?php
/**
 * 测试邮件发送的JSON响应
 */

// 加载环境配置
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

// 加载必要的文件
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/services/Database.php';
require_once __DIR__ . '/../app/helpers.php';

// 模拟发送验证码请求
header('Content-Type: application/json; charset=utf-8');

try {
    // 临时禁用所有输出缓冲
    ob_start();
    
    $testEmail = 'test@example.com';
    $subject = '测试邮件';
    $content = '这是一封测试邮件';
    
    $errorMsg = null;
    // 测试脚本也采用短超时+不重试，避免 SMTP 连接异常时卡住很久（线上会导致 502）
    $result = send_system_mail($testEmail, $subject, $content, $errorMsg, [
        'timeout' => 10,
        'retry_attempts' => 1,
        'retry_delay' => 0,
    ]);
    
    // 清除所有输出（包括调试信息）
    ob_clean();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => '验证码已发送，请注意查收'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $errorMsg ?: '发送失败，请稍后重试'
        ]);
    }
} catch (Exception $e) {
    // 清除所有输出
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '发送异常: ' . $e->getMessage()
    ]);
}
