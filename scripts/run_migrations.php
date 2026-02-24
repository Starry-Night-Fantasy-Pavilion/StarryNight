<?php
// scripts/run_migrations.php

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

echo "Starting database migration...
";

try {
    $pdo = Database::pdo();
    $prefix = Database::prefix();

    $migrations_dir = realpath(__DIR__ . '/../database/migrations');
    if (!$migrations_dir || !is_dir($migrations_dir)) {
        throw new \Exception('Error: Migrations directory (database/migrations) not found or not readable.');
    }

    $sql_files = glob($migrations_dir . '/*.sql');
    if (empty($sql_files)) {
        throw new \Exception('Error: No SQL files found in the migrations directory.');
    }

    // Sort files numerically to ensure correct order
    natsort($sql_files);

    echo "Found " . count($sql_files) . " migration files. Executing...
";

    foreach ($sql_files as $schema_file) {
        $filename = basename($schema_file);
        echo " - Executing: {$filename}
";

        if (!is_readable($schema_file)) {
            throw new \Exception("Error: Migration file ({$filename}) is not readable.");
        }

        $sql = file_get_contents($schema_file);
        if ($sql === false) {
            throw new \Exception("Error: Could not read migration file ({$filename}).");
        }

        $sql = str_replace('__PREFIX__', $prefix, $sql);
        
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if ($statement) {
                try {
                    $pdo->exec($statement);
                } catch (\PDOException $e) {
                    echo "   - Warning in {$filename}: " . $e->getMessage() . "
";
                }
            }
        }
    }

    echo "
Database migration completed successfully!
";

} catch (\Exception $e) {
    echo "
AN ERROR OCCURRED:
";
    echo $e->getMessage() . "
";
    exit(1);
}
