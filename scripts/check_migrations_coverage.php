<?php

/**
 * 检查：
 * 1）代码中使用到的前缀表名（{$prefix}xxx）
 * 2）迁移 SQL 中声明的表（__PREFIX__xxx）
 * 对比两者，找出：
 *  - 代码用到但迁移中没有的表
 *  - 迁移里有但代码中没用到的表
 */

$root = dirname(__DIR__);

function collectMigrationTables(string $migrationsDir): array
{
    $tables = [];
    foreach (glob($migrationsDir . '/*.sql') as $file) {
        $sql = file_get_contents($file);
        if ($sql === false) {
            continue;
        }
        // 兼容 "CREATE TABLE `__PREFIX__xxx`" 和
        // "CREATE TABLE IF NOT EXISTS `__PREFIX__xxx`" 两种写法
        if (preg_match_all('/CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`__PREFIX__([a-zA-Z0-9_]+)`/i', $sql, $m)) {
            foreach ($m[1] as $name) {
                $tables[$name] = true;
            }
        }
    }
    ksort($tables);
    return array_keys($tables);
}

function collectCodeTables(string $codeDir): array
{
    $tables = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($codeDir, FilesystemIterator::SKIP_DOTS)
    );
    /** @var SplFileInfo $fi */
    foreach ($it as $fi) {
        if (!$fi->isFile()) continue;
        $ext = strtolower($fi->getExtension());
        if ($ext !== 'php') continue;
        $code = file_get_contents($fi->getPathname());
        if ($code === false) continue;

        // 匹配 "{$prefix}table_name" 或 "{$prefix}table-name" 这类形式
        if (preg_match_all('/`?\{\$prefix\}([a-zA-Z0-9_]+)`?/m', $code, $m)) {
            foreach ($m[1] as $name) {
                $tables[$name] = true;
            }
        }
        // 也有部分直接拼接前缀： $prefix . 'users'
        if (preg_match_all('/\$prefix\s*\.\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $code, $m2)) {
            foreach ($m2[1] as $name) {
                $tables[$name] = true;
            }
        }
    }
    ksort($tables);
    return array_keys($tables);
}

$migrationsDir = $root . '/database/migrations';
$codeDir = $root . '/app';

echo "Scanning migrations in: {$migrationsDir}\n";
$migrationTables = collectMigrationTables($migrationsDir);
echo "Found " . count($migrationTables) . " tables in migrations.\n\n";

echo "Scanning code in: {$codeDir}\n";
$codeTables = collectCodeTables($codeDir);
echo "Found " . count($codeTables) . " tables referenced in code.\n\n";

$migrationSet = array_flip($migrationTables);
$codeSet = array_flip($codeTables);

$missingInMigrations = array_values(array_diff($codeTables, $migrationTables));
$unusedInCode = array_values(array_diff($migrationTables, $codeTables));

echo "=== Tables used in code but NOT defined in migrations ===\n";
if (empty($missingInMigrations)) {
    echo "OK: None.\n";
} else {
    foreach ($missingInMigrations as $t) {
        echo " - {$t}\n";
    }
}

echo "\n=== Tables defined in migrations but not referenced in code ===\n";
if (empty($unusedInCode)) {
    echo "OK: None.\n";
} else {
    foreach ($unusedInCode as $t) {
        echo " - {$t}\n";
    }
}

