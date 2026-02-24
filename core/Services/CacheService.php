<?php
/**
 * 缓存服务
 * 
 * @package Core\Services
 */

namespace Core\Services;

class CacheService
{
    private string $cachePath;
    private int $defaultTtl;
    private string $prefix;

    public function __construct(string $cachePath = null, int $defaultTtl = 3600, string $prefix = 'sn_')
    {
        $this->cachePath = $cachePath ?? dirname(__DIR__, 2) . '/storage/cache';
        $this->defaultTtl = $defaultTtl;
        $this->prefix = $prefix;
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * 获取缓存
     */
    public function get(string $key, $default = null)
    {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $content = file_get_contents($file);
        $data = unserialize($content);
        
        // 检查是否过期
        if ($data['expires_at'] !== 0 && $data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }
        
        return $data['value'];
    }

    /**
     * 设置缓存
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $file = $this->getCacheFile($key);
        $ttl = $ttl ?? $this->defaultTtl;
        
        $data = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : 0,
            'created_at' => time()
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * 删除缓存
     */
    public function delete(string $key): bool
    {
        $file = $this->getCacheFile($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }

    /**
     * 检查缓存是否存在
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * 清空所有缓存
     */
    public function clear(): bool
    {
        $files = glob($this->cachePath . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }

    /**
     * 获取或设置缓存（闭包）
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * 永久缓存
     */
    public function forever(string $key, $value): bool
    {
        return $this->set($key, $value, 0);
    }

    /**
     * 递增
     */
    public function increment(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $value;
        $this->set($key, $new);
        return $new;
    }

    /**
     * 递减
     */
    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }

    /**
     * 获取缓存文件路径
     */
    private function getCacheFile(string $key): string
    {
        $hash = md5($this->prefix . $key);
        return $this->cachePath . '/' . $hash . '.cache';
    }

    /**
     * 获取缓存统计
     */
    public function stats(): array
    {
        $files = glob($this->cachePath . '/*.cache');
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'count' => count($files),
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize)
        ];
    }

    /**
     * 格式化字节
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
