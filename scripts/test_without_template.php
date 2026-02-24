<?php
/**
 * 测试不使用邮件模板的发送
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

header('Content-Type: application/json; charset=utf-8');

try {
    ob_start();
    
    $testEmail = 'test@example.com';
    $subject = '测试邮件（无模板）';
    $content = '<h2>验证码</h2><p>您的验证码是：123456</p><p>此邮件为系统自动发送，请勿回复。</p>';
    
    $errorMsg = null;
    $result = send_system_mail($testEmail, $subject, $content, $errorMsg);
    
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
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '发送异常: ' . $e->getMessage()
    ]);
}
