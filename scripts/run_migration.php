<?php
/**
 * 数据库迁移执行脚本
 * 
 * 用法: php scripts/run_migration.php [migration_file]
 * 如果不指定文件，将执行所有未执行的迁移
 */

require_once __DIR__ . '/../vendor/autoload.php';

use app\services\PluginAutomationService;

// 加载环境变量
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            // putenv("$key=$value");
        }
    }
}

try {
    $automationService = new PluginAutomationService();
    
    if (isset($argv[1])) {
        // 执行指定的迁移文件
        $migrationFile = $argv[1];
        $migrationPath = __DIR__ . '/../database/migrations/' . $migrationFile;
        
        if (!file_exists($migrationPath)) {
            echo "错误: 迁移文件不存在: {$migrationFile}\n";
            exit(1);
        }
        
        echo "执行迁移: {$migrationFile}\n";
        // 这里可以添加单独执行某个迁移的逻辑
        // 目前使用 runMigrations 会执行所有未执行的迁移
        $result = $automationService->runMigrations();
    } else {
        // 执行所有未执行的迁移
        echo "执行所有未执行的数据库迁移...\n";
        $result = $automationService->runMigrations();
    }
    
    if ($result['success']) {
        echo "✓ " . $result['message'] . "\n";
        if (isset($result['results'])) {
            foreach ($result['results'] as $file => $fileResult) {
                if ($fileResult['success']) {
                    echo "  ✓ {$file}\n";
                } else {
                    echo "  ✗ {$file}: " . ($fileResult['message'] ?? '失败') . "\n";
                }
            }
        }
        exit(0);
    } else {
        echo "✗ " . $result['message'] . "\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
