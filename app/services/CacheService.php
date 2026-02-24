<?php

namespace app\services;

/**
 * 多级缓存服务
 * 提供内存缓存、Redis缓存和数据库查询缓存的统一接口
 */
class CacheService
{
    /**
     * @var \Redis|\Predis\Client|null Redis客户端实例
     */
    private static $redis = null;
    
    /**
     * @var array 内存缓存（当前请求生命周期）
     */
    private static $memoryCache = [];
    
    /**
     * @var bool Redis是否可用
     */
    private static $redisAvailable = false;
    
    /**
     * 初始化Redis连接
     */
    private static function initRedis(): void
    {
        if (self::$redis !== null) {
            return;
        }
        
        try {
            $host = get_env('REDIS_HOST', '127.0.0.1');
            $port = (int)get_env('REDIS_PORT', 6379);
            $password = get_env('REDIS_PASSWORD', '');
            $database = (int)get_env('REDIS_DATABASE', 0);
            
            // 尝试使用Redis扩展
            if (extension_loaded('redis')) {
                self::$redis = new \Redis();
                $connected = self::$redis->connect($host, $port, 2);
                if ($connected && $password) {
                    self::$redis->auth($password);
                }
                if ($database > 0) {
                    self::$redis->select($database);
                }
                self::$redisAvailable = $connected;
            } 
            // 尝试使用Predis库
            elseif (class_exists('\Predis\Client')) {
                $config = [
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                ];
                if ($password) {
                    $config['password'] = $password;
                }
                self::$redis = new \Predis\Client($config);
                self::$redis->connect();
                self::$redisAvailable = true;
            }
        } catch (\Exception $e) {
            error_log('Redis连接失败: ' . $e->getMessage());
            self::$redisAvailable = false;
        }
    }
    
