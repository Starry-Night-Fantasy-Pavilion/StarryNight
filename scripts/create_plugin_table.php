<?php
/**
 * 创建插件配置表
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

try {
    $pdo = app\services\Database::pdo();
    
    // 创建插件配置表
    $sql = "
    CREATE TABLE IF NOT EXISTS `admin_plugins` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `plugin_id` varchar(100) NOT NULL,
        `status` enum('enabled','disabled') NOT NULL DEFAULT 'disabled',
        `config_json` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `plugin_id` (`plugin_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "Table admin_plugins created successfully\n";
    
    // 插入SMTP服务插件配置
    $config = [
        'host' => 'mail15.serv00.com',
        'port' => 465,
        'username' => 'fazyaldzvh@fazyaldzvh.serv00.net',
        'password' => '0Y0dkjuLF(*#k5(ZhOu)',
        'smtpsecure' => 'ssl',
        'fromname' => '星夜阁',
        'systememail' => 'fazyaldzvh@fazyaldzvh.serv00.net',
        'charset' => 'utf-8',
        'timeout' => 30,
        'keepalive' => false,
        'retry_attempts' => 3,
        'retry_delay' => 5,
        'verify_peer' => false,
        'verify_peer_name' => false,
        'debug' => 0
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO `admin_plugins` (`plugin_id`, `status`, `config_json`) 
        VALUES ('email/smtp_service', 'enabled', :config)
        ON DUPLICATE KEY UPDATE 
        `status` = VALUES(`status`),
        `config_json` = VALUES(`config_json`)
    ");
    
    $stmt->execute([':config' => json_encode($config)]);
    echo "SMTP service plugin configured successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
