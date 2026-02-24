<?php

declare(strict_types=1);

namespace Core;

/**
 * 插件基类
 *
 * 所有插件必须继承此类，实现标准化的插件接口
 * 基于智简魔方财务系统插件架构规范
 */
abstract class Plugin
{
    /**
     * 插件信息
     * @var array<string, mixed>
     */
    protected array $info = [];

    /**
     * 插件配置
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * 插件目录
     * @var string
     */
    protected string $pluginDir = '';

    /**
     * 插件类型
     * @var string
     */
    protected string $type = '';

    /**
     * 依赖插件列表
     * @var array<int, string>
     */
    protected array $dependencies = [];

    /**
     * 构造函数
     *
     * @param array $config 插件配置
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->pluginDir = $this->getPluginDirectory();
    }

    /**
     * 获取插件目录
     *
     * @return string
     */
    protected function getPluginDirectory(): string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

    /**
     * 安装插件
     * 创建数据库表、初始化配置等
     *
     * @return bool
     */
    abstract public function install(): bool;

    /**
     * 卸载插件
     * 删除数据库表、清理配置等
     *
     * @return bool
     */
    abstract public function uninstall(): bool;

    /**
     * 激活插件
     * 注册钩子、路由等
     *
     * @return bool
     */
    public function activate(): bool
    {
        $this->registerHooks();
        $this->registerRoutes();
        return true;
    }

    /**
     * 停用插件
     * 注销钩子、路由等
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        $this->unregisterHooks();
        $this->unregisterRoutes();
        return true;
    }

    /**
     * 获取插件信息
     *
     * @return array<string, mixed>
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * 获取插件配置
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 保存插件配置
     *
     * @param array $config 配置数据
     * @return void
     */
    public function saveConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 获取插件类型
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * 获取依赖列表
     *
     * @return array<int, string>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * 注册钩子
     * 子类可以重写此方法注册自定义钩子
     *
     * @return void
     */
    protected function registerHooks(): void
    {
        // 子类实现
    }

    /**
     * 注销钩子
     * 子类可以重写此方法注销自定义钩子
     *
     * @return void
     */
    protected function unregisterHooks(): void
    {
        // 子类实现
    }

    /**
     * 注册路由
     * 子类可以重写此方法注册自定义路由
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        // 子类实现
    }

    /**
     * 注销路由
     * 子类可以重写此方法注销自定义路由
     *
     * @return void
     */
    protected function unregisterRoutes(): void
    {
        // 子类实现
    }

    /**
     * 获取插件权限
     * 子类可以重写此方法定义权限
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPermissions(): array
    {
        return [];
    }

    /**
     * 获取菜单项
     * 子类可以重写此方法定义菜单
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMenuItems(): array
    {
        return [];
    }

    /**
     * 获取钩子列表
     * 子类可以重写此方法定义钩子
     *
     * @return array<string, callable>
     */
    public function getHooks(): array
    {
        return [];
    }

    /**
     * 获取路由列表
     * 子类可以重写此方法定义路由
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoutes(): array
    {
        return [];
    }

    /**
     * 获取数据库表列表
     * 子类可以重写此方法定义插件表
     *
     * @return array<int, string>
     */
    public function getTables(): array
    {
        return [];
    }

    /**
     * 执行SQL文件
     *
     * @param string $sqlFile SQL文件路径
     * @return bool
     */
    protected function executeSqlFile(string $sqlFile): bool
    {
        if (!file_exists($sqlFile)) {
            return false;
        }

        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            return false;
        }

        // 分割SQL语句
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    Database::execute($statement);
                } catch (\Exception $e) {
                    error_log("SQL执行失败: " . $e->getMessage());
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 创建目录
     *
     * @param string $dir 目录路径
     * @return bool
     */
    protected function createDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return @mkdir($dir, 0755, true);
        }
        return true;
    }

    /**
     * 删除目录
     *
     * @param string $dir 目录路径
     * @return bool
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }

        return @rmdir($dir);
    }
}
