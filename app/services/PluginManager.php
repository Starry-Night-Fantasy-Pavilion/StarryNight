<?php

namespace app\services;

use Core\Plugin;
use Core\OAuthPlugin;
use Core\GatewayPlugin;
use Core\CertificationPlugin;

/**
 * 插件管理器
 *
 * 负责发现、加载和管理所有应用插件
 * 集成事件系统和依赖管理功能
 * 支持智简魔方财务系统插件架构规范
 */
class PluginManager
{
    /**
     * @var array<string, array{instance: Plugin, config: array, path: string, type: string}> 存储所有已加载和实例化的插件
     */
    protected array $plugins = [];

    /**
     * @var array<string, array<int, callable>> 存储所有已注册的钩子及其回调
     */
    protected array $hooks = [];

    /**
     * @var PluginMigrationService 插件数据库迁移服务
     */
    protected PluginMigrationService $migrationService;

    /**
     * @var DependencyManager 依赖管理器
     */
    protected DependencyManager $dependencyManager;

    /**
     * @var ConfigManager 配置管理器
     */
    protected ConfigManager $configManager;

    /**
     * @var FrontendMapper 前端映射器
     */
    protected FrontendMapper $frontendMapper;

    /**
     * @var array<string, string> 插件类型目录映射
     */
    protected array $pluginDirs = [
        'oauth' => 'public/plugins/oauth/',
        'gateways' => 'public/plugins/gateways/',
        'certification' => 'public/plugins/certification/',
        'addons' => 'public/plugins/addons/',
        'notification' => 'public/plugins/notification/',
        'payment' => 'public/plugins/payment/'
    ];

    /**
     * 构造函数
     *
     * 初始化插件管理器并自动发现插件。
     */
    public function __construct()
    {
        // 旧架构模式：不再在构造函数中强制依赖数据库迁移服务，
        // 避免因为数据库未配置或权限问题导致整个插件系统不可用。
        // 如需使用插件数据库自动化管理，请单独通过 PluginMigrationService 调用。
        $this->migrationService = new PluginMigrationService();
        $this->configManager = new ConfigManager();
        $this->discoverPlugins();

        // 初始化依赖管理器（需要在插件发现之后）
        $this->dependencyManager = new DependencyManager($this);
    }

    /**
     * 设置前端映射器
     *
     * @param FrontendMapper $frontendMapper
     * @return void
     */
    public function setFrontendMapper(FrontendMapper $frontendMapper): void
    {
        $this->frontendMapper = $frontendMapper;
    }

    /**
     * 获取前端映射器
     *
     * @return FrontendMapper|null
     */
    public function getFrontendMapper(): ?FrontendMapper
    {
        return $this->frontendMapper ?? null;
    }

