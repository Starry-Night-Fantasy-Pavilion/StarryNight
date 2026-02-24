<?php

declare(strict_types=1);

namespace Core\Orm;

use Core\Services\DatabaseService;
use PDO;

/**
 * 模型适配器基类
 * 提供向后兼容的静态方法，同时支持依赖注入
 * 
 * @package Core\Orm
 * @version 2.0.0
 */
abstract class ModelAdapter extends Model
{
    /**
     * 数据库服务实例缓存
     */
    protected static ?DatabaseService $dbService = null;

    /**
     * 设置数据库服务
     *
     * @param DatabaseService $service 数据库服务
     * @return void
     */
    public static function setDatabaseService(DatabaseService $service): void
    {
        static::$dbService = $service;
    }

    /**
     * 获取数据库服务
     *
     * @return DatabaseService
     */
    public static function getDatabaseService(): DatabaseService
    {
        if (static::$dbService === null) {
            static::$dbService = DatabaseService::fromEnvironment();
        }

        return static::$dbService;
    }

    /**
     * 获取PDO连接
     *
     * @return PDO
     */
    protected static function getPdo(): PDO
    {
        return static::getDatabaseService()->getPdo();
    }

    /**
     * 获取表前缀
     *
     * @return string
     */
    protected static function getPrefix(): string
    {
        return static::getDatabaseService()->getPrefix();
    }

    /**
     * 获取带前缀的表名
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return static::getDatabaseService()->tableName((new static())->getTable());
    }

    /**
     * 执行查询并返回所有结果
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array
     */
    protected static function queryAll(string $sql, array $params = []): array
    {
        return static::getDatabaseService()->queryAll($sql, $params);
    }

    /**
     * 执行查询并返回单行结果
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array|null
     */
    protected static function queryOne(string $sql, array $params = []): ?array
    {
        return static::getDatabaseService()->queryOne($sql, $params);
    }

    /**
     * 执行SQL语句
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return bool
     */
    protected static function execute(string $sql, array $params = []): bool
    {
        return static::getDatabaseService()->execute($sql, $params);
    }

    /**
     * 开始事务
     *
     * @return bool
     */
    protected static function beginTransaction(): bool
    {
        return static::getDatabaseService()->beginTransaction();
    }

    /**
     * 提交事务
     *
     * @return bool
     */
    protected static function commit(): bool
    {
        return static::getDatabaseService()->commit();
    }

    /**
     * 回滚事务
     *
     * @return bool
     */
    protected static function rollback(): bool
    {
        return static::getDatabaseService()->rollback();
    }

    /**
     * 执行事务回调
     *
     * @param callable $callback 回调函数
     * @return mixed
     * @throws \Exception
     */
    protected static function transaction(callable $callback): mixed
    {
        return static::getDatabaseService()->transaction($callback);
    }

    /**
     * 根据条件查找单条记录
     *
     * @param array $conditions 条件
     * @return static|null
     */
    public static function findBy(array $conditions): ?static
    {
        $query = static::newQuery();

        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }

    /**
     * 根据条件查找所有记录
     *
     * @param array $conditions 条件
     * @return array
     */
    public static function findAllBy(array $conditions): array
    {
        $query = static::newQuery();

        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }

        return $query->get();
    }

    /**
     * 分页查询
     *
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @param array $conditions 条件
     * @param string $orderBy 排序字段
     * @param string $orderDir 排序方向
     * @return array
     */
    public static function paginate(int $page = 1, int $perPage = 15, array $conditions = [], string $orderBy = 'id', string $orderDir = 'desc'): array
    {
        $query = static::newQuery();

        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }

        // 获取总数
        $totalQuery = clone $query;
        $total = $totalQuery->count();

        // 分页获取数据
        $offset = ($page - 1) * $perPage;
        $items = $query->orderBy($orderBy, $orderDir)
            ->limit($perPage)
            ->offset($offset)
            ->get();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
        ];
    }

    /**
     * 批量更新
     *
     * @param array $ids ID列表
     * @param array $data 更新数据
     * @return int 影响行数
     */
    public static function batchUpdate(array $ids, array $data): int
    {
        if (empty($ids)) {
            return 0;
        }

        $instance = new static();
        $table = static::getDatabaseService()->tableName($instance->getTable());
        $primaryKey = $instance->getPrimaryKey();

        $setParts = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setParts[] = "`{$key}` = :{$key}";
            $params[":{$key}"] = $value;
        }

        $placeholders = [];
        foreach ($ids as $index => $id) {
            $placeholders[] = ":id_{$index}";
            $params[":id_{$index}"] = $id;
        }

        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE `%s` IN (%s)",
            $table,
            implode(', ', $setParts),
            $primaryKey,
            implode(', ', $placeholders)
        );

        $stmt = static::getPdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * 批量删除
     *
     * @param array $ids ID列表
     * @return int 影响行数
     */
    public static function batchDelete(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $instance = new static();
        $table = static::getDatabaseService()->tableName($instance->getTable());
        $primaryKey = $instance->getPrimaryKey();

        $placeholders = [];
        $params = [];

        foreach ($ids as $index => $id) {
            $placeholders[] = ":id_{$index}";
            $params[":id_{$index}"] = $id;
        }

        $sql = sprintf(
            "DELETE FROM `%s` WHERE `%s` IN (%s)",
            $table,
            $primaryKey,
            implode(', ', $placeholders)
        );

        $stmt = static::getPdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * 转换为数组（支持嵌套模型）
     *
     * @param bool $hideSensitive 是否隐藏敏感字段
     * @return array
     */
    public function toArray(bool $hideSensitive = true): array
    {
        $attributes = parent::toArray();

        if ($hideSensitive) {
            // 移除敏感字段
            $sensitiveFields = ['password', 'remember_token', 'api_key', 'secret'];
            foreach ($sensitiveFields as $field) {
                unset($attributes[$field]);
            }
        }

        return $attributes;
    }

    /**
     * 刷新模型数据
     *
     * @return static|null
     */
    public function refresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }

        $fresh = static::find($this->getKey());

        if ($fresh !== null) {
            $this->attributes = $fresh->attributes;
            $this->original = $fresh->original;
        }

        return $this;
    }

    /**
     * 克隆模型（不含主键）
     *
     * @return static
     */
    public function replicate(): static
    {
        $model = new static();
        $model->fill($this->attributes);
        $model->setAttribute($this->primaryKey, null);
        $model->exists = false;

        return $model;
    }
}
