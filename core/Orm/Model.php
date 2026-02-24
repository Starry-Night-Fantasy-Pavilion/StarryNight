<?php

declare(strict_types=1);

namespace Core\Orm;

use JsonSerializable;
use ReflectionClass;

/**
 * 基础模型类
 * 实现Active Record模式
 */
abstract class Model implements ModelInterface, JsonSerializable
{
    /**
     * 表名（不含前缀）
     */
    protected string $table;

    /**
     * 主键名
     */
    protected string $primaryKey = 'id';

    /**
     * 主键值
     */
    protected mixed $keyValue = null;

    /**
     * 模型属性
     */
    protected array $attributes = [];

    /**
     * 原始属性（用于脏检查）
     */
    protected array $original = [];

    /**
     * 可填充属性
     */
    protected array $fillable = [];

    /**
     * 保护属性（不可填充）
     */
    protected array $guarded = ['id'];

    /**
     * 隐藏属性（序列化时）
     */
    protected array $hidden = [];

    /**
     * 追加属性
     */
    protected array $appends = [];

    /**
     * 是否使用时间戳
     */
    protected bool $timestamps = true;

    /**
     * 创建时间字段
     */
    protected string $createdAtColumn = 'created_at';

    /**
     * 更新时间字段
     */
    protected string $updatedAtColumn = 'updated_at';

    /**
     * 软删除字段
     */
    protected ?string $softDeleteColumn = null;

    /**
     * 是否存在于数据库
     */
    protected bool $exists = false;

    /**
     * 缓存的查询构建器
     */
    protected static ?QueryBuilder $queryBuilder = null;

    /**
     * 构造函数
     *
     * @param array $attributes 属性
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->syncOriginal();
    }

    /**
     * 获取表名
     *
     * @return string
     */
    public function getTable(): string
    {
        if (!isset($this->table)) {
            // 从类名推断表名
            $className = (new ReflectionClass($this))->getShortName();
            $this->table = $this->camelToSnake($className) . 's';
        }
        return $this->table;
    }

    /**
     * 获取主键名
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * 获取主键值
     *
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    /**
     * 设置主键值
     *
     * @param mixed $value 主键值
     * @return void
     */
    public function setKey(mixed $value): void
    {
        $this->attributes[$this->primaryKey] = $value;
    }

    /**
     * 填充属性
     *
     * @param array $attributes 属性
     * @return static
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    /**
     * 检查属性是否可填充
     *
     * @param string $key 属性名
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        // 如果设置了fillable，只允许fillable中的属性
        if (!empty($this->fillable) && !in_array($key, $this->fillable, true)) {
            return false;
        }

        // 如果属性在guarded中，不允许填充
        if (in_array($key, $this->guarded, true)) {
            return false;
        }

        return true;
    }

    /**
     * 设置属性
     *
     * @param string $key 属性名
     * @param mixed $value 属性值
     * @return static
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * 获取属性
     *
     * @param string $key 属性名
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        // 检查是否有访问器
        $accessor = 'get' . $this->studly($key) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->$accessor();
        }

        return null;
    }

    /**
     * 同步原始属性
     *
     * @return static
     */
    public function syncOriginal(): static
    {
        $this->original = $this->attributes;
        return $this;
    }

    /**
     * 设置是否存在
     *
     * @param bool $exists 是否存在
     * @return static
     */
    public function setExists(bool $exists): static
    {
        $this->exists = $exists;
        return $this;
    }

    /**
     * 设置原始属性
     *
     * @param array $original 原始属性
     * @return static
     */
    public function setOriginal(array $original): static
    {
        $this->original = $original;
        return $this;
    }

    /**
     * 获取脏属性
     *
     * @return array
     */
    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /**
     * 检查是否为脏属性
     *
     * @param string|null $key 属性名
     * @return bool
     */
    public function isDirty(?string $key = null): bool
    {
        if ($key === null) {
            return !empty($this->getDirty());
        }
        return array_key_exists($key, $this->getDirty());
    }

    /**
     * 创建新查询构建器
     *
     * @return QueryBuilder
     */
    public static function newQuery(): QueryBuilder
    {
        $instance = new static();
        return (new QueryBuilder($instance->getTable()))->setModel(static::class);
    }

    /**
     * 根据主键查找记录
     *
     * @param int|string $id 主键值
     * @return static|null
     */
    public static function find(int|string $id): ?static
    {
        return static::newQuery()->find($id);
    }

