<?php

namespace app\services;

/**
 * 静态资源优化服务
 * 
 * 提供静态资源的合并、压缩和缓存功能
 */
class AssetOptimizer
{
    private static $config = null;
    private static $cacheDir = null;
    
    /**
     * 获取配置
     */
    private static function getConfig(): array
    {
        if (self::$config === null) {
            $configFile = __DIR__ . '/../../config/assets.php';
            self::$config = file_exists($configFile) ? require $configFile : [];
        }
        return self::$config;
    }
    
    /**
     * 获取缓存目录
     */
    private static function getCacheDir(): string
    {
        if (self::$cacheDir === null) {
            $config = self::getConfig();
            self::$cacheDir = $config['cache']['directory'] ?? __DIR__ . '/../../storage/framework/assets/';
            
            // 确保缓存目录存在
            if (!is_dir(self::$cacheDir)) {
                @mkdir(self::$cacheDir, 0755, true);
            }
        }
        return self::$cacheDir;
    }
    
    /**
     * 生成缓存键
     */
    private static function getCacheKey(string $type, string $name): string
    {
        $files = self::getConfig()[$type][$name] ?? [];
        
        // 获取所有文件的修改时间
        $mtimes = [];
        foreach ($files as $file) {
            $fullPath = __DIR__ . '/../../public' . $file;
            if (file_exists($fullPath)) {
                $mtimes[] = filemtime($fullPath);
            }
        }
        
        // 使用文件修改时间的 MD5 作为缓存键
        return md5($name . implode(',', $mtimes));
    }
    
    /**
     * 合并 CSS 文件
     */
    public static function combineCss(string $name): string
    {
        $config = self::getConfig();
        $files = $config['css'][$name] ?? [];
        
        if (empty($files)) {
            return '';
        }
        
        $cacheKey = self::getCacheKey('css', $name);
        $cacheFile = self::getCacheDir() . $cacheKey . '.css';
        
        // 检查缓存
        if (file_exists($cacheFile)) {
            return '/storage/framework/assets/' . $cacheKey . '.css';
        }
        
        // 合并文件
        $content = '';
        foreach ($files as $file) {
            $fullPath = __DIR__ . '/../../public' . $file;
            if (file_exists($fullPath)) {
                $fileContent = file_get_contents($fullPath);
                
                // 移除 CSS 注释
                if ($config['minify']['remove_comments'] ?? true) {
                    $fileContent = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $fileContent);
                }
                
                // 移除多余空白
                if ($config['minify']['remove_whitespace'] ?? true) {
                    $fileContent = preg_replace('/\s+/', ' ', $fileContent);
                    $fileContent = str_replace(["\n", "\r", "\t"], '', $fileContent);
                }
                
                $content .= $fileContent . "\n";
            }
        }
        
        // 保存到缓存
        file_put_contents($cacheFile, $content, LOCK_EX);
        
        return '/storage/framework/assets/' . $cacheKey . '.css';
    }
    
    /**
     * 合并 JavaScript 文件
     */
    public static function combineJs(string $name): string
    {
        $config = self::getConfig();
        $files = $config['js'][$name] ?? [];
        
        if (empty($files)) {
            return '';
        }
        
        $cacheKey = self::getCacheKey('js', $name);
        $cacheFile = self::getCacheDir() . $cacheKey . '.js';
        
        // 检查缓存
        if (file_exists($cacheFile)) {
            return '/storage/framework/assets/' . $cacheKey . '.js';
        }
        
        // 合并文件
        $content = '';
        foreach ($files as $file) {
            $fullPath = __DIR__ . '/../../public' . $file;
            if (file_exists($fullPath)) {
                $fileContent = file_get_contents($fullPath);
                
                // 移除单行注释
                if ($config['minify']['remove_comments'] ?? true) {
                    $fileContent = preg_replace('/\/\/.*$/m', '', $fileContent);
                }
                
                // 移除多行注释
                if ($config['minify']['remove_comments'] ?? true) {
                    $fileContent = preg_replace('/\/\*[\s\S]*?\*\//', '', $fileContent);
                }
                
                // 移除多余空白
                if ($config['minify']['remove_whitespace'] ?? true) {
                    $fileContent = preg_replace('/\s+/', ' ', $fileContent);
                    $fileContent = str_replace(["\n", "\r", "\t"], '', $fileContent);
                }
                
                $content .= $fileContent . ";\n";
            }
        }
        
        // 保存到缓存
        file_put_contents($cacheFile, $content, LOCK_EX);
        
        return '/storage/framework/assets/' . $cacheKey . '.js';
    }
    
    /**
     * 清除资源缓存
     */
    public static function clearCache(): bool
    {
        $cacheDir = self::getCacheDir();
        $files = glob($cacheDir . '*');
        
        if ($files === false) {
            return false;
        }
        
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        
        return true;
    }
}
