<?php

declare(strict_types=1);

namespace Core\Services;

use Core\Orm\RepositoryInterface;
use app\services\CacheService;

/**
 * Service基类
 * 提供业务逻辑层的通用实现
 */
abstract class BaseService
{
    /**
     * Repository实例
     */
    protected ?RepositoryInterface $repository = null;

    /**
     * 缓存前缀
     */
    protected string $cachePrefix = '';

    /**
     * 默认缓存时间（秒）
     */
    protected int $defaultCacheTtl = 3600;

    /**
     * 错误信息
     */
    protected ?string $lastError = null;

    /**
     * 构造函数
     *
     * @param RepositoryInterface|null $repository Repository实例
     */
    public function __construct(?RepositoryInterface $repository = null)
    {
        if ($repository !== null) {
            $this->repository = $repository;
        }
    }

    /**
     * 设置Repository
     *
     * @param RepositoryInterface $repository Repository实例
     * @return static
     */
    public function setRepository(RepositoryInterface $repository): static
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * 获取Repository
     *
     * @return RepositoryInterface|null
     */
    public function getRepository(): ?RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * 根据ID查找
     *
     * @param int|string $id 主键值
     * @return array|object|null
     */
    public function find(int|string $id): array|object|null
    {
        return $this->repository?->find($id);
    }

    /**
     * 根据ID查找，不存在则抛出异常
     *
     * @param int|string $id 主键值
     * @return array|object
     * @throws \RuntimeException
     */
    public function findOrFail(int|string $id): array|object
    {
        return $this->repository?->findOrFail($id);
    }

    /**
     * 获取所有记录
     *
     * @return array
     */
    public function all(): array
    {
        return $this->repository?->all() ?? [];
    }

    /**
     * 创建记录
     *
     * @param array $data 数据
     * @return array|object|null
     */
    public function create(array $data): array|object|null
    {
        return $this->repository?->create($data);
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
        return $this->repository?->update($id, $data) ?? false;
    }

    /**
     * 删除记录
     *
     * @param int|string $id 主键值
     * @return bool
     */
    public function delete(int|string $id): bool
    {
        return $this->repository?->delete($id) ?? false;
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
        return $this->repository?->paginate($page, $perPage, $conditions) ?? [
            'items' => [],
            'total' => 0,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => 0,
        ];
    }

    /**
     * 执行事务
     *
     * @param callable $callback 回调函数
     * @return mixed
     * @throws \Exception
     */
    public function transaction(callable $callback): mixed
    {
        return $this->repository?->transaction($callback);
    }

    /**
     * 缓存数据
     *
     * @param string $key 缓存键
     * @param callable $callback 回调函数
     * @param int|null $ttl 缓存时间
     * @return mixed
     */
    protected function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);

        return CacheService::remember($cacheKey, $callback, $ttl ?? $this->defaultCacheTtl);
    }

    /**
     * 获取缓存
     *
     * @param string $key 缓存键
     * @return mixed
     */
    protected function getCache(string $key): mixed
    {
        return CacheService::get($this->getCacheKey($key));
    }

    /**
     * 设置缓存
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int|null $ttl 缓存时间
     * @return bool
     */
    protected function setCache(string $key, mixed $value, ?int $ttl = null): bool
    {
        return CacheService::set($this->getCacheKey($key), $value, $ttl ?? $this->defaultCacheTtl);
    }

    /**
     * 删除缓存
     *
     * @param string $key 缓存键
     * @return bool
     */
    protected function deleteCache(string $key): bool
    {
        return CacheService::delete($this->getCacheKey($key));
    }

    /**
     * 清除所有相关缓存
     *
     * @return int
     */
    protected function flushCache(): int
    {
        $prefix = $this->cachePrefix ?: $this->getCachePrefix();
        return CacheService::deleteByPattern("{$prefix}:*");
    }

    /**
     * 获取缓存键
     *
     * @param string $key 键名
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        $prefix = $this->cachePrefix ?: $this->getCachePrefix();
        return "{$prefix}:{$key}";
    }

    /**
     * 获取缓存前缀
     *
     * @return string
     */
    protected function getCachePrefix(): string
    {
        if (!empty($this->cachePrefix)) {
            return $this->cachePrefix;
        }

        // 从类名推断缓存前缀
        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }

    /**
     * 设置最后错误信息
     *
     * @param string $error 错误信息
     * @return void
     */
    protected function setError(string $error): void
    {
        $this->lastError = $error;
    }

    /**
     * 获取最后错误信息
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * 清除错误信息
     *
     * @return void
     */
    protected function clearError(): void
    {
        $this->lastError = null;
    }

    /**
     * 验证数据
     *
     * @param array $data 数据
     * @param array $rules 验证规则
     * @return array{valid: bool, errors: array}
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $error = $this->validateRule($field, $value, $rule, $data);

                if ($error !== null) {
                    $errors[$field][] = $error;
                    break; // 遇到第一个错误就停止
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 验证单个规则
     *
     * @param string $field 字段名
     * @param mixed $value 值
     * @param string $rule 规则
     * @param array $data 所有数据
     * @return string|null 错误信息
     */
    protected function validateRule(string $field, mixed $value, string $rule, array $data): ?string
    {
        $fieldLabel = $this->getFieldLabel($field);

        // 解析规则和参数
        $params = [];
        if (str_contains($rule, ':')) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '') {
                    return "{$fieldLabel}是必填项";
                }
                break;

            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "{$fieldLabel}格式不正确";
                }
                break;

            case 'min':
                $min = (int)($params[0] ?? 0);
                if (is_string($value) && mb_strlen($value) < $min) {
                    return "{$fieldLabel}长度不能少于{$min}个字符";
                }
                if (is_numeric($value) && $value < $min) {
                    return "{$fieldLabel}不能小于{$min}";
                }
                break;

            case 'max':
                $max = (int)($params[0] ?? 0);
                if (is_string($value) && mb_strlen($value) > $max) {
                    return "{$fieldLabel}长度不能超过{$max}个字符";
                }
                if (is_numeric($value) && $value > $max) {
                    return "{$fieldLabel}不能大于{$max}";
                }
                break;

            case 'in':
                if ($value !== null && !in_array($value, $params, true)) {
                    return "{$fieldLabel}值无效";
                }
                break;

            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    return "{$fieldLabel}必须是数字";
                }
                break;

            case 'integer':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
                    return "{$fieldLabel}必须是整数";
                }
                break;

            case 'regex':
                $pattern = $params[0] ?? '';
                if ($value !== null && $value !== '' && !preg_match($pattern, (string)$value)) {
                    return "{$fieldLabel}格式不正确";
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($data[$confirmField] ?? null)) {
                    return "{$fieldLabel}确认不匹配";
                }
                break;

            case 'unique':
                // 需要在子类中实现
                break;

            case 'exists':
                // 需要在子类中实现
                break;
        }

        return null;
    }

    /**
     * 获取字段标签
     *
     * @param string $field 字段名
     * @return string
     */
    protected function getFieldLabel(string $field): string
    {
        // 转换为更友好的标签名
        return ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * 记录日志
     *
     * @param string $level 日志级别
     * @param string $message 消息
     * @param array $context 上下文
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $logMessage = "[{$level}] " . static::class . ": {$message}";

        if (!empty($context)) {
            $logMessage .= " " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        error_log($logMessage);
    }

    /**
     * 记录信息日志
     *
     * @param string $message 消息
     * @param array $context 上下文
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * 记录错误日志
     *
     * @param string $message 消息
     * @param array $context 上下文
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * 记录警告日志
     *
     * @param string $message 消息
     * @param array $context 上下文
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }
}
