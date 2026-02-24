<?php

declare(strict_types=1);

namespace Core\Orm;

use app\services\Database;
use PDO;

/**
 * Repository基类
 * 提供数据仓库的通用实现
 */
abstract class Repository implements RepositoryInterface
{
    /**
     * 模型类名
     */
    protected string $model;

    /**
     * 表名
     */
    protected string $table;

    /**
     * 主键名
     */
    protected string $primaryKey = 'id';

    /**
     * 缓存前缀
     */
    protected string $cachePrefix = '';

    /**
     * 缓存时间（秒）
     */
    protected int $cacheTtl = 3600;

    /**
     * 是否启用缓存
     */
    protected bool $cacheEnabled = false;

    /**
     * 构造函数
     */
    public function __construct()
    {
        if (isset($this->model) && class_exists($this->model)) {
            $modelInstance = new $this->model();
            $this->table = $modelInstance->getTable();
            $this->primaryKey = $modelInstance->getPrimaryKey();
        }
    }

    /**
     * 创建查询构建器
     *
     * @return QueryBuilder
     */
    protected function query(): QueryBuilder
    {
        $query = new QueryBuilder($this->table);
        if (isset($this->model)) {
            $query->setModel($this->model);
        }
        return $query;
    }

    /**
     * 根据主键查找
     *
     * @param int|string $id 主键值
     * @return array|object|null
     */
    public function find(int|string $id): array|object|null
    {
        $cacheKey = $this->getCacheKey('find', $id);

        if ($this->cacheEnabled && $cached = $this->getCache($cacheKey)) {
            return $cached;
        }

        $result = $this->query()->find($id, $this->primaryKey);

        if ($this->cacheEnabled && $result) {
            $this->setCache($cacheKey, $result);
        }

        return $result;
    }

    /**
     * 根据主键查找，不存在则抛出异常
     *
     * @param int|string $id 主键值
     * @return array|object
     * @throws \RuntimeException
     */
    public function findOrFail(int|string $id): array|object
    {
        $result = $this->find($id);

        if ($result === null) {
            throw new \RuntimeException("Record not found for ID: {$id}");
        }

        return $result;
    }

    /**
     * 查找所有记录
     *
     * @return array
     */
    public function all(): array
    {
        $cacheKey = $this->getCacheKey('all');

        if ($this->cacheEnabled && $cached = $this->getCache($cacheKey)) {
            return $cached;
        }

        $result = $this->query()->get();

        if ($this->cacheEnabled) {
            $this->setCache($cacheKey, $result);
        }

        return $result;
    }

    /**
     * 根据条件查找
     *
     * @param array $conditions 条件
     * @return array
     */
    public function findBy(array $conditions): array
    {
        $query = $this->query();

        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->get();
    }

    /**
     * 根据条件查找第一条
     *
     * @param array $conditions 条件
     * @return array|object|null
     */
    public function findFirstBy(array $conditions): array|object|null
    {
        $query = $this->query();

        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    /**
     * 创建记录
     *
     * @param array $data 数据
     * @return array|object|null
     */
    public function create(array $data): array|object|null
    {
        $id = $this->query()->insert($data);

        if ($id) {
            $this->clearCache();
            return $this->find($id);
        }

        return null;
    }

    /**
     * 更新记录
     *
     * @param int|string $id 主键值
     * @param array $data 更新数据
     * @return bool
     */
    public function update(int|string $id, array $data): bool
    {
        $affected = $this->query()
            ->where($this->primaryKey, $id)
            ->update($data);

        if ($affected > 0) {
            $this->clearCache();
            return true;
        }

        return false;
    }

    /**
     * 删除记录
     *
     * @param int|string $id 主键值
     * @return bool
     */
    public function delete(int|string $id): bool
    {
        $affected = $this->query()
            ->where($this->primaryKey, $id)
            ->delete();

        if ($affected > 0) {
            $this->clearCache();
            return true;
        }

        return false;
    }

    /**
     * 分页查询
     *
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @param array $conditions 条件
     * @return array
     */
    public function paginate(int $page = 1, int $perPage = 15, array $conditions = []): array
    {
        $query = $this->query();

        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        // 获取总数
        $total = $query->clone()->count();

        // 获取分页数据
        $items = $query->forPage($page, $perPage)->get();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 0,
        ];
    }

