<?php
/**
 * 检查邮件插件配置
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
    $stmt = $pdo->prepare('SELECT plugin_id, status, config_json FROM admin_plugins WHERE plugin_id LIKE "email/%"');
    $stmt->execute();
    $plugins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Email plugins found:\n";
    echo "Total: " . count($plugins) . "\n";
    echo "========================\n";
    foreach ($plugins as $plugin) {
        echo "- " . $plugin['plugin_id'] . " (status: " . $plugin['status'] . ")\n";
        if (!empty($plugin['config_json'])) {
            $config = json_decode($plugin['config_json'], true);
            if ($config && isset($config['host'])) {
                echo "  Host: " . $config['host'] . "\n";
                echo "  Port: " . $config['port'] . "\n";
                echo "  Username: " . $config['username'] . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
