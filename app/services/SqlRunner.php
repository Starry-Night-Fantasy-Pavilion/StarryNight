<?php
namespace app\services;

use PDO;

class SqlRunner
{
    public static function runSqlFile(PDO $pdo, string $filePath, string $prefix): void
    {
        if (!is_readable($filePath)) {
            throw new \RuntimeException('SQL 文件不存在或不可读: ' . $filePath);
        }

        $sql = (string)file_get_contents($filePath);
        $sql = str_replace('__PREFIX__', $prefix, $sql);

        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }
}

