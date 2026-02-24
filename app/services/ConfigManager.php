<?php

declare(strict_types=1);

namespace app\services;

/**
 * 配置管理器
 * 
 * 处理插件和系统的配置存储和检索
 * PHP 8.0+ 兼容版本
 */
class ConfigManager
{
    /**
     * @var string 配置文件存储目录
     */
    private string $configPath;

    /**
     * @var array<string, array> 配置缓存
     */
    private array $cache = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->configPath = __DIR__ . '/../../storage/framework/configs/';
        $this->ensureDirectoryExists();
    }

    /**
     * 确保配置目录存在
     *
     * @return void
     */
    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->configPath)) {
            @mkdir($this->configPath, 0755, true);
        }
    }

    /**
     * 保存配置
     *
     * @param string $name 配置名称
     * @param array $config 配置数据
     * @return bool
     */
    public function save(string $name, array $config): bool
    {
        $file = $this->configPath . $name . '.json';
        $jsonContent = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($jsonContent === false) {
            return false;
        }

        $result = file_put_contents($file, $jsonContent, LOCK_EX) !== false;

        if ($result) {
            $this->cache[$name] = $config;
        }

        return $result;
    }

    /**
     * 加载配置
     *
     * @param string $name 配置名称
     * @param array $default 默认配置
     * @return array
     */
    public function load(string $name, array $default = []): array
    {
        // 检查缓存
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $file = $this->configPath . $name . '.json';

        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return $default;
        }

        $config = json_decode($content, true);

        if (!is_array($config)) {
            return $default;
        }

        // 存入缓存
        $this->cache[$name] = $config;

        return $config;
    }

    /**
     * 获取配置项
     *
     * @param string $name 配置名称
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $name, string $key, $default = null)
    {
        $config = $this->load($name);
        return $config[$key] ?? $default;
    }

    /**
     * 设置配置项
     *
     * @param string $name 配置名称
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @return bool
     */
    public function set(string $name, string $key, $value): bool
    {
        $config = $this->load($name);
        $config[$key] = $value;
        return $this->save($name, $config);
    }

    /**
     * 删除配置
     *
     * @param string $name 配置名称
     * @return bool
     */
    public function delete(string $name): bool
    {
        $file = $this->configPath . $name . '.json';

        if (file_exists($file)) {
            $result = unlink($file);
            if ($result) {
                unset($this->cache[$name]);
            }
            return $result;
        }

        return true;
    }

    /**
     * 检查配置是否存在
     *
     * @param string $name 配置名称
     * @return bool
     */
    public function exists(string $name): bool
    {
        $file = $this->configPath . $name . '.json';
        return file_exists($file);
    }

    /**
     * 获取所有配置名称
     *
     * @return array<int, string>
     */
    public function list(): array
    {
        $configs = [];
        $files = glob($this->configPath . '*.json');

        if ($files === false) {
            return $configs;
        }

        foreach ($files as $file) {
            $configs[] = basename($file, '.json');
        }

        return $configs;
    }

    /**
     * 清除配置缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * 批量保存配置
     *
     * @param array<string, array> $configs 配置数组
     * @return array<string, bool>
     */
    public function saveBatch(array $configs): array
    {
        $results = [];
        foreach ($configs as $name => $config) {
            $results[$name] = $this->save($name, $config);
        }
        return $results;
    }

    /**
     * 统一错误处理
     */
    protected function handleError(\Exception $e, $operation = '') {
        $errorMessage = $operation ? $operation . '失败: ' . $e->getMessage() : $e->getMessage();
        
        // 记录错误日志
        error_log('Service Error: ' . $errorMessage);
        
        // 抛出自定义异常
        throw new \Exception($errorMessage, $e->getCode(), $e);
    }
}