    /**
     * 根据主键查找，不存在则抛出异常
     *
     * @param int|string $id 主键值
     * @return static
     * @throws \RuntimeException
     */
    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);
        if ($model === null) {
            throw new \RuntimeException("Model not found for ID: {$id}");
        }
        return $model;
    }

    /**
     * 查找所有记录
     *
     * @return array
     */
    public static function all(): array
    {
        return static::newQuery()->get();
    }

    /**
     * 创建新记录
     *
     * @param array $data 数据
     * @return static|null
     */
    public static function create(array $data): ?static
    {
        $model = new static();
        $model->fill($data);

        if ($model->timestamps) {
            $model->setAttribute($model->createdAtColumn, date('Y-m-d H:i:s'));
            $model->setAttribute($model->updatedAtColumn, date('Y-m-d H:i:s'));
        }

        $id = static::newQuery()->insert($model->attributes);

        if ($id) {
            $model->setKey($id);
            $model->setExists(true);
            $model->syncOriginal();
            return $model;
        }

        return null;
    }

    /**
     * 更新记录
     *
     * @param array $data 更新数据
     * @return bool
     */
    public function update(array $data): bool
    {
        $this->fill($data);
        return $this->save();
    }

    /**
     * 删除记录
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $key = $this->getKey();
        if ($key === null) {
            return false;
        }

        // 软删除
        if ($this->softDeleteColumn !== null) {
            return $this->performSoftDelete();
        }

        // 硬删除
        $affected = static::newQuery()->where($this->primaryKey, $key)->delete();
        $this->exists = false;

        return $affected > 0;
    }

    /**
     * 执行软删除
     *
     * @return bool
     */
    protected function performSoftDelete(): bool
    {
        $this->setAttribute($this->softDeleteColumn, date('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * 恢复软删除
     *
     * @return bool
     */
    public function restore(): bool
    {
        if ($this->softDeleteColumn === null) {
            return false;
        }

        $this->setAttribute($this->softDeleteColumn, null);
        return $this->save();
    }

    /**
     * 强制删除（包括软删除的记录）
     *
     * @return bool
     */
    public function forceDelete(): bool
    {
        $softDelete = $this->softDeleteColumn;
        $this->softDeleteColumn = null;
        $result = $this->delete();
        $this->softDeleteColumn = $softDelete;
        return $result;
    }

    /**
     * 保存记录（新增或更新）
     *
     * @return bool
     */
    public function save(): bool
    {
        if ($this->exists) {
            return $this->performUpdate();
        }
        return $this->performInsert();
    }

    /**
     * 执行插入
     *
     * @return bool
     */
    protected function performInsert(): bool
    {
        if ($this->timestamps) {
            $this->setAttribute($this->createdAtColumn, date('Y-m-d H:i:s'));
            $this->setAttribute($this->updatedAtColumn, date('Y-m-d H:i:s'));
        }

        $id = static::newQuery()->insert($this->attributes);

        if ($id) {
            $this->setKey($id);
            $this->exists = true;
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    /**
     * 执行更新
     *
     * @return bool
     */
    protected function performUpdate(): bool
    {
        $dirty = $this->getDirty();

        if (empty($dirty)) {
            return true;
        }

        if ($this->timestamps) {
            $this->setAttribute($this->updatedAtColumn, date('Y-m-d H:i:s'));
            $dirty[$this->updatedAtColumn] = $this->getAttribute($this->updatedAtColumn);
        }

        $affected = static::newQuery()
            ->where($this->primaryKey, $this->getKey())
            ->update($dirty);

        if ($affected > 0) {
            $this->syncOriginal();
            return true;
        }

        return false;
    }

    /**
     * 首次或创建
     *
     * @param array $attributes 查询属性
     * @param array $values 创建时的额外属性
     * @return static
     */
    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        $query = static::newQuery();
        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }

        $model = $query->first();

        if ($model !== null) {
            return $model;
        }

        return static::create(array_merge($attributes, $values));
    }

    /**
     * 更新或创建
     *
     * @param array $attributes 查询属性
     * @param array $values 更新/创建属性
     * @return static
     */
    public static function updateOrCreate(array $attributes, array $values = []): static
    {
        $model = static::firstOrCreate($attributes, $values);
        $model->fill($values);
        $model->save();
        return $model;
    }

    /**
     * 转换为数组
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = $this->attributes;

        // 移除隐藏属性
        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }

        // 追加属性
        foreach ($this->appends as $key) {
            $attributes[$key] = $this->getAttribute($key);
        }

        return $attributes;
    }

    /**
     * 转换为JSON
     *
     * @param int $options JSON选项
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options | JSON_UNESCAPED_UNICODE);
    }

    /**
     * JSON序列化
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 魔术方法：获取属性
     *
     * @param string $key 属性名
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * 魔术方法：设置属性
     *
     * @param string $key 属性名
     * @param mixed $value 属性值
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * 魔术方法：检查属性是否存在
     *
     * @param string $key 属性名
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->getAttribute($key) !== null;
    }

    /**
     * 魔术方法：取消设置属性
     *
     * @param string $key 属性名
     * @return void
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * 魔术方法：调用静态方法
     *
     * @param string $method 方法名
     * @param array $arguments 参数
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::newQuery()->$method(...$arguments);
    }

    /**
     * 驼峰转蛇形
     *
     * @param string $value 字符串
     * @return string
     */
    protected function camelToSnake(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    /**
     * 转换为StudlyCase
     *
     * @param string $value 字符串
     * @return string
     */
    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    /**
     * 克隆模型
     *
     * @return static
     */
    public function replicate(): static
    {
        $model = new static();
        $model->fill($this->attributes);
        $model->setKey(null);
        $model->exists = false;
        return $model;
    }

    /**
     * 刷新模型
     *
     * @return static|null
     */
    public function refresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }

        $model = static::find($this->getKey());
        if ($model !== null) {
            $this->attributes = $model->attributes;
            $this->original = $model->original;
        }

        return $this;
    }

    /**
     * 是否存在
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * 转换为字符串
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}
