<?php

declare(strict_types=1);

namespace Core\Services;

use PDO;
use PDOException;
use Psr\Container\ContainerInterface;

/**
 * 数据库服务类
 * 实现PSR-11容器接口，支持依赖注入
 * 
 * @package Core\Services
 * @version 2.0.0
 */
class DatabaseService implements ContainerInterface
{
    /**
     * PDO连接实例
     */
    private ?PDO $pdo = null;

    /**
     * 数据库配置
     */
    private array $config;

    /**
     * 表前缀
     */
    private string $prefix;

    /**
     * 是否在事务中
     */
    private bool $inTransaction = false;

    /**
     * 构造函数
     *
     * @param array $config 数据库配置
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => '',
            'username' => 'root',
            'password' => '',
            'prefix' => 'sn_',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ], $config);

        $this->prefix = $this->config['prefix'];
    }

    /**
     * 从环境变量创建实例
     *
     * @return static
     */
    public static function fromEnvironment(): static
    {
        $config = [
            'host' => self::env('DB_HOST', '127.0.0.1'),
            'port' => (int) self::env('DB_PORT', 3306),
            'database' => self::env('DB_DATABASE', ''),
            'username' => self::env('DB_USERNAME', 'root'),
            'password' => self::env('DB_PASSWORD', ''),
            'prefix' => self::env('DB_PREFIX', 'sn_'),
        ];

        return new static($config);
    }

    /**
     * 获取PDO连接
     *
     * @return PDO
     * @throws PDOException
     */
    public function getPdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw $e;
        }

        return $this->pdo;
    }

    /**
     * 获取表前缀
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * 获取带前缀的表名
     *
     * @param string $table 表名
     * @return string
     */
    public function tableName(string $table): string
    {
        return $this->prefix . $table;
    }

    /**
     * 执行查询并返回所有结果
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array
     */
    public function queryAll(string $sql, array $params = []): array
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * 执行查询并返回单行结果
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array|null
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * 执行SQL语句
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return bool
     */
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->getPdo()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * 插入数据并返回插入ID
     *
     * @param string $table 表名（不含前缀）
     * @param array $data 数据
     * @return int|string
     */
    public function insert(string $table, array $data): int|string
    {
        $tableName = $this->tableName($table);
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            "INSERT INTO `%s` (`%s`) VALUES (%s)",
            $tableName,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->getPdo()->lastInsertId();
    }

    /**
     * 更新数据
     *
     * @param string $table 表名（不含前缀）
     * @param array $data 更新数据
     * @param string $where WHERE条件
     * @param array $whereParams WHERE参数
     * @return int 影响行数
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $tableName = $this->tableName($table);
        $setParts = [];

        foreach ($data as $column => $value) {
            $setParts[] = "`{$column}` = ?";
        }

        $sql = sprintf("UPDATE `%s` SET %s WHERE %s", $tableName, implode(', ', $setParts), $where);

        $params = array_merge(array_values($data), $whereParams);
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * 删除数据
     *
     * @param string $table 表名（不含前缀）
     * @param string $where WHERE条件
     * @param array $params 参数
     * @return int 影响行数
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $tableName = $this->tableName($table);
        $sql = sprintf("DELETE FROM `%s` WHERE %s", $tableName, $where);

        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * 开始事务
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        if ($this->inTransaction) {
            return false;
        }

        $result = $this->getPdo()->beginTransaction();
        $this->inTransaction = true;
        return $result;
    }

    /**
     * 提交事务
     *
     * @return bool
     */
    public function commit(): bool
    {
        if (!$this->inTransaction) {
            return false;
        }

        $result = $this->getPdo()->commit();
        $this->inTransaction = false;
        return $result;
    }

    /**
     * 回滚事务
     *
     * @return bool
     */
    public function rollback(): bool
    {
        if (!$this->inTransaction) {
            return false;
        }

        $result = $this->getPdo()->rollBack();
        $this->inTransaction = false;
        return $result;
    }

    /**
     * 执行事务回调
     *
     * @param callable $callback 回调函数
     * @return mixed
     * @throws \Exception
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 检查是否在事务中
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    /**
     * 获取最后插入ID
     *
     * @return int|string
     */
    public function lastInsertId(): int|string
    {
        return $this->getPdo()->lastInsertId();
    }

    /**
     * 引用表名
     *
     * @param string $table 表名
     * @return string
     */
    public function quoteTable(string $table): string
    {
        return '`' . $this->tableName($table) . '`';
    }

    /**
     * 引用值
     *
     * @param mixed $value 值
     * @return string
     */
    public function quote(mixed $value): string
    {
        return $this->getPdo()->quote($value);
    }

    /**
     * PSR-11: 检查容器中是否有条目
     *
     * @param string $id 条目ID
     * @return bool
     */
    public function has(string $id): bool
    {
        return $id === 'pdo' || $id === 'database';
    }

    /**
     * PSR-11: 从容器中获取条目
     *
     * @param string $id 条目ID
     * @return mixed
     */
    public function get(string $id): mixed
    {
        if ($id === 'pdo') {
            return $this->getPdo();
        }

        if ($id === 'database') {
            return $this;
        }

        throw new \RuntimeException("Service not found: {$id}");
    }

    /**
     * 关闭连接
     *
     * @return void
     */
    public function close(): void
    {
        $this->pdo = null;
        $this->inTransaction = false;
    }

    /**
     * 读取环境变量
     *
     * @param string $key 键名
     * @param mixed $default 默认值
     * @return mixed
     */
    private static function env(string $key, mixed $default = null): mixed
    {
        if (function_exists('get_env')) {
            return get_env($key, $default);
        }

        // 从$_ENV或$_SERVER中获取
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return $default;
    }

    /**
     * 魔术方法：调用PDO方法
     *
     * @param string $method 方法名
     * @param array $arguments 参数
     * @return mixed
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->getPdo()->$method(...$arguments);
    }
}
