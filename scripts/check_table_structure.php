<?php
/**
 * 检查数据库表结构
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
    
    echo "检查 admin_plugins 表结构:\n";
    $stmt = $pdo->prepare("DESCRIBE admin_plugins");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n检查 notification_templates 表是否存在:\n";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'notification_templates'");
    $stmt->execute();
    $exists = $stmt->rowCount() > 0;
    echo "存在: " . ($exists ? "是" : "否") . "\n";
    
    if ($exists) {
        echo "\nnotification_templates 表结构:\n";
        $stmt = $pdo->prepare("DESCRIBE notification_templates");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
