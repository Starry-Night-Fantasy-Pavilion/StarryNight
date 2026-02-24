<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/helpers.php';

use app\services\Database;

try {
    $pdo = Database::pdo();
    $prefix = Database::prefix();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

    echo "Current tables:\n";
    foreach ($tables as $t) {
        echo " - {$t}\n";
    }

    foreach (['ai_channels', 'notice_bar'] as $name) {
        $full = $prefix . $name;
        if (!in_array($full, $tables, true)) {
            echo "MISSING TABLE: {$full}\n";
        }
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