    /**
     * 获取缓存值
     *
     * @param string $key 缓存键
     * @param callable|null $fallback 如果缓存不存在，执行的回调函数
     * @param int $ttl 缓存过期时间（秒），仅当fallback存在时有效
     * @return mixed 缓存值或fallback返回值
     */
    public static function get(string $key, ?callable $fallback = null, int $ttl = 3600)
    {
        // 1. 检查内存缓存
        if (isset(self::$memoryCache[$key])) {
            return self::$memoryCache[$key];
        }
        
        // 2. 检查Redis缓存
        self::initRedis();
        if (self::$redisAvailable && self::$redis) {
            try {
                $value = self::$redis->get($key);
                if ($value !== false && $value !== null) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        self::$memoryCache[$key] = $decoded;
                        return $decoded;
                    }
                    // 如果不是JSON，直接返回
                    self::$memoryCache[$key] = $value;
                    return $value;
                }
            } catch (\Exception $e) {
                error_log('Redis读取失败: ' . $e->getMessage());
            }
        }
        
        // 3. 执行fallback并缓存结果
        if ($fallback !== null) {
            $value = $fallback();
            self::set($key, $value, $ttl);
            return $value;
        }
        
        return null;
    }
    
    /**
     * 设置缓存值
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $ttl 过期时间（秒），0表示永不过期
     * @return bool 是否成功
     */
    public static function set(string $key, $value, int $ttl = 3600): bool
    {
        // 1. 设置内存缓存
        self::$memoryCache[$key] = $value;
        
        // 2. 设置Redis缓存
        self::initRedis();
        if (self::$redisAvailable && self::$redis) {
            try {
                $encoded = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                
                if ($ttl > 0) {
                    if (self::$redis instanceof \Redis) {
                        return self::$redis->setex($key, $ttl, $encoded);
                    } else {
                        return self::$redis->setex($key, $ttl, $encoded) !== null;
                    }
                } else {
                    if (self::$redis instanceof \Redis) {
                        return self::$redis->set($key, $encoded);
                    } else {
                        return self::$redis->set($key, $encoded) !== null;
                    }
                }
            } catch (\Exception $e) {
                error_log('Redis写入失败: ' . $e->getMessage());
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 删除缓存
     *
     * @param string $key 缓存键
     * @return bool 是否成功
     */
    public static function delete(string $key): bool
    {
        // 删除内存缓存
        unset(self::$memoryCache[$key]);
        
        // 删除Redis缓存
        self::initRedis();
        if (self::$redisAvailable && self::$redis) {
            try {
                if (self::$redis instanceof \Redis) {
                    return self::$redis->del($key) > 0;
                } else {
                    return self::$redis->del($key) > 0;
                }
            } catch (\Exception $e) {
                error_log('Redis删除失败: ' . $e->getMessage());
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 批量删除缓存（支持通配符）
     *
     * @param string $pattern 匹配模式，如 'user:*'
     * @return int 删除的数量
     */
    public static function deleteByPattern(string $pattern): int
    {
        $count = 0;
        
        // 删除内存缓存中的匹配项
        foreach (array_keys(self::$memoryCache) as $key) {
            if (fnmatch($pattern, $key)) {
                unset(self::$memoryCache[$key]);
                $count++;
            }
        }
        
        // 删除Redis缓存中的匹配项
        self::initRedis();
        if (self::$redisAvailable && self::$redis) {
            try {
                if (self::$redis instanceof \Redis) {
                    $keys = self::$redis->keys($pattern);
                    if (!empty($keys)) {
                        $count += self::$redis->del($keys);
                    }
                } else {
                    $keys = self::$redis->keys($pattern);
                    if (!empty($keys)) {
                        $count += count($keys);
                        self::$redis->del($keys);
                    }
                }
            } catch (\Exception $e) {
                error_log('Redis批量删除失败: ' . $e->getMessage());
            }
        }
        
        return $count;
    }
    
    /**
     * 检查缓存是否存在
     *
     * @param string $key 缓存键
     * @return bool
     */
    public static function has(string $key): bool
    {
        // 检查内存缓存
        if (isset(self::$memoryCache[$key])) {
            return true;
        }
        
        // 检查Redis缓存
        self::initRedis();
        if (self::$redisAvailable && self::$redis) {
            try {
                if (self::$redis instanceof \Redis) {
                    return self::$redis->exists($key) > 0;
                } else {
                    return self::$redis->exists($key) > 0;
                }
            } catch (\Exception $e) {
                error_log('Redis检查失败: ' . $e->getMessage());
            }
        }
        
        return false;
    }
    
    /**
     * 清空所有缓存
     *
     * @return bool
     */
    public static function flush(): bool
    {
        // 清空内存缓存
        self::$memoryCache = [];
        
        // 清空Redis缓存
        self::initRedis();
        if (self::$redisAvailable && self::$redis) {
            try {
                if (self::$redis instanceof \Redis) {
                    return self::$redis->flushDB();
                } else {
                    return self::$redis->flushdb() !== null;
                }
            } catch (\Exception $e) {
                error_log('Redis清空失败: ' . $e->getMessage());
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 获取缓存统计信息
     *
     * @return array
     */
    public static function getStats(): array
    {
        self::initRedis();
        
        $stats = [
            'memory_cache_count' => count(self::$memoryCache),
            'redis_available' => self::$redisAvailable,
        ];
        
        if (self::$redisAvailable && self::$redis) {
            try {
                if (self::$redis instanceof \Redis) {
                    $info = self::$redis->info();
                    $stats['redis_keys'] = $info['db0']['keys'] ?? 0;
                    $stats['redis_memory'] = $info['used_memory_human'] ?? '0B';
                } else {
                    $info = self::$redis->info();
                    $stats['redis_keys'] = $info['db0']['keys'] ?? 0;
                    $stats['redis_memory'] = $info['used_memory_human'] ?? '0B';
                }
            } catch (\Exception $e) {
                $stats['redis_error'] = $e->getMessage();
            }
        }
        
        return $stats;
    }
    
    /**
     * 记住缓存（简化版get+set）
     *
     * @param string $key 缓存键
     * @param callable $callback 生成缓存值的回调
     * @param int $ttl 过期时间（秒）
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = 3600)
    {
        return self::get($key, $callback, $ttl);
    }
}
