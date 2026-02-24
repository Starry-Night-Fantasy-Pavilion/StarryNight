<?php
/**
 * 使用已知可靠的SMTP服务器测试
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
    
    // 直接使用PHPMailer测试
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    $mail = new PHPMailer(true);
    
    // 使用Gmail SMTP测试（需要替换为真实的账号密码）
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'test@gmail.com'; // 替换为真实账号
    $mail->Password = 'password'; // 替换为真实密码或应用专用密码
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->SMTPDebug = 0;
    
    $mail->setFrom('test@gmail.com', 'Test Sender');
    $mail->addAddress('test@example.com');
    
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email.';
    
    $result = $mail->send();
    
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'message' => '邮件系统工作正常，SMTP连接成功'
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'SMTP测试失败: ' . $e->getMessage()
    ]);
}
