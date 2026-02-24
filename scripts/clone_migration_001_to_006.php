<?php

// 将 001_core_system.sql 的完整内容克隆到 002~006，作为完整迁移脚本使用

require_once __DIR__ . '/../vendor/autoload.php';

$migrationsDir = realpath(__DIR__ . '/../database/migrations');
if ($migrationsDir === false) {
    echo "ERROR: migrations directory not found.\n";
    exit(1);
}

$source = $migrationsDir . DIRECTORY_SEPARATOR . '001_core_system.sql';
if (!file_exists($source)) {
    echo "ERROR: source migration 001_core_system.sql not found.\n";
    exit(1);
}

$content = file_get_contents($source);
if ($content === false) {
    echo "ERROR: failed to read 001_core_system.sql.\n";
    exit(1);
}

$targets = [
    '002_api_and_channels.sql',
    '003_ai_music_system.sql',
    '004_membership_payment.sql',
    '005_advanced_features.sql',
    '006_novel_creation_and_community.sql',
];

foreach ($targets as $file) {
    $path = $migrationsDir . DIRECTORY_SEPARATOR . $file;
    file_put_contents($path, $content);
    echo "Cloned 001_core_system.sql to {$file}\n";
}

echo "Clone complete.\n";