    /**
     * 发现并加载所有已启用的插件
     *
     * 此方法会扫描 `public/plugins` 目录下的所有子目录，查找所有插件。
     * 对于每个插件，它会读取 `plugin.json` 配置文件。
     * 如果 `status` 设置为 `enabled`，它将根据 `plugin.json` 中的 `namespace` 字段
     * 构造主类名，并实例化该类，最后将其存储在 `$plugins` 属性中。
     *
     * @return void
     */
    public function discoverPlugins()
    {
        // 旧架构模式下，仅基于文件系统扫描插件，不强制执行数据库自动注册，
        // 避免因数据库连接失败导致插件发现中断。
        $plugins_base_dir = realpath(__DIR__ . '/../../public/plugins');

        if (!$plugins_base_dir || !is_dir($plugins_base_dir)) {
            return;
        }

        // 递归扫描所有插件目录
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($plugins_base_dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            if ($fileInfo->getFilename() !== 'plugin.json') {
                continue;
            }

            $config_file = $fileInfo->getPathname();
            if (!is_readable($config_file)) {
                continue;
            }

            $config = json_decode(file_get_contents($config_file), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            $installed = $config['installed'] ?? false;
            $installedOk = ($installed === true || $installed === 1 || $installed === '1' || $installed === 'true');
            if (!$installedOk) {
                continue;
            }

            $status = $config['status'] ?? 'disabled';
            if ($status !== 'enabled') {
                continue;
            }

            $plugin_dir = realpath(dirname($config_file));
            if (!$plugin_dir) {
                continue;
            }
            
            // 确定主类文件名，默认为 Plugin.php
            $main_class_filename = $config['main_class'] ?? 'Plugin.php';
            $main_class_path = $plugin_dir . '/' . $main_class_filename;

            if (!is_readable($main_class_path)) {
                continue;
            }

            // 使用 plugin.json 中的 namespace，如果没有则尝试从路径推断
            $namespace = $config['namespace'] ?? null;
            if (!$namespace) {
                // 从路径推断命名空间（向后兼容）
                $relativePath = str_replace($plugins_base_dir . DIRECTORY_SEPARATOR, '', $plugin_dir);
                $pathParts = explode(DIRECTORY_SEPARATOR, $relativePath);
                $namespace = 'plugins\\' . implode('\\', $pathParts);
            }

            $class_name = $namespace . '\\' . pathinfo($main_class_filename, PATHINFO_FILENAME);

            // 只有当类不存在时才加载文件，避免重复声明错误
            if (!class_exists($class_name)) {
                require_once $main_class_path;
            }

            if (class_exists($class_name)) {
                $plugin_id = $config['plugin_id'] ?? $config['id'] ?? basename($plugin_dir);
                $this->plugins[$plugin_id] = [
                    'instance' => new $class_name(),
                    'config' => $config,
                    'path' => $plugin_dir,
                ];
            }
        }
    }

    /**
     * 获取所有已加载的插件实例和配置
     *
     * @return array 返回存储所有插件数据的数组
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * 注册一个钩子（Hook）
     *
     * 允许插件或其他代码将一个回调函数挂载到一个特定的事件点（钩子）。
     *
     * @param string $hook 钩子的名称 (例如: 'before_render_header')
     * @param callable $callback 当钩子被触发时要执行的回调函数
     * @return void
     */
    public function registerHook($hook, $callback)
    {
        if (!isset($this->hooks[$hook])) {
            $this->hooks[$hook] = [];
        }
        $this->hooks[$hook][] = $callback;
    }

    /**
     * 触发一个钩子
     *
     * 执行所有注册到特定钩子的回调函数，并向它们传递参数。
     *
     * @param string $hook 要触发的钩子的名称
     * @param mixed ...$args 传递给回调函数的一个或多个参数
     * @return void
     */
    public function triggerHook($hook, ...$args)
    {
        if (isset($this->hooks[$hook])) {
            foreach ($this->hooks[$hook] as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    /**
     * 安装插件
     *
     * @param string $pluginPath 插件路径
     * @return array 安装结果
     */
    public function installPlugin($pluginPath)
    {
        // 注册插件到数据库
        $registerResult = $this->migrationService->registerPlugin($pluginPath);
        if (!$registerResult['success']) {
            return $registerResult;
        }

        $pluginId = $registerResult['plugin_id'];

        // 安装插件数据库
        $dbResult = $this->migrationService->installPluginDatabase($pluginId);
        if (!$dbResult['success']) {
            return $dbResult;
        }

        // 更新插件状态为已启用
        $this->updatePluginStatus($pluginId, 'enabled');

        return ['success' => true, 'message' => '插件安装成功', 'plugin_id' => $pluginId];
    }

    /**
     * 卸载插件
     *
     * @param string $pluginId 插件ID
     * @return array 卸载结果
     */
    public function uninstallPlugin($pluginId)
    {
        // 卸载插件数据库
        $dbResult = $this->migrationService->uninstallPluginDatabase($pluginId);
        if (!$dbResult['success']) {
            return $dbResult;
        }

        // 更新插件状态为已禁用
        $this->updatePluginStatus($pluginId, 'disabled');

        return ['success' => true, 'message' => '插件卸载成功'];
    }

    /**
     * 更新插件状态
     *
     * @param string $pluginId 插件ID
     * @param string $status 状态
     * @return bool 更新结果
     */
    protected function updatePluginStatus($pluginId, $status)
    {
        return Database::update('admin_plugins', ['status' => $status], 'plugin_id = ?', [$pluginId]);
    }

    /**
     * 获取插件数据库表信息
     *
     * @param string $pluginId 插件ID
     * @return array 表信息
     */
    public function getPluginTables($pluginId)
    {
        return $this->migrationService->getPluginTables($pluginId);
    }

    /**
     * 获取插件迁移历史
     *
     * @param string $pluginId 插件ID
     * @return array 迁移历史
     */
    public function getPluginMigrations($pluginId)
    {
        return $this->migrationService->getPluginMigrations($pluginId);
    }

    /**
     * 获取所有已注册的插件信息
     *
     * @return array 插件信息
     */
    public function getAllRegisteredPlugins()
    {
        return Database::queryAll("SELECT * FROM " . Database::prefix() . "admin_plugins ORDER BY type, name");
    }

    /**
     * 批量处理所有插件的数据库安装
     *
     * @return array 处理结果
     */
    public function batchInstallPluginDatabases()
    {
        $plugins = Database::queryAll(
            "SELECT plugin_id FROM " . Database::prefix() . "admin_plugins WHERE status = 'installed' AND install_sql_path IS NOT NULL"
        );

        $results = [];
        foreach ($plugins as $plugin) {
            $pluginId = $plugin['plugin_id'];
            $result = $this->migrationService->installPluginDatabase($pluginId);
            $results[$pluginId] = $result;
            
            if ($result['success']) {
                $this->updatePluginStatus($pluginId, 'enabled');
            }
        }

        return $results;
    }

    /**
     * 获取依赖管理器
     *
     * @return DependencyManager
     */
    public function getDependencyManager(): DependencyManager
    {
        return $this->dependencyManager;
    }

    /**
     * 获取配置管理器
     *
     * @return ConfigManager
     */
    public function getConfigManager(): ConfigManager
    {
        return $this->configManager;
    }

    /**
     * 使用事件系统触发事件
     *
     * @param string $event 事件名称
     * @param array $data 事件数据
     * @return void
     */
    public function triggerEvent(string $event, array $data = []): void
    {
        // 添加插件上下文到事件数据
        $data['plugin_manager'] = $this;
        EventSystem::dispatch($event, $data);
    }

    /**
     * 注册插件事件监听器
     *
     * @param string $event 事件名称
     * @param callable $callback 回调函数
     * @param int $priority 优先级
     * @return void
     */
    public function on(string $event, callable $callback, int $priority = 10): void
    {
        EventSystem::listen($event, $callback, $priority);
    }

    /**
     * 检查插件依赖是否满足
     *
     * @param string $pluginId 插件ID
     * @return array ['satisfied' => bool, 'missing' => array]
     */
    public function checkPluginDependencies(string $pluginId): array
    {
        return $this->dependencyManager->checkDependenciesSatisfied($pluginId);
    }

    /**
     * 获取插件配置
     *
     * @param string $pluginId 插件ID
     * @return array
     */
    public function getPluginConfig(string $pluginId): array
    {
        return $this->configManager->load($pluginId);
    }

    /**
     * 保存插件配置
     *
     * @param string $pluginId 插件ID
     * @param array $config 配置数据
     * @return bool
     */
    public function savePluginConfig(string $pluginId, array $config): bool
    {
        return $this->configManager->save($pluginId, $config);
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
