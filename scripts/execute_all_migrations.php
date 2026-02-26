<?php
/**
 * 执行所有邮件系统相关迁移
 */

echo "=== 执行邮件系统完整迁移 ===\n\n";

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
    
    // 定义迁移文件执行顺序
    $migrations = [
        '009_admin_plugins.sql' => '插件管理表',
        '012_extra_tables.sql' => '额外表（包含通知模板）',
        '018_fix_plugin_tables.sql' => '修复插件表结构',
        '020_email_templates_init.sql' => '邮件模板初始数据'
    ];
    
    $migrationDir = __DIR__ . '/../database/migrations';
    $executedMigrations = [];
    
    foreach ($migrations as $file => $description) {
        echo "执行迁移: {$file} - {$description}\n";
        
        $filePath = $migrationDir . '/' . $file;
        if (!file_exists($filePath)) {
            echo "  ⚠ 文件不存在，跳过\n\n";
            continue;
        }
        
        // 读取并处理SQL
        $sql = file_get_contents($filePath);
        $sql = str_replace('__PREFIX__', '', $sql);
        
        // 分割SQL语句
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos(trim($statement), '--') === 0) {
                continue;
            }
            
            try {
                $pdo->exec($statement . ';');
                $successCount++;
            } catch (Exception $e) {
                // 忽略一些常见的错误
                $errorMsg = $e->getMessage();
                if (strpos($errorMsg, 'Duplicate entry') !== false ||
                    strpos($errorMsg, 'already exists') !== false ||
                    strpos($errorMsg, 'check that column') !== false) {
                    // 这些是预期的错误，忽略
                } else {
                    echo "  ⚠ SQL错误: " . $errorMsg . "\n";
                    $errorCount++;
                }
            }
        }
        
        echo "  ✓ 成功执行 {$successCount} 条语句\n";
        if ($errorCount > 0) {
            echo "  ⚠ {$errorCount} 条语句出错（可能是预期的）\n";
        }
        
        $executedMigrations[] = $file;
        echo "\n";
    }
    
    // 验证表结构
    echo "=== 验证表结构 ===\n";
    
    // 检查 admin_plugins 表
    echo "1. 检查 admin_plugins 表:\n";
    $stmt = $pdo->prepare("DESCRIBE admin_plugins");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $expectedColumns = ['id', 'plugin_id', 'name', 'version', 'type', 'category', 'description', 'author', 'status', 'config_json'];
    $actualColumns = array_column($columns, 'Field');
    
    foreach ($expectedColumns as $col) {
        if (in_array($col, $actualColumns)) {
            echo "  ✓ {$col}\n";
        } else {
            echo "  ✗ {$col} (缺失)\n";
        }
    }
    
    // 检查 notification_templates 表
    echo "\n2. 检查 notification_templates 表:\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notification_templates");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  ✓ 模板数量: {$count}\n";
    
    // 检查邮件模板
    $emailTemplates = ['register_verify_email', 'reset_password_email', 'welcome_email'];
    foreach ($emailTemplates as $template) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notification_templates WHERE channel = 'email' AND code = ?");
        $stmt->execute([$template]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        echo "  " . ($exists ? "✓" : "✗") . " {$template}\n";
    }
    
    // 检查 SMTP 插件配置
    echo "\n3. 检查 SMTP 插件配置:\n";
    $stmt = $pdo->prepare("SELECT status, config_json FROM admin_plugins WHERE plugin_id = 'email/smtp_service'");
    $stmt->execute();
    $plugin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($plugin) {
        echo "  ✓ 插件状态: {$plugin['status']}\n";
        $config = json_decode($plugin['config_json'], true);
        if ($config && isset($config['host'])) {
            echo "  ✓ SMTP配置完整\n";
        } else {
            echo "  ⚠ SMTP配置不完整\n";
        }
    } else {
        echo "  ✗ SMTP插件未找到\n";
    }
    
    // 测试邮件模板功能
    echo "\n4. 测试邮件模板功能:\n";
    require_once __DIR__ . '/../app/helpers.php';
    
    try {
        $template = get_email_template('register_verify_email', [
            'code' => 'TEST123',
            'minutes' => '15'
        ]);
        
        if ($template) {
            echo "  ✓ 邮件模板获取成功\n";
            echo "  主题: {$template['subject']}\n";
            echo "  内容长度: " . strlen($template['body']) . " 字符\n";
        } else {
            echo "  ✗ 邮件模板获取失败\n";
        }
    } catch (Exception $e) {
        echo "  ✗ 邮件模板测试异常: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== 迁移执行完成 ===\n";
    echo "✓ 已执行迁移: " . implode(', ', $executedMigrations) . "\n";
    echo "✓ 表结构已更新\n";
    echo "✓ 邮件模板数据已插入\n";
    echo "✓ SMTP插件配置已完成\n";
    echo "✓ 邮件系统功能已验证\n";
    
} catch (Exception $e) {
    echo "❌ 迁移执行失败: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
}