    /**
     * 统计数量
     *
     * @param array $conditions 条件
     * @return int
     */
    public function count(array $conditions = []): int
    {
        $query = $this->query();

        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->count();
    }

    /**
     * 检查是否存在
     *
     * @param array $conditions 条件
     * @return bool
     */
    public function exists(array $conditions): bool
    {
        return $this->count($conditions) > 0;
    }

    /**
     * 开始事务
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        Database::beginTransaction();
    }

    /**
     * 提交事务
     *
     * @return void
     */
    public function commit(): void
    {
        Database::commit();
    }

    /**
     * 回滚事务
     *
     * @return void
     */
    public function rollback(): void
    {
        Database::rollback();
    }

    /**
     * 执行事务
     *
     * @param callable $callback 回调函数
     * @return mixed
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
     * 批量插入
     *
     * @param array $data 数据数组
     * @return bool
     */
    public function insertBatch(array $data): bool
    {
        $result = $this->query()->insertBatch($data);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * 批量更新
     *
     * @param array $ids ID数组
     * @param array $data 更新数据
     * @return int 影响行数
     */
    public function updateBatch(array $ids, array $data): int
    {
        $affected = $this->query()
            ->whereIn($this->primaryKey, $ids)
            ->update($data);

        if ($affected > 0) {
            $this->clearCache();
        }

        return $affected;
    }

    /**
     * 批量删除
     *
     * @param array $ids ID数组
     * @return int 影响行数
     */
    public function deleteBatch(array $ids): int
    {
        $affected = $this->query()
            ->whereIn($this->primaryKey, $ids)
            ->delete();

        if ($affected > 0) {
            $this->clearCache();
        }

        return $affected;
    }

    /**
     * 增加字段值
     *
     * @param int|string $id 主键值
     * @param string $column 列名
     * @param int|float $amount 增量
     * @return bool
     */
    public function increment(int|string $id, string $column, int|float $amount = 1): bool
    {
        $affected = $this->query()
            ->where($this->primaryKey, $id)
            ->increment($column, $amount);

        if ($affected > 0) {
            $this->clearCache();
            return true;
        }

        return false;
    }

    /**
     * 减少字段值
     *
     * @param int|string $id 主键值
     * @param string $column 列名
     * @param int|float $amount 减量
     * @return bool
     */
    public function decrement(int|string $id, string $column, int|float $amount = 1): bool
    {
        return $this->increment($id, $column, -$amount);
    }

    /**
     * 获取缓存键
     *
     * @param string $method 方法名
     * @param mixed ...$args 参数
     * @return string
     */
    protected function getCacheKey(string $method, mixed ...$args): string
    {
        $prefix = $this->cachePrefix ?: $this->table;
        return "{$prefix}:{$method}:" . md5(serialize($args));
    }

    /**
     * 获取缓存
     *
     * @param string $key 缓存键
     * @return mixed
     */
    protected function getCache(string $key): mixed
    {
        if (class_exists(\app\services\CacheService::class)) {
            return \app\services\CacheService::get($key);
        }
        return null;
    }

    /**
     * 设置缓存
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @return bool
     */
    protected function setCache(string $key, mixed $value): bool
    {
        if (class_exists(\app\services\CacheService::class)) {
            return \app\services\CacheService::set($key, $value, $this->cacheTtl);
        }
        return false;
    }

    /**
     * 清除缓存
     *
     * @return bool
     */
    protected function clearCache(): bool
    {
        if (class_exists(\app\services\CacheService::class)) {
            $prefix = $this->cachePrefix ?: $this->table;
            return \app\services\CacheService::deleteByPattern("{$prefix}:*") > 0;
        }
        return false;
    }

    /**
     * 启用缓存
     *
     * @param int $ttl 缓存时间
     * @return static
     */
    public function withCache(int $ttl = 3600): static
    {
        $this->cacheEnabled = true;
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * 禁用缓存
     *
     * @return static
     */
    public function withoutCache(): static
    {
        $this->cacheEnabled = false;
        return $this;
    }

    /**
     * 执行原生SQL查询
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array
     */
    protected function rawQuery(string $sql, array $params = []): array
    {
        return Database::queryAll($sql, $params);
    }

    /**
     * 执行原生SQL语句
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return bool
     */
    protected function rawExecute(string $sql, array $params = []): bool
    {
        return Database::execute($sql, $params);
    }
}
