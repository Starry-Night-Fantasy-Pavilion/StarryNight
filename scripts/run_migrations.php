<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use app\services\Database;

function out(string $message): void
{
    echo $message . PHP_EOL;
}

function normalizeStatements(string $sql): array
{
    // 简单分割：适配当前迁移文件风格（以分号结尾的DDL/DML）。
    // 若后续引入复杂存储过程，可替换为更完整的 SQL parser。
    $chunks = array_map('trim', explode(';', $sql));
    return array_values(array_filter($chunks, static fn(string $s): bool => $s !== ''));
}

try {
    out('Starting database migration...');

    $pdo = Database::pdo();
    $prefix = Database::prefix();

    $migrationsDir = realpath(__DIR__ . '/../database/migrations');
    if ($migrationsDir === false || !is_dir($migrationsDir)) {
        throw new RuntimeException('Migrations directory (database/migrations) not found or not readable.');
    }

    $sqlFiles = glob($migrationsDir . '/*.sql');
    if ($sqlFiles === false || $sqlFiles === []) {
        throw new RuntimeException('No SQL files found in the migrations directory.');
    }

    natsort($sqlFiles);
    $sqlFiles = array_values($sqlFiles);

    // 检查迁移编号冲突（如 017_xxx.sql / 017_yyy.sql）。
    $seenPrefixes = [];
    foreach ($sqlFiles as $filePath) {
        $name = basename($filePath);
        if (preg_match('/^(\d+)_/i', $name, $m) === 1) {
            $seq = ltrim($m[1], '0');
            $seq = $seq === '' ? '0' : $seq;
            if (isset($seenPrefixes[$seq])) {
                out(sprintf('[WARN] Duplicate migration sequence detected: %s (%s, %s)', $seq, $seenPrefixes[$seq], $name));
            } else {
                $seenPrefixes[$seq] = $name;
            }
        }
    }

    out('Found ' . count($sqlFiles) . ' migration files. Executing...');

    $fileCount = 0;
    $statementCount = 0;
    $warningCount = 0;

    foreach ($sqlFiles as $schemaFile) {
        $filename = basename($schemaFile);
        out(' - Executing: ' . $filename);

        if (!is_readable($schemaFile)) {
            throw new RuntimeException("Migration file ({$filename}) is not readable.");
        }

        $sql = file_get_contents($schemaFile);
        if ($sql === false) {
            throw new RuntimeException("Could not read migration file ({$filename}).");
        }

        $sql = str_replace('__PREFIX__', $prefix, $sql);
        $statements = normalizeStatements($sql);

        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
                $statementCount++;
            } catch (PDOException $e) {
                $warningCount++;
                out('   - Warning in ' . $filename . ': ' . $e->getMessage());
            }
        }

        $fileCount++;
    }

    out('');
    out('Database migration completed.');
    out(sprintf('Summary: files=%d, statements=%d, warnings=%d', $fileCount, $statementCount, $warningCount));
} catch (Throwable $e) {
    out('');
    out('AN ERROR OCCURRED:');
    out($e->getMessage());
    exit(1);
}
