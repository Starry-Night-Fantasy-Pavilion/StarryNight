<?php

// 警告：这个脚本用于从“当前数据库结构”恢复丢失的迁移 SQL 文件。
// 它不会删除任何表或数据，只是读取现有表结构并写回到 migration 文件中。

require_once __DIR__ . '/../vendor/autoload.php';

use app\services\Database;

echo "Restoring deleted migration files from current database schema...\n";

try {
    $pdo = Database::pdo();
    $prefix = Database::prefix();

    // 获取所有以前缀开头的表
    $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($prefix . '%'));
    $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    if (empty($tables)) {
        throw new \RuntimeException("No tables found with prefix {$prefix}, nothing to restore.");
    }

    $ddl = "-- Restored core schema from current database\n";
    $ddl .= "-- Generated at " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $row = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
        if (!isset($row['Create Table'])) {
            continue;
        }
        $ddl .= $row['Create Table'] . ";\n\n";
    }

    $migrationsDir = realpath(__DIR__ . '/../database/migrations');
    if ($migrationsDir === false) {
        throw new \RuntimeException("Migrations directory not found.");
    }

    // 恢复 001_core_system.sql，写入当前完整结构
    $targetFile = $migrationsDir . DIRECTORY_SEPARATOR . '001_core_system.sql';
    file_put_contents($targetFile, $ddl);
    echo "Written restored core schema to {$targetFile}\n";

    // 确保 002-006 至少存在占位文件，避免安装向导报“缺文件”
    $placeholders = [
        '002_api_and_channels.sql',
        '003_ai_music_system.sql',
        '004_membership_payment.sql',
        '005_advanced_features.sql',
        '006_novel_creation_and_community.sql',
    ];

    foreach ($placeholders as $fname) {
        $path = $migrationsDir . DIRECTORY_SEPARATOR . $fname;
        if (!file_exists($path)) {
            file_put_contents(
                $path,
                "-- Placeholder restored migration: {$fname}\n-- Original file was missing; core schema is in 001_core_system.sql\n"
            );
            echo "Created placeholder {$path}\n";
        }
    }

    echo "Restore completed.\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

