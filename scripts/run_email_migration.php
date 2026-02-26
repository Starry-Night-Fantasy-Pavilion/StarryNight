<?php
/**
 * 执行邮件系统相关迁移
 */

echo "开始执行邮件系统迁移...\n\n";

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
    
    // 1. 检查并创建 admin_plugins 表
    echo "步骤 1: 检查 admin_plugins 表...\n";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'admin_plugins'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "  创建 admin_plugins 表...\n";
        $sql = file_get_contents(__DIR__ . '/../database/migrations/009_admin_plugins.sql');
        $sql = str_replace('__PREFIX__', '', $sql);
        $pdo->exec($sql);
        echo "  ✓ admin_plugins 表创建成功\n";
    } else {
        echo "  ✓ admin_plugins 表已存在\n";
    }
    
    // 2. 检查并创建 notification_templates 表
    echo "\n步骤 2: 检查 notification_templates 表...\n";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'notification_templates'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "  创建 notification_templates 表...\n";
        $createSql = "
        CREATE TABLE `notification_templates` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `channel` varchar(50) NOT NULL COMMENT 'email/sms/system',
          `code` varchar(100) NOT NULL,
          `title` varchar(255) DEFAULT NULL,
          `content` text NOT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_channel_code` (`channel`,`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知模板';
        ";
        $pdo->exec($createSql);
        echo "  ✓ notification_templates 表创建成功\n";
    } else {
        echo "  ✓ notification_templates 表已存在\n";
    }
    
    // 3. 插入邮件模板数据
    echo "\n步骤 3: 插入邮件模板数据...\n";
    $migrationSql = file_get_contents(__DIR__ . '/../database/migrations/020_email_templates_init.sql');
    $migrationSql = str_replace('__PREFIX__', '', $migrationSql);
    
    // 提取 INSERT 语句并执行
    preg_match_all("/INSERT INTO.*?;/s", $migrationSql, $insertMatches);
    $insertCount = 0;
    
    foreach ($insertMatches[0] as $insertSql) {
        try {
            $pdo->exec($insertSql);
            $insertCount++;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                echo "  ⚠ 插入数据时出错: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "  ✓ 插入了 {$insertCount} 条模板数据\n";
    
    // 4. 配置 SMTP 插件
    echo "\n步骤 4: 配置 SMTP 插件...\n";
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
        INSERT INTO admin_plugins (plugin_id, status, config_json) 
        VALUES ('email/smtp_service', 'enabled', :config)
        ON DUPLICATE KEY UPDATE 
        status = VALUES(status),
        config_json = VALUES(config_json),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([':config' => json_encode($config)]);
    echo "  ✓ SMTP 插件配置完成\n";
    
    // 5. 验证模板文件
    echo "\n步骤 5: 验证邮件模板文件...\n";
    $templateFiles = [
        'register_verify_email.html',
        'reset_password_email.html',
        'welcome_email.html'
    ];
    
    $templateDir = __DIR__ . '/../public/static/errors/html/Email/';
    $fileCount = 0;
    
    foreach ($templateFiles as $file) {
        if (file_exists($templateDir . $file)) {
            $fileCount++;
            echo "  ✓ {$file}\n";
        } else {
            echo "  ✗ {$file} (缺失)\n";
        }
    }
    
    echo "\n迁移完成！\n";
    echo "✓ 创建了 {$fileCount} 个邮件模板文件\n";
    echo "✓ 配置了 SMTP 插件\n";
    echo "✓ 插入了默认邮件模板数据\n";
    
    // 6. 测试邮件模板功能
    echo "\n步骤 6: 测试邮件模板功能...\n";
    require_once __DIR__ . '/../app/helpers.php';
    
    $template = get_email_template('register_verify_email', [
        'code' => 'TEST123',
        'minutes' => '15'
    ]);
    
    if ($template) {
        echo "  ✓ 邮件模板功能正常\n";
        echo "  主题: " . $template['subject'] . "\n";
        echo "  内容长度: " . strlen($template['body']) . " 字符\n";
    } else {
        echo "  ✗ 邮件模板功能异常\n";
    }
    
    echo "\n🎉 邮件系统迁移全部完成！\n";
    
} catch (Exception $e) {
    echo "❌ 迁移失败: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
}
