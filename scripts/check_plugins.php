<?php
/**
 * 检查第三方登录插件状态
 */

// 加载环境变量和数据库配置
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            $parts = explode('=', $line, 2);
            $key = trim($parts[0]);
            $value = isset($parts[1]) ? trim($parts[1], " \t\n\r\0\x0B'\"") : '';
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

// 数据库配置
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_DATABASE') ?: 'starrynight';
$dbUser = getenv('DB_USERNAME') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';
$dbPrefix = getenv('DB_PREFIX') ?: 'sn_';

echo "数据库配置:\n";
echo "  Host: {$dbHost}\n";
echo "  Database: {$dbName}\n";
echo "  User: {$dbUser}\n";
echo "  Prefix: {$dbPrefix}\n\n";

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $table = $dbPrefix . 'admin_plugins';
    
    echo "=== 检查第三方登录插件状态 ===\n\n";
    
    // 查询所有第三方登录插件
    $stmt = $pdo->query("SELECT plugin_id, name, type, status, installed_at FROM `{$table}` WHERE `type` = 'thirdparty_login'");
    $plugins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($plugins)) {
        echo "数据库中没有找到第三方登录插件记录\n";
        echo "尝试查询所有插件类型...\n\n";
        
        $stmt = $pdo->query("SELECT DISTINCT type FROM `{$table}`");
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "数据库中存在的插件类型: " . implode(', ', $types) . "\n\n";
        
        // 查询所有插件
        $stmt = $pdo->query("SELECT plugin_id, name, type, status FROM `{$table}` LIMIT 20");
        $allPlugins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "数据库中的插件列表:\n";
        foreach ($allPlugins as $p) {
            echo "  - {$p['plugin_id']} | {$p['name']} | type={$p['type']} | status={$p['status']}\n";
        }
    } else {
        echo "找到 " . count($plugins) . " 个第三方登录插件:\n";
        foreach ($plugins as $plugin) {
            echo "  - ID: {$plugin['plugin_id']}\n";
            echo "    名称: {$plugin['name']}\n";
            echo "    状态: {$plugin['status']}\n";
            echo "    安装时间: {$plugin['installed_at']}\n\n";
        }
    }
    
    // 检查插件文件
    echo "\n=== 检查插件文件 ===\n";
    $pluginsDir = realpath(__DIR__ . '/../public/plugins');
    echo "插件目录: {$pluginsDir}\n";
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pluginsDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $foundPlugins = [];
    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile() || $fileInfo->getFilename() !== 'plugin.json') {
            continue;
        }
        
        $configFile = $fileInfo->getPathname();
        $config = json_decode(file_get_contents($configFile), true);
        
        if (($config['type'] ?? '') === 'thirdparty_login') {
            $pluginPath = dirname($configFile);
            $relativePath = str_replace($pluginsDir . DIRECTORY_SEPARATOR, '', $pluginPath);
            
            echo "\n找到第三方登录插件:\n";
            echo "  - plugin_id: {$config['plugin_id']}\n";
            echo "  - 名称: {$config['name']}\n";
            echo "  - 路径: {$relativePath}\n";
            echo "  - 状态: " . ($config['status'] ?? 'N/A') . "\n";
            echo "  - 已安装: " . (($config['installed'] ?? false) ? '是' : '否') . "\n";
            
            // 检查 logo 文件
            $logoFiles = ['logo.png', 'logo.jpg', 'logo.svg'];
            foreach ($logoFiles as $logo) {
                $logoPath = $pluginPath . DIRECTORY_SEPARATOR . $logo;
                if (file_exists($logoPath)) {
                    echo "  - Logo: {$logo} (存在)\n";
                }
            }
            
            $foundPlugins[] = $config['plugin_id'];
        }
    }
    
    if (empty($foundPlugins)) {
        echo "\n没有找到第三方登录插件文件\n";
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
