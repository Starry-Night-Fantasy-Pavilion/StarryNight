<?php
/**
 * SMTP 邮件配置更新脚本
 * 
 * 用于修复 SMTP 邮件配置问题
 * 
 * 使用方法: php scripts/update_smtp_config.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// 数据库配置
$host = '127.0.0.1';
$port = 3306;
$dbName = '51111';
$username = '51111';
$password = 'xpbsB6RxB6bhATjG';
$prefix = 'sn_';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "数据库连接成功！\n";
    
    // SMTP 配置
    $smtpConfig = [
        'host' => 'mail15.serv00.com',
        'port' => '465',
        'username' => 'fazyaldzvh@fazyaldzvh.serv00.net',
        'password' => '0Y0dkjuLF(*#k5(ZhOu)',
        'smtpsecure' => 'ssl',  // 端口 465 需要使用 SSL
        'fromname' => '星夜阁',
        'systememail' => 'fazyaldzvh@fazyaldzvh.serv00.net',
        'charset' => 'utf-8'
    ];
    
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
