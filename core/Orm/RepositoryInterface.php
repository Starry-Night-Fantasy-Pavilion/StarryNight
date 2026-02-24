<?php

declare(strict_types=1);

namespace Core\Orm;

/**
 * Repository接口
 * 定义数据仓库必须实现的方法
 */
interface RepositoryInterface
{
    /**
     * 根据主键查找
     *
     * @param int|string $id 主键值
     * @return array|object|null
     */
    public function find(int|string $id): array|object|null;

    /**
     * 根据主键查找，不存在则抛出异常
     *
     * @param int|string $id 主键值
     * @return array|object
     * @throws \RuntimeException
     */
    public function findOrFail(int|string $id): array|object;

    /**
     * 查找所有记录
     *
     * @return array
     */
    public function all(): array;

    /**
     * 根据条件查找
     *
     * @param array $conditions 条件
     * @return array
     */
    public function findBy(array $conditions): array;

    /**
     * 根据条件查找第一条
     *
     * @param array $conditions 条件
     * @return array|object|null
     */
    public function findFirstBy(array $conditions): array|object|null;

    /**
     * 创建记录
     *
     * @param array $data 数据
     * @return array|object|null
     */
    public function create(array $data): array|object|null;

    /**
     * 更新记录
     *
     * @param int|string $id 主键值
     * @param array $data 更新数据
     * @return bool
     */
    public function update(int|string $id, array $data): bool;

    /**
     * 删除记录
     *
     * @param int|string $id 主键值
     * @return bool
     */
    public function delete(int|string $id): bool;

    /**
     * 分页查询
     *
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @param array $conditions 条件
     * @return array
     */
    public function paginate(int $page = 1, int $perPage = 15, array $conditions = []): array;

    /**
     * 统计数量
     *
     * @param array $conditions 条件
     * @return int
     */
    public function count(array $conditions = []): int;

    /**
     * 检查是否存在
     *
     * @param array $conditions 条件
     * @return bool
     */
    public function exists(array $conditions): bool;

    /**
     * 开始事务
     *
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * 提交事务
     *
     * @return void
     */
    public function commit(): void;

    /**
     * 回滚事务
     *
     * @return void
     */
    public function rollback(): void;

    /**
     * 执行事务
     *
     * @param callable $callback 回调函数
     * @return mixed
     */
    public function transaction(callable $callback): mixed;
}
