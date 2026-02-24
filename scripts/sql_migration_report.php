<?php
/**
 * SQL迁移文件检查报告
 */

echo "=== SQL迁移文件检查报告 ===\n\n";

$migrationDir = __DIR__ . '/../database/migrations';
$migrationFiles = [
    '001_core_system.sql',
    '007_admin_system.sql', 
    '008_settings_table.sql',
    '009_admin_plugins.sql',
    '010_ai_channels_and_logs.sql',
    '011_update_notice_bar_table.sql',
    '012_extra_tables.sql',
    '013_fix_compatibility.sql',
    '014_missing_core_tables.sql',
    '015_ai_agent_market.sql',
    '016_database_index_optimization.sql',
    '017_email_templates_init.sql'
];

$emailRelatedTables = [];
$pluginRelatedTables = [];

foreach ($migrationFiles as $file) {
    $filePath = $migrationDir . '/' . $file;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        echo "检查文件: {$file}\n";
        echo "文件大小: " . number_format(filesize($filePath)) . " 字节\n";
        
        // 检查邮件相关表
        if (strpos($content, 'notification_templates') !== false) {
            $emailRelatedTables[] = $file;
            echo "  ✓ 包含 notification_templates 表\n";
        }
        
        if (strpos($content, 'email') !== false) {
            echo "  ✓ 包含邮件相关内容\n";
        }
        
        // 检查插件相关表
        if (strpos($content, 'admin_plugins') !== false) {
            $pluginRelatedTables[] = $file;
            echo "  ✓ 包含 admin_plugins 表\n";
        }
        
        if (strpos($content, 'plugin') !== false) {
            echo "  ✓ 包含插件相关内容\n";
        }
        
        // 检查前缀使用
        if (strpos($content, '__PREFIX__') !== false) {
            echo "  ✓ 使用了表前缀 __PREFIX__\n";
        }
        
        echo "\n";
    }
}

echo "=== 邮件相关表文件 ===\n";
foreach ($emailRelatedTables as $file) {
    echo "- {$file}\n";
}

echo "\n=== 插件相关表文件 ===\n";
foreach ($pluginRelatedTables as $file) {
    echo "- {$file}\n";
}

echo "\n=== 关键表结构分析 ===\n";

// 分析 admin_plugins 表结构
echo "\n1. admin_plugins 表结构:\n";
$adminPluginsFile = $migrationDir . '/009_admin_plugins.sql';
if (file_exists($adminPluginsFile)) {
    $content = file_get_contents($adminPluginsFile);
    preg_match('/CREATE TABLE.*?admin_plugins.*?\);/s', $content, $matches);
    if (isset($matches[0])) {
        $createSql = $matches[0];
        // 移除前缀标记
        $createSql = str_replace('__PREFIX__', '', $createSql);
        $lines = explode("\n", $createSql);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '`') !== false && strpos($line, 'CREATE') === false && strpos($line, 'ENGINE') === false && !empty($line)) {
                echo "  {$line}\n";
            }
        }
    }
}

echo "\n2. notification_templates 表结构:\n";
$notificationFile = $migrationDir . '/012_extra_tables.sql';
if (file_exists($notificationFile)) {
    $content = file_get_contents($notificationFile);
    preg_match('/CREATE TABLE.*?notification_templates.*?\);/s', $content, $matches);
    if (isset($matches[0])) {
        $createSql = $matches[0];
        // 移除前缀标记
        $createSql = str_replace('__PREFIX__', '', $createSql);
        $lines = explode("\n", $createSql);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '`') !== false && strpos($line, 'CREATE') === false && strpos($line, 'ENGINE') === false && !empty($line)) {
                echo "  {$line}\n";
            }
        }
    }
}

echo "\n=== 建议的迁移执行顺序 ===\n";
echo "1. 001_core_system.sql - 核心系统表\n";
echo "2. 007_admin_system.sql - 管理员系统\n";
echo "3. 008_settings_table.sql - 设置表\n";
echo "4. 009_admin_plugins.sql - 插件管理表\n";
echo "5. 012_extra_tables.sql - 额外表（包含 notification_templates）\n";
echo "6. 017_email_templates_init.sql - 邮件模板初始数据\n";
echo "7. 其他迁移文件...\n";

echo "\n=== 潜在问题检查 ===\n";

// 检查表前缀一致性
echo "1. 表前缀使用检查:\n";
$prefixInconsistency = false;
foreach ($migrationFiles as $file) {
    $filePath = $migrationDir . '/' . $file;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        if (strpos($content, 'sn_') !== false && strpos($content, '__PREFIX__') !== false) {
            echo "  ⚠ {$file}: 同时使用了 sn_ 前缀和 __PREFIX__\n";
            $prefixInconsistency = true;
        }
    }
}
if (!$prefixInconsistency) {
    echo "  ✓ 表前缀使用一致\n";
}

echo "\n2. 重复表定义检查:\n";
$tableDefinitions = [];
foreach ($migrationFiles as $file) {
    $filePath = $migrationDir . '/' . $file;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        preg_match_all('/CREATE TABLE.*?`([^`]+)`.*?\);/s', $content, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $table) {
                if (!isset($tableDefinitions[$table])) {
                    $tableDefinitions[$table] = [];
                }
                $tableDefinitions[$table][] = $file;
            }
        }
    }
}

$duplicateTables = array_filter($tableDefinitions, function($files) {
    return count($files) > 1;
});

if (empty($duplicateTables)) {
    echo "  ✓ 没有重复的表定义\n";
} else {
    foreach ($duplicateTables as $table => $files) {
        echo "  ⚠ 表 {$table} 在多个文件中定义: " . implode(', ', $files) . "\n";
    }
}

echo "\n=== 检查完成 ===\n";
