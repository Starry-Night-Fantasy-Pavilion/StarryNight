<?php
/**
 * 测试邮件模板功能
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

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/services/Database.php';
require_once __DIR__ . '/../app/helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    ob_start();
    
    // 测试获取邮件模板
    $template = get_email_template('register_verify_email', [
        'code' => '123456',
        'minutes' => '15'
    ]);
    
    ob_clean();
    
    if ($template) {
        echo json_encode([
            'success' => true,
            'message' => '邮件模板获取成功',
            'data' => [
                'subject' => $template['subject'],
                'body_length' => strlen($template['body']),
                'has_placeholders' => strpos($template['body'], '{{') !== false
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '邮件模板不存在或获取失败'
        ]);
    }
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '测试异常: ' . $e->getMessage()
    ]);
}
