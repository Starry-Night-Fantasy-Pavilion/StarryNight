<?php

declare(strict_types=1);

namespace app\services;

/**
 * 依赖管理器
 * 
 * 管理插件之间的依赖关系，支持依赖解析和循环依赖检测
 * PHP 8.0+ 兼容版本
 */
class DependencyManager
{
    /**
     * @var PluginManager 插件管理器
     */
    private PluginManager $pluginManager;

    /**
     * @var array<string, array<int, string>> 依赖缓存
     */
    private array $dependencyCache = [];

    /**
     * 构造函数
     *
     * @param PluginManager $pluginManager
     */
    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    /**
     * 解析插件依赖
     *
     * @param string $pluginId 插件ID
     * @return array<int, string> 解析后的依赖列表
     * @throws \Exception
     */
    public function resolveDependencies(string $pluginId): array
    {
        // 检查缓存
        if (isset($this->dependencyCache[$pluginId])) {
            return $this->dependencyCache[$pluginId];
        }

        $dependencies = $this->getPluginDependencies($pluginId);
        $resolved = [];
        $unresolved = $dependencies;

        while (!empty($unresolved)) {
            $dependency = array_shift($unresolved);

            if (in_array($dependency, $resolved, true)) {
                continue;
            }

            if (!$this->isPluginInstalled($dependency)) {
                throw new \Exception("依赖插件未安装: {$dependency}");
            }

            $resolved[] = $dependency;

            // 获取依赖插件的依赖
            $depDependencies = $this->getPluginDependencies($dependency);
            $unresolved = array_merge($unresolved, $depDependencies);
        }

        // 缓存结果
        $this->dependencyCache[$pluginId] = $resolved;

        return $resolved;
    }

    /**
     * 获取插件的依赖列表
     *
     * @param string $pluginId 插件ID
     * @return array<int, string>
     */
    public function getPluginDependencies(string $pluginId): array
    {
        $plugins = $this->pluginManager->getPlugins();

        if (!isset($plugins[$pluginId])) {
            // 尝试从数据库获取
            $plugin = Database::queryOne(
                "SELECT dependencies FROM " . Database::prefix() . "admin_plugins WHERE plugin_id = ?",
                [$pluginId]
            );

            if ($plugin && !empty($plugin['dependencies'])) {
                $dependencies = json_decode($plugin['dependencies'], true);
                return is_array($dependencies) ? $dependencies : [];
            }

            return [];
        }

        $config = $plugins[$pluginId]['config'] ?? [];
        $dependencies = $config['dependencies'] ?? [];

        return is_array($dependencies) ? $dependencies : [];
    }

    /**
     * 检查插件是否已安装
     *
     * @param string $pluginId 插件ID
     * @return bool
     */
    public function isPluginInstalled(string $pluginId): bool
    {
        $plugins = $this->pluginManager->getPlugins();

        if (isset($plugins[$pluginId])) {
            return true;
        }

        // 检查数据库
        $result = Database::queryOne(
            "SELECT COUNT(*) as count FROM " . Database::prefix() . "admin_plugins WHERE plugin_id = ? AND status = 'enabled'",
            [$pluginId]
        );

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * 检查循环依赖
     *
     * @param string $pluginId 插件ID
     * @return bool 是否存在循环依赖
     */
    public function checkCircularDependencies(string $pluginId): bool
    {
        $visited = [];
        $recursionStack = [];

        return $this->checkCircular($pluginId, $visited, $recursionStack);
    }

    /**
     * 递归检查循环依赖
     *
     * @param string $pluginId 插件ID
     * @param array<string> $visited 已访问的插件
     * @param array<string> $recursionStack 递归栈
     * @return bool
     */
    private function checkCircular(string $pluginId, array &$visited, array &$recursionStack): bool
    {
        if (in_array($pluginId, $recursionStack, true)) {
            return true;
        }

        if (in_array($pluginId, $visited, true)) {
            return false;
        }

        $visited[] = $pluginId;
        $recursionStack[] = $pluginId;

        $dependencies = $this->getPluginDependencies($pluginId);

        foreach ($dependencies as $dependency) {
            if ($this->checkCircular($dependency, $visited, $recursionStack)) {
                return true;
            }
        }

        array_pop($recursionStack);
        return false;
    }

    /**
     * 获取依赖树
     *
     * @param string $pluginId 插件ID
     * @return array<string, mixed>
     */
    public function getDependencyTree(string $pluginId): array
    {
        $tree = [
            'plugin' => $pluginId,
            'dependencies' => []
        ];

        $dependencies = $this->getPluginDependencies($pluginId);

        foreach ($dependencies as $dependency) {
            $tree['dependencies'][] = $this->getDependencyTree($dependency);
        }

        return $tree;
    }

    /**
     * 检查所有依赖是否满足
     *
     * @param string $pluginId 插件ID
     * @return array{satisfied: bool, missing: array<int, string>}
     */
    public function checkDependenciesSatisfied(string $pluginId): array
    {
        $dependencies = $this->getPluginDependencies($pluginId);
        $missing = [];

        foreach ($dependencies as $dependency) {
            if (!$this->isPluginInstalled($dependency)) {
                $missing[] = $dependency;
            }
        }

        return [
            'satisfied' => empty($missing),
            'missing' => $missing
        ];
    }

    /**
     * 清除依赖缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->dependencyCache = [];
    }

    /**
     * 获取所有已安装插件的依赖关系
     *
     * @return array<string, array<int, string>>
     */
    public function getAllDependencies(): array
    {
        $plugins = $this->pluginManager->getPlugins();
        $allDeps = [];

        foreach (array_keys($plugins) as $pluginId) {
            $allDeps[$pluginId] = $this->getPluginDependencies($pluginId);
        }

        return $allDeps;
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
