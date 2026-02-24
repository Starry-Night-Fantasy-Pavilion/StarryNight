<?php
/**
 * 清除 OPcache 并测试插件加载
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>清除 OPcache 并测试插件加载</h1>";

// 清除 OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p style='color:green;'>✓ OPcache 已清除</p>";
} else {
    echo "<p style='color:orange;'>⚠ OPcache 未启用</p>";
}

// 显示 OPcache 状态
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    if ($status && $status['opcache_enabled']) {
        echo "<h2>OPcache 状态</h2>";
        echo "<ul>";
        echo "<li>状态: 启用</li>";
        echo "<li>内存使用: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB</li>";
        echo "<li>缓存脚本数: " . $status['opcache_statistics']['num_cached_scripts'] . "</li>";
        echo "</ul>";
    }
}

// 清除文件状态缓存
clearstatcache();
echo "<p style='color:green;'>✓ 文件状态缓存已清除</p>";

// 测试自动加载器
echo "<h2>测试自动加载器</h2>";
require_once __DIR__ . '/../vendor/autoload.php';

$classes = [
    'Core\EmailPlugin',
    'Core\Plugin',
    'Core\VerificationPlugin',
    'Core\SMSPlugin',
    'Core\GatewayPlugin',
];

echo "<ul>";
foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "<li style='color:green;'>✓ $class</li>";
    } else {
        echo "<li style='color:red;'>✗ $class</li>";
    }
}
echo "</ul>";

// 测试插件目录
echo "<h2>测试插件目录</h2>";
$pluginsDir = realpath(__DIR__ . '/plugins');
if ($pluginsDir && is_dir($pluginsDir)) {
    echo "<p style='color:green;'>✓ 插件目录存在: $pluginsDir</p>";
    
    // 查找 plugin.json 文件
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pluginsDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $count = 0;
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isFile() && $fileInfo->getFilename() === 'plugin.json') {
            $count++;
        }
    }
    echo "<p style='color:green;'>✓ 找到 $count 个插件配置文件</p>";
} else {
    echo "<p style='color:red;'>✗ 插件目录不存在</p>";
}

echo "<p><a href='/admin/plugins'>返回插件管理</a></p>";
