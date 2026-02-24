<?php

declare(strict_types=1);

namespace Core\Api;

/**
 * API版本管理器
 * 支持多版本API共存、版本路由、版本废弃通知
 * 
 * @package Core\Api
 * @version 1.0.0
 */
class ApiVersionManager
{
    /**
     * 支持的API版本列表
     */
    private array $versions = [];

    /**
     * 当前默认版本
     */
    private string $currentVersion = 'v1';

    /**
     * 版本配置
     */
    private array $versionConfig = [];

    /**
     * 版本路由映射
     */
    private array $routeMap = [];

    /**
     * 构造函数
     *
     * @param array $config 配置
     */
    public function __construct(array $config = [])
    {
        $this->currentVersion = $config['current_version'] ?? 'v1';
        $this->versions = $config['versions'] ?? ['v1'];
        $this->versionConfig = $config['version_config'] ?? [];
    }

    /**
     * 注册API版本
     *
     * @param string $version 版本号 (如 v1, v2)
     * @param array $config 版本配置
     * @return static
     */
    public function registerVersion(string $version, array $config = []): static
    {
        if (!in_array($version, $this->versions, true)) {
            $this->versions[] = $version;
        }

        $this->versionConfig[$version] = array_merge([
            'deprecated' => false,
            'sunset_date' => null,
            'migration_guide' => null,
            'controllers_namespace' => "Api\\{$version}\\Controllers",
            'middleware' => [],
        ], $config);

        return $this;
    }

    /**
     * 获取当前版本
     *
     * @return string
     */
    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }

    /**
     * 设置当前版本
     *
     * @param string $version 版本号
     * @return static
     */
    public function setCurrentVersion(string $version): static
    {
        if (!in_array($version, $this->versions, true)) {
            throw new \InvalidArgumentException("Unsupported API version: {$version}");
        }

        $this->currentVersion = $version;
        return $this;
    }

    /**
     * 获取所有支持的版本
     *
     * @return array
     */
    public function getSupportedVersions(): array
    {
        return $this->versions;
    }

    /**
     * 检查版本是否支持
     *
     * @param string $version 版本号
     * @return bool
     */
    public function isVersionSupported(string $version): bool
    {
        return in_array($version, $this->versions, true);
    }

    /**
     * 检查版本是否已废弃
     *
     * @param string $version 版本号
     * @return bool
     */
    public function isVersionDeprecated(string $version): bool
    {
        return $this->versionConfig[$version]['deprecated'] ?? false;
    }

    /**
     * 获取版本废弃信息
     *
     * @param string $version 版本号
     * @return array|null
     */
    public function getDeprecationInfo(string $version): ?array
    {
        if (!$this->isVersionDeprecated($version)) {
            return null;
        }

        $config = $this->versionConfig[$version] ?? [];

        return [
            'deprecated' => true,
            'sunset_date' => $config['sunset_date'] ?? null,
            'migration_guide' => $config['migration_guide'] ?? null,
            'message' => $config['message'] ?? "API version {$version} is deprecated",
        ];
    }

    /**
     * 从请求中解析版本
     *
     * @param string $uri URI路径
     * @return array [version, path_without_version]
     */
    public function parseVersionFromUri(string $uri): array
    {
        // 移除前导斜杠
        $uri = ltrim($uri, '/');

        // 匹配版本模式: /api/v1/... 或 /v1/...
        if (preg_match('#^(?:api/)?(v\d+)/?(.*)$#i', $uri, $matches)) {
            $version = strtolower($matches[1]);
            $path = $matches[2] ?? '';

            if ($this->isVersionSupported($version)) {
                return [$version, $path];
            }
        }

        // 返回默认版本
        return [$this->currentVersion, $uri];
    }

    /**
     * 从请求头解析版本
     *
     * @param array $headers 请求头
     * @return string|null
     */
    public function parseVersionFromHeaders(array $headers): ?string
    {
        // 检查 Accept: application/json; version=v1
        $accept = $headers['Accept'] ?? $headers['HTTP_ACCEPT'] ?? '';
        if (preg_match('/version=(v\d+)/i', $accept, $matches)) {
            $version = strtolower($matches[1]);
            if ($this->isVersionSupported($version)) {
                return $version;
            }
        }

        // 检查自定义头 X-API-Version: v1
        $apiVersion = $headers['X-API-Version'] ?? $headers['HTTP_X_API_VERSION'] ?? '';
        if (preg_match('/^(v\d+)$/i', $apiVersion, $matches)) {
            $version = strtolower($matches[1]);
            if ($this->isVersionSupported($version)) {
                return $version;
            }
        }

        return null;
    }

    /**
     * 获取版本控制器命名空间
     *
     * @param string $version 版本号
     * @return string
     */
    public function getControllerNamespace(string $version): string
    {
        return $this->versionConfig[$version]['controllers_namespace'] ?? "Api\\{$version}\\Controllers";
    }

    /**
     * 获取版本中间件
     *
     * @param string $version 版本号
     * @return array
     */
    public function getVersionMiddleware(string $version): array
    {
        return $this->versionConfig[$version]['middleware'] ?? [];
    }

    /**
     * 注册版本路由
     *
     * @param string $version 版本号
     * @param string $path 路径
     * @param string $controller 控制器
     * @param string $action 方法
     * @return static
     */
    public function registerRoute(string $version, string $path, string $controller, string $action): static
    {
        $this->routeMap[$version][$path] = [
            'controller' => $controller,
            'action' => $action,
        ];

        return $this;
    }

    /**
     * 获取版本路由
     *
     * @param string $version 版本号
     * @param string $path 路径
     * @return array|null
     */
    public function getRoute(string $version, string $path): ?array
    {
        return $this->routeMap[$version][$path] ?? null;
    }

    /**
     * 生成版本化URL
     *
     * @param string $path 路径
     * @param string|null $version 版本号（默认当前版本）
     * @return string
     */
    public function url(string $path, ?string $version = null): string
    {
        $version = $version ?? $this->currentVersion;
        $path = ltrim($path, '/');

        return "/api/{$version}/{$path}";
    }

    /**
     * 添加废弃响应头
     *
     * @param string $version 版本号
     * @return void
     */
    public function addDeprecationHeaders(string $version): void
    {
        $info = $this->getDeprecationInfo($version);

        if ($info === null) {
            return;
        }

        header("X-API-Deprecated: true");
        header("X-API-Sunset: {$info['sunset_date']}");

        if ($info['migration_guide']) {
            header("Link: <{$info['migration_guide']}>; rel=\"deprecation-guide\"");
        }
    }

    /**
     * 获取API版本信息响应
     *
     * @return array
     */
    public function getVersionInfo(): array
    {
        $versions = [];

        foreach ($this->versions as $version) {
            $versions[$version] = [
                'current' => $version === $this->currentVersion,
                'deprecated' => $this->isVersionDeprecated($version),
                'sunset_date' => $this->versionConfig[$version]['sunset_date'] ?? null,
            ];
        }

        return [
            'current_version' => $this->currentVersion,
            'supported_versions' => $versions,
        ];
    }

    /**
     * 创建版本路由组
     *
     * @param \Core\Routing\Router $router 路由器
     * @param string $version 版本号
     * @param callable $callback 回调函数
     * @return void
     */
    public function createVersionRouteGroup(\Core\Routing\Router $router, string $version, callable $callback): void
    {
        $middleware = $this->getVersionMiddleware($version);

        $router->group([
            'prefix' => "api/{$version}",
            'middleware' => $middleware,
        ], function ($group) use ($callback, $version) {
            $callback($group, $version);
        });
    }
}
