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
    $envValue = static function (string $key, mixed $default = null): mixed {
        if (defined('ENV_SETTINGS') && is_array(ENV_SETTINGS) && array_key_exists($key, ENV_SETTINGS)) {
            return ENV_SETTINGS[$key];
        }

        return $default;
    };

    $config = [
        'host' => (string) $envValue('SMTP_HOST', ''),
        'port' => (int) $envValue('SMTP_PORT', 465),
        'username' => (string) $envValue('SMTP_USERNAME', ''),
        'password' => (string) $envValue('SMTP_PASSWORD', ''),
        'smtpsecure' => (string) $envValue('SMTP_SECURE', 'ssl'),
        'fromname' => (string) $envValue('SMTP_FROM_NAME', '星夜阁'),
        'systememail' => (string) $envValue('SMTP_FROM_EMAIL', ''),
        'charset' => (string) $envValue('SMTP_CHARSET', 'utf-8'),
        'timeout' => (int) $envValue('SMTP_TIMEOUT', 30),
        'keepalive' => (bool) $envValue('SMTP_KEEPALIVE', false),
        'retry_attempts' => (int) $envValue('SMTP_RETRY_ATTEMPTS', 3),
        'retry_delay' => (int) $envValue('SMTP_RETRY_DELAY', 5),
        'verify_peer' => (bool) $envValue('SMTP_VERIFY_PEER', false),
        'verify_peer_name' => (bool) $envValue('SMTP_VERIFY_PEER_NAME', false),
        'debug' => (int) $envValue('SMTP_DEBUG', 0),
    ];

    if ($config['host'] === '' || $config['username'] === '' || $config['password'] === '' || $config['systememail'] === '') {
        throw new RuntimeException('SMTP 配置缺失：请在 .env 中设置 SMTP_HOST/SMTP_USERNAME/SMTP_PASSWORD/SMTP_FROM_EMAIL');
    }
    
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
