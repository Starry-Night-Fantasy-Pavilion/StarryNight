<?php

declare(strict_types=1);

namespace Core\Orm;

/**
 * 模型接口
 * 定义ORM模型必须实现的方法
 */
interface ModelInterface
{
    /**
     * 根据主键查找记录
     *
     * @param int|string $id 主键值
     * @return static|null
     */
    public static function find(int|string $id): ?static;

    /**
     * 查找所有记录
     *
     * @return array
     */
    public static function all(): array;

    /**
     * 创建新记录
     *
     * @param array $data 数据
     * @return static|null
     */
    public static function create(array $data): ?static;

    /**
     * 更新记录
     *
     * @param array $data 更新数据
     * @return bool
     */
    public function update(array $data): bool;

    /**
     * 删除记录
     *
     * @return bool
     */
    public function delete(): bool;

    /**
     * 保存记录（新增或更新）
     *
     * @return bool
     */
    public function save(): bool;

    /**
     * 转换为数组
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * 转换为JSON
     *
     * @param int $options JSON选项
     * @return string
     */
    public function toJson(int $options = 0): string;
}
