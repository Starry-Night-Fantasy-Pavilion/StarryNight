<?php

declare(strict_types=1);

namespace Core\Services;

/**
 * 增强版缓存服务
 * 支持缓存标签、分布式锁、批量操作等高级功能
 * 
 * 注意：Redis类和RedisException类来自phpredis扩展
 * 如果扩展未安装，将使用内存缓存作为后备
 */
class EnhancedCacheService
{
    /**
     * Redis连接实例
     * @var \Redis|null
     */
    protected $redis = null;

    /**
     * 内存缓存
     */
    protected array $memoryCache = [];

    /**
     * Redis是否可用
     */
    protected bool $redisAvailable = false;

    /**
     * 默认缓存前缀
     */
    protected string $prefix = 'cache:';

    /**
     * 默认TTL
     */
    protected int $defaultTtl = 3600;

    /**
     * 缓存标签
     */
    protected array $tags = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->initRedis();
    }

    /**
     * 初始化Redis连接
     */
    protected function initRedis(): void
    {
        if ($this->redis !== null) {
            return;
        }

        try {
            $host = $this->getEnv('REDIS_HOST', '127.0.0.1');
            $port = (int)$this->getEnv('REDIS_PORT', 6379);
            $password = $this->getEnv('REDIS_PASSWORD', '');
            $database = (int)$this->getEnv('REDIS_DATABASE', 0);

            // 检查Redis扩展是否加载
            if (extension_loaded('redis') && class_exists('\\Redis')) {
                $this->redis = new \Redis();
                $connected = $this->redis->connect($host, $port, 2);

                if ($connected) {
                    if ($password) {
                        $this->redis->auth($password);
                    }
                    if ($database > 0) {
                        $this->redis->select($database);
                    }
                    $this->redisAvailable = true;
                }
            }
        } catch (\Exception $e) {
            error_log('Redis连接失败: ' . $e->getMessage());
            $this->redisAvailable = false;
        }
    }

    /**
     * 获取环境变量
     */
    protected function getEnv(string $key, mixed $default = null): mixed
    {
        if (function_exists('get_env')) {
            return get_env($key, $default);
        }
        return $_ENV[$key] ?? $default;
    }

    /**
     * 获取缓存
     *
     * @param string $key 缓存键
     * @param callable|null $fallback 回调函数
     * @param int|null $ttl 过期时间
     * @return mixed
     */
    public function get(string $key, ?callable $fallback = null, ?int $ttl = null): mixed
    {
        $fullKey = $this->prefixKey($key);

        // 1. 检查内存缓存
        if (isset($this->memoryCache[$fullKey])) {
            return $this->memoryCache[$fullKey];
        }

        // 2. 检查Redis缓存
        if ($this->redisAvailable && $this->redis) {
            try {
                $value = $this->redis->get($fullKey);
                if ($value !== false) {
                    $decoded = $this->decode($value);
                    $this->memoryCache[$fullKey] = $decoded;
                    return $decoded;
                }
            } catch (\Exception $e) {
                error_log('Redis读取失败: ' . $e->getMessage());
            }
        }

        // 3. 执行回调并缓存
        if ($fallback !== null) {
            $value = $fallback();
            $this->set($key, $value, $ttl);
            return $value;
        }

        return null;
    }

    /**
     * 设置缓存
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int|null $ttl 过期时间
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $fullKey = $this->prefixKey($key);
        $ttl = $ttl ?? $this->defaultTtl;

        // 设置内存缓存
        $this->memoryCache[$fullKey] = $value;

        // 设置Redis缓存
        if ($this->redisAvailable && $this->redis) {
            try {
                $encoded = $this->encode($value);

                if ($ttl > 0) {
                    return $this->redis->setex($fullKey, $ttl, $encoded);
                }

                return $this->redis->set($fullKey, $encoded);
            } catch (\Exception $e) {
                error_log('Redis写入失败: ' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * 删除缓存
     *
     * @param string $key 缓存键
     * @return bool
     */
    public function delete(string $key): bool
    {
        $fullKey = $this->prefixKey($key);

        // 删除内存缓存
        unset($this->memoryCache[$fullKey]);

        // 删除Redis缓存
        if ($this->redisAvailable && $this->redis) {
            try {
                return $this->redis->del($fullKey) > 0;
            } catch (\Exception $e) {
                error_log('Redis删除失败: ' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * 检查缓存是否存在
     *
     * @param string $key 缓存键
     * @return bool
     */
    public function has(string $key): bool
    {
        $fullKey = $this->prefixKey($key);

        if (isset($this->memoryCache[$fullKey])) {
            return true;
        }

        if ($this->redisAvailable && $this->redis) {
            try {
                return $this->redis->exists($fullKey) > 0;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * 批量删除缓存
     *
     * @param string $pattern 匹配模式
     * @return int
     */
    public function deleteByPattern(string $pattern): int
    {
        $count = 0;
        $fullPattern = $this->prefixKey($pattern);

        // 删除内存缓存
        foreach (array_keys($this->memoryCache) as $key) {
            if (fnmatch($fullPattern, $key)) {
                unset($this->memoryCache[$key]);
                $count++;
            }
        }

        // 删除Redis缓存
        if ($this->redisAvailable && $this->redis) {
            try {
                $keys = $this->redis->keys($fullPattern);
                if (!empty($keys)) {
                    $count += $this->redis->del($keys);
                }
            } catch (\Exception $e) {
                error_log('Redis批量删除失败: ' . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * 设置缓存标签
     *
     * @param array|string $tags 标签
     * @return static
     */
    public function tags(array|string $tags): static
    {
        $this->tags = is_array($tags) ? $tags : [$tags];
        return $this;
    }

    /**
     * 带标签的缓存设置
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int|null $ttl 过期时间
     * @return bool
     */
    public function setWithTag(string $key, mixed $value, ?int $ttl = null): bool
    {
        $result = $this->set($key, $value, $ttl);

        if ($result && !empty($this->tags)) {
            $this->addKeyToTags($key);
        }

        $this->tags = [];
        return $result;
    }

    /**
     * 将缓存键添加到标签集合
     *
     * @param string $key 缓存键
     */
    protected function addKeyToTags(string $key): void
    {
        if (!$this->redisAvailable || !$this->redis) {
            return;
        }

        try {
            foreach ($this->tags as $tag) {
                $tagKey = $this->prefixKey("tag:{$tag}");
                $this->redis->sAdd($tagKey, $key);
            }
        } catch (\Exception $e) {
            error_log('添加标签失败: ' . $e->getMessage());
        }
    }

    /**
     * 清除标签下的所有缓存
     *
     * @param string $tag 标签名
     * @return int
     */
    public function clearTag(string $tag): int
    {
        if (!$this->redisAvailable || !$this->redis) {
            return 0;
        }

        $count = 0;
        $tagKey = $this->prefixKey("tag:{$tag}");

        try {
            $keys = $this->redis->sMembers($tagKey);
            foreach ($keys as $key) {
                if ($this->delete($key)) {
                    $count++;
                }
            }
            $this->redis->del($tagKey);
        } catch (\Exception $e) {
            error_log('清除标签缓存失败: ' . $e->getMessage());
        }

        return $count;
    }

    /**
     * 获取分布式锁
     *
     * @param string $key 锁键
     * @param int $ttl 锁过期时间
     * @param int $retry 重试次数
     * @param int $delay 重试间隔（毫秒）
     * @return string|null 锁令牌
     */
    public function lock(string $key, int $ttl = 30, int $retry = 3, int $delay = 200): ?string
    {
        if (!$this->redisAvailable || !$this->redis) {
            return uniqid('lock_', true);
        }

        $lockKey = $this->prefixKey("lock:{$key}");
        $token = uniqid('token_', true);

        for ($i = 0; $i < $retry; $i++) {
            try {
                $result = $this->redis->set($lockKey, $token, ['NX', 'EX' => $ttl]);
                if ($result) {
                    return $token;
                }
            } catch (\Exception $e) {
                error_log('获取锁失败: ' . $e->getMessage());
            }

            usleep($delay * 1000);
        }

        return null;
    }

    /**
     * 释放分布式锁
     *
     * @param string $key 锁键
     * @param string $token 锁令牌
     * @return bool
     */
    public function unlock(string $key, string $token): bool
    {
        if (!$this->redisAvailable || !$this->redis) {
            return true;
        }

        $lockKey = $this->prefixKey("lock:{$key}");

        try {
            // 使用Lua脚本确保原子性
            $script = "
                if redis.call('GET', KEYS[1]) == ARGV[1] then
                    return redis.call('DEL', KEYS[1])
                else
                    return 0
                end
            ";
            return $this->redis->eval($script, [$lockKey, $token], 1) > 0;
        } catch (\Exception $e) {
            error_log('释放锁失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 执行带锁的操作
     *
     * @param string $key 锁键
     * @param callable $callback 回调函数
     * @param int $ttl 锁过期时间
     * @return mixed
     * @throws \RuntimeException
     */
    public function withLock(string $key, callable $callback, int $ttl = 30): mixed
    {
        $token = $this->lock($key, $ttl);

        if ($token === null) {
            throw new \RuntimeException("无法获取锁: {$key}");
        }

        try {
            return $callback();
        } finally {
            $this->unlock($key, $token);
        }
    }

    /**
     * 自增
     *
     * @param string $key 缓存键
     * @param int $value 增量
     * @return int
     */
    public function increment(string $key, int $value = 1): int
    {
        $fullKey = $this->prefixKey($key);

        if ($this->redisAvailable && $this->redis) {
            try {
                return $this->redis->incrBy($fullKey, $value);
            } catch (\Exception $e) {
                error_log('自增失败: ' . $e->getMessage());
            }
        }

        // 内存缓存自增
        $current = $this->memoryCache[$fullKey] ?? 0;
        $this->memoryCache[$fullKey] = $current + $value;
        return $this->memoryCache[$fullKey];
    }

    /**
     * 自减
     *
     * @param string $key 缓存键
     * @param int $value 减量
     * @return int
     */
    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }

    /**
     * 批量获取
     *
     * @param array $keys 缓存键数组
     * @return array
     */
    public function multiGet(array $keys): array
    {
        $result = [];
        $fullKeys = array_map(fn($key) => $this->prefixKey($key), $keys);

        // 从内存缓存获取
        foreach ($fullKeys as $index => $fullKey) {
            if (isset($this->memoryCache[$fullKey])) {
                $result[$keys[$index]] = $this->memoryCache[$fullKey];
                unset($fullKeys[$index]);
            }
        }

        // 从Redis获取
        if ($this->redisAvailable && $this->redis && !empty($fullKeys)) {
            try {
                $values = $this->redis->mGet(array_values($fullKeys));
                foreach ($values as $index => $value) {
                    if ($value !== false) {
                        $key = $keys[array_search($fullKeys[$index], $fullKeys)];
                        $result[$key] = $this->decode($value);
                    }
                }
            } catch (\Exception $e) {
                error_log('批量获取失败: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * 批量设置
     *
     * @param array $data 键值对
     * @param int|null $ttl 过期时间
     * @return bool
     */
    public function multiSet(array $data, ?int $ttl = null): bool
    {
        if (empty($data)) {
            return true;
        }

        $ttl = $ttl ?? $this->defaultTtl;

        // 设置内存缓存
        foreach ($data as $key => $value) {
            $this->memoryCache[$this->prefixKey($key)] = $value;
        }

        // 设置Redis缓存
        if ($this->redisAvailable && $this->redis) {
            try {
                $pipeline = $this->redis->multi(\Redis::PIPELINE);
                foreach ($data as $key => $value) {
                    $fullKey = $this->prefixKey($key);
                    $encoded = $this->encode($value);
                    if ($ttl > 0) {
                        $pipeline->setex($fullKey, $ttl, $encoded);
                    } else {
                        $pipeline->set($fullKey, $encoded);
                    }
                }
                $pipeline->exec();
            } catch (\Exception $e) {
                error_log('批量设置失败: ' . $e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * 清空所有缓存
     *
     * @return bool
     */
    public function flush(): bool
    {
        $this->memoryCache = [];

        if ($this->redisAvailable && $this->redis) {
            try {
                return $this->redis->flushDB();
            } catch (\Exception $e) {
                error_log('清空缓存失败: ' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * 获取缓存统计
     *
     * @return array
     */
    public function getStats(): array
    {
        $stats = [
            'memory_cache_count' => count($this->memoryCache),
            'redis_available' => $this->redisAvailable,
        ];

        if ($this->redisAvailable && $this->redis) {
            try {
                $info = $this->redis->info();
                $stats['redis_keys'] = $info['db0']['keys'] ?? 0;
                $stats['redis_memory'] = $info['used_memory_human'] ?? '0B';
                $stats['redis_hits'] = $info['keyspace_hits'] ?? 0;
                $stats['redis_misses'] = $info['keyspace_misses'] ?? 0;
            } catch (\Exception $e) {
                $stats['redis_error'] = $e->getMessage();
            }
        }

        return $stats;
    }

    /**
     * 添加前缀
     *
     * @param string $key 缓存键
     * @return string
     */
    protected function prefixKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * 编码值
     *
     * @param mixed $value 值
     * @return string
     */
    protected function encode(mixed $value): string
    {
        return serialize($value);
    }

    /**
     * 解码值
     *
     * @param string $value 编码值
     * @return mixed
     */
    protected function decode(string $value): mixed
    {
        return unserialize($value);
    }

    /**
     * 设置缓存前缀
     *
     * @param string $prefix 前缀
     * @return static
     */
    public function setPrefix(string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * 设置默认TTL
     *
     * @param int $ttl 过期时间
     * @return static
     */
    public function setDefaultTtl(int $ttl): static
    {
        $this->defaultTtl = $ttl;
        return $this;
    }

    /**
     * 检查Redis是否可用
     *
     * @return bool
     */
    public function isRedisAvailable(): bool
    {
        return $this->redisAvailable;
    }

    /**
     * 获取Redis实例
     *
     * @return \Redis|null
     */
    public function getRedis(): ?\Redis
    {
        return $this->redis;
    }
}
