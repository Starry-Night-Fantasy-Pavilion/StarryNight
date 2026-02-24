<?php
// scripts/reset_database.php

require_once __DIR__ . '/../vendor/autoload.php';

use app\services\Database;

// Simple autoloader for app classes
spl_autoload_register(function ($class) {
    // Correctly escape the backslash
    $file = __DIR__ . '/../' . str_replace('', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

echo "Attempting to reset the database...
";

try {
    $pdo = Database::pdo();
    
    // Disable foreign key checks to avoid order issues
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "Database is already empty. No tables to drop.
";
    } else {
        echo "Dropping the following tables:
";
        foreach ($tables as $table) {
            echo " - " . $table . "
";
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        echo "All tables dropped.
";
    }

    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');

    echo "
Database reset successfully!
";

} catch (\Exception $e) {
    echo "
AN ERROR OCCURRED during database reset:
";
    echo $e->getMessage() . "
";
    // Re-enable foreign key checks even if it fails
    if (isset($pdo)) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    }
    exit(1);
}
