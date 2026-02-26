<?php
/**
 * SMTP 邮件配置更新脚本
 * 
 * 用于修复 SMTP 邮件配置问题
 * 
 * 使用方法: php scripts/update_smtp_config.php
 */

// 加载环境配置
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }
        if ($name !== '') {
            $env[$name] = $value;
        }
    }
    define('ENV_SETTINGS', $env);
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/services/Database.php';

try {
    $pdo = app\services\Database::pdo();
    $prefix = app\services\Database::prefix();
    
    echo "数据库连接成功！\n";
    
    $envValue = static function (string $key, mixed $default = null): mixed {
        if (defined('ENV_SETTINGS') && is_array(ENV_SETTINGS) && array_key_exists($key, ENV_SETTINGS)) {
            return ENV_SETTINGS[$key];
        }

        $value = getenv($key);
        return $value === false ? $default : $value;
    };

    // SMTP 配置（从环境变量读取，避免硬编码敏感信息）
    $smtpConfig = [
        'host' => (string) $envValue('SMTP_HOST', ''),
        'port' => (int) $envValue('SMTP_PORT', 465),
        'username' => (string) $envValue('SMTP_USERNAME', ''),
        'password' => (string) $envValue('SMTP_PASSWORD', ''),
        'smtpsecure' => (string) $envValue('SMTP_SECURE', 'ssl'),  // 端口 465 需要使用 SSL
        'fromname' => (string) $envValue('SMTP_FROM_NAME', '星夜阁'),
        'systememail' => (string) $envValue('SMTP_FROM_EMAIL', ''),
        'charset' => (string) $envValue('SMTP_CHARSET', 'utf-8')
    ];

    if ($smtpConfig['host'] === '' || $smtpConfig['username'] === '' || $smtpConfig['password'] === '' || $smtpConfig['systememail'] === '') {
        throw new RuntimeException('SMTP 配置缺失：请在 .env 中设置 SMTP_HOST/SMTP_USERNAME/SMTP_PASSWORD/SMTP_FROM_EMAIL');
    }
    
    $configJson = json_encode($smtpConfig, JSON_UNESCAPED_UNICODE);
    
    $tableName = $prefix . 'admin_plugin_configs';
    
    // 检查配置是否存在
    $stmt = $pdo->prepare("SELECT * FROM $tableName WHERE plugin_id = ?");
    $stmt->execute(['email/asiayun_smtp_pro']);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "找到现有配置，更新中...\n";
        $stmt = $pdo->prepare("UPDATE $tableName SET config_json = ? WHERE plugin_id = ?");
        $stmt->execute([$configJson, 'email/asiayun_smtp_pro']);
        echo "配置已更新！\n";
    } else {
        echo "未找到配置，插入新配置...\n";
        $stmt = $pdo->prepare("INSERT INTO $tableName (plugin_id, config_json, created_at) VALUES (?, ?, NOW())");
        $stmt->execute(['email/asiayun_smtp_pro', $configJson]);
        echo "配置已插入！\n";
    }
    
    // 验证更新
    $stmt = $pdo->prepare("SELECT * FROM $tableName WHERE plugin_id = ?");
    $stmt->execute(['email/asiayun_smtp_pro']);
    $result = $stmt->fetch();
    
    echo "\n当前配置:\n";
    echo "----------------------------------------\n";
    if ($result && $result['config_json']) {
        $config = json_decode($result['config_json'], true);
        foreach ($config as $key => $value) {
            // 密码需要脱敏显示
            if ($key === 'password') {
                $value = '********';
            }
            echo "$key: $value\n";
        }
    }
    echo "----------------------------------------\n";
    
    echo "\nSMTP 配置更新完成！\n";
    
    // 同时更新 legacy_plugins.json 确保插件已启用
    $legacyFile = __DIR__ . '/../storage/framework/legacy_plugins.json';
    if (file_exists($legacyFile)) {
        $json = file_get_contents($legacyFile);
        $plugins = json_decode($json, true);
        
        if (!isset($plugins['email/asiayun_smtp_pro'])) {
            $plugins['email/asiayun_smtp_pro'] = [];
        }
        $plugins['email/asiayun_smtp_pro']['installed'] = true;
        $plugins['email/asiayun_smtp_pro']['status'] = 'enabled';
        
        file_put_contents($legacyFile, json_encode($plugins, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "插件已启用！\n";
    }
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
    exit(1);
}
