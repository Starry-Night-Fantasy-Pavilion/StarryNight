<?php

namespace app\services;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;
    private static ?array $envCache = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        // 从 .env / ENV_SETTINGS 中读取数据库配置，移除硬编码
        $host = (string) self::env('DB_HOST', '127.0.0.1');
        $port = (int) self::env('DB_PORT', 3306);
        $dbName = (string) self::env('DB_DATABASE', '52222');
        $username = (string) self::env('DB_USERNAME', '52222');
        $password = (string) self::env('DB_PASSWORD', '');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $dbName
        );

        try {
            self::$pdo = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw $e;
        }

        return self::$pdo;
    }

    public static function prefix(): string
    {
        // 优先从配置中读取前缀，默认为 sn_
        return (string) self::env('DB_PREFIX', 'sn_');
    }

    /**
     * 统一的环境变量读取逻辑：
     * - 如果全局 helper get_env 存在，则优先使用
     * - 否则在此处懒加载项目根目录的 .env 文件到静态缓存中
     */
    private static function env(string $key, mixed $default = null): mixed
    {
        if (function_exists('get_env')) {
            return get_env($key, $default);
        }

        if (self::$envCache === null) {
            self::$envCache = [];
            $root = dirname(__DIR__, 2); // app/services -> project root
            $envFile = $root . DIRECTORY_SEPARATOR . '.env';
            if (file_exists($envFile) && is_readable($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#') === true) {
                        continue;
                    }
                    if (!str_contains($line, '=')) {
                        continue;
                    }
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    if ($value !== '' && $value[0] === '"' && substr($value, -1) === '"') {
                        $value = substr($value, 1, -1);
                    } elseif ($value !== '' && $value[0] === "'" && substr($value, -1) === "'") {
                        $value = substr($value, 1, -1);
                    }
                    if ($name !== '') {
                        self::$envCache[$name] = $value;
                    }
                }
            }
        }

        if (array_key_exists($key, self::$envCache)) {
            return self::$envCache[$key];
        }

        return $default;
    }

    /**
     * 执行SQL查询并返回所有结果
     */
    public static function queryAll($sql, $params = [])
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * 执行SQL查询并返回单行结果
     */
    public static function queryOne($sql, $params = [])
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * 执行SQL语句（无返回结果）
     */
    public static function execute($sql, $params = [])
    {
        $stmt = self::pdo()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 插入数据并返回插入ID
     */
    public static function insert($table, $data)
    {
        $prefix = self::prefix();
        $table = $prefix . $table;
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute(array_values($data));
        
        return self::pdo()->lastInsertId();
    }

    /**
     * 更新数据
     */
    public static function update($table, $data, $where, $whereParams = [])
    {
        $prefix = self::prefix();
        $table = $prefix . $table;
        
        $setParts = [];
        foreach ($data as $column => $value) {
            $setParts[] = "`{$column}` = ?";
        }
        
        $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $stmt = self::pdo()->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * 删除数据
     */
    public static function delete($table, $where, $params = [])
    {
        $prefix = self::prefix();
        $table = $prefix . $table;
        
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        
        $stmt = self::pdo()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 开始事务
     */
    public static function beginTransaction()
    {
        return self::pdo()->beginTransaction();
    }

    /**
     * 提交事务
     */
    public static function commit()
    {
        return self::pdo()->commit();
    }

    /**
     * 回滚事务
     */
    public static function rollback()
    {
        return self::pdo()->rollback();
    }

    /**
     * 统一错误处理
     */
    protected function handleError(\Exception $e, $operation = '') {
        $errorMessage = $operation ? $operation . '失败: ' . $e->getMessage() : $e->getMessage();
        
        // 记录错误日志
        error_log('Service Error: ' . $errorMessage);
        
        // 抛出自定义异常
        throw new \Exception($errorMessage, $e->getCode(), $e);
    }
}
