<?php
/**
 * 检查表结构详情
 */

echo "=== 关键表结构详情 ===\n\n";

$migrationDir = __DIR__ . '/../database/migrations';

// 1. 检查 admin_plugins 表
echo "1. admin_plugins 表结构 (来自 009_admin_plugins.sql):\n";
$adminPluginsFile = $migrationDir . '/009_admin_plugins.sql';
if (file_exists($adminPluginsFile)) {
    $content = file_get_contents($adminPluginsFile);
    echo "文件内容预览:\n";
    $lines = explode("\n", $content);
    $inCreate = false;
    foreach ($lines as $line) {
        if (strpos($line, 'CREATE TABLE') !== false) {
            $inCreate = true;
        }
        if ($inCreate) {
            echo "  " . trim($line) . "\n";
            if (strpos($line, ');') !== false) {
                break;
            }
        }
    }
}

echo "\n2. notification_templates 表结构 (来自 012_extra_tables.sql):\n";
$notificationFile = $migrationDir . '/012_extra_tables.sql';
if (file_exists($notificationFile)) {
    $content = file_get_contents($notificationFile);
    $lines = explode("\n", $content);
    $inCreate = false;
    foreach ($lines as $line) {
        if (strpos($line, 'CREATE TABLE') !== false && strpos($line, 'notification_templates') !== false) {
            $inCreate = true;
        }
        if ($inCreate) {
            echo "  " . trim($line) . "\n";
            if (strpos($line, ');') !== false) {
                break;
            }
        }
    }
}

echo "\n3. notification_templates 表结构 (来自 001_core_system.sql):\n";
$coreFile = $migrationDir . '/001_core_system.sql';
if (file_exists($coreFile)) {
    $content = file_get_contents($coreFile);
    $lines = explode("\n", $content);
    $inCreate = false;
    foreach ($lines as $line) {
        if (strpos($line, 'CREATE TABLE') !== false && strpos($line, 'notification_templates') !== false) {
            $inCreate = true;
        }
        if ($inCreate) {
            echo "  " . trim($line) . "\n";
            if (strpos($line, ');') !== false) {
                break;
            }
        }
    }
}

echo "\n=== 发现的问题 ===\n";

// 检查重复定义
echo "1. 表重复定义检查:\n";
$tables = [];

// 检查所有文件中的表定义
foreach (['001_core_system.sql', '009_admin_plugins.sql', '012_extra_tables.sql'] as $file) {
    $filePath = $migrationDir . '/' . $file;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        preg_match_all('/CREATE TABLE.*?`([^`]+)`/s', $content, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $table) {
                if (!isset($tables[$table])) {
                    $tables[$table] = [];
                }
                $tables[$table][] = $file;
            }
        }
    }
}

foreach ($tables as $table => $files) {
    if (count($files) > 1) {
        echo "  ⚠ 表 {$table} 在多个文件中定义:\n";
        foreach ($files as $file) {
            echo "    - {$file}\n";
        }
    }
}

// 检查前缀不一致
echo "\n2. 前缀使用检查:\n";
$prefixIssues = [];
foreach (['001_core_system.sql', '009_admin_plugins.sql', '012_extra_tables.sql'] as $file) {
    $filePath = $migrationDir . '/' . $file;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $hasPrefix = strpos($content, '__PREFIX__') !== false;
        $hasSnPrefix = strpos($content, 'sn_') !== false;
        
        if ($hasPrefix && $hasSnPrefix) {
            $prefixIssues[] = $file;
        }
    }
}

if (empty($prefixIssues)) {
    echo "  ✓ 前缀使用一致\n";
} else {
    foreach ($prefixIssues as $file) {
        echo "  ⚠ {$file}: 同时使用了 __PREFIX__ 和 sn_ 前缀\n";
    }
}

echo "\n=== 检查完成 ===\n";
