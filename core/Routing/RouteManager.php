<?php

declare(strict_types=1);

namespace Core\Routing;

use Core\Routing\Router;
use app\services\CacheService;

/**
 * 路由管理器
 * 统一管理应用路由，支持缓存和分组
 */
class RouteManager
{
    /**
     * 路由器实例
     */
    protected Router $router;

    /**
     * 路由配置
     */
    protected array $config = [];

    /**
     * 是否启用缓存
     */
    protected bool $cacheEnabled = true;

    /**
     * 缓存键
     */
    protected const CACHE_KEY = 'routes:compiled';

    /**
     * 缓存时间（秒）
     */
    protected const CACHE_TTL = 86400;

    /**
     * 构造函数
     *
     * @param Router $router 路由器实例
     * @param array $config 路由配置
     */
    public function __construct(Router $router, array $config = [])
    {
        $this->router = $router;
        $this->config = $config;
    }

    /**
     * 加载路由配置
     *
     * @param string|null $configFile 配置文件路径
     * @return static
     */
    public function load(?string $configFile = null): static
    {
        $configFile = $configFile ?? dirname(__DIR__, 2) . '/config/routes.php';

        if (file_exists($configFile)) {
            $this->config = require $configFile;
        }

        return $this;
    }

    /**
     * 注册所有路由
     *
     * @return static
     */
    public function register(): static
    {
        // 尝试从缓存加载
        if ($this->cacheEnabled && $cached = $this->getFromCache()) {
            $this->restoreFromCache($cached);
            return $this;
        }

        // 注册路由
        foreach ($this->config as $group) {
            $this->registerGroup($group);
        }

        // 缓存路由
        if ($this->cacheEnabled) {
            $this->saveToCache();
        }

        return $this;
    }

    /**
     * 注册路由组
     *
     * @param array $group 路由组配置
     * @return void
     */
    protected function registerGroup(array $group): void
    {
        $prefix = $group['prefix'] ?? '';
        $middleware = $group['middleware'] ?? [];
        $routes = $group['routes'] ?? [];

        foreach ($routes as $route) {
            [$method, $path, $handler] = $route;
            $fullPath = $prefix . $path;

            // 规范化路径
            $fullPath = $this->normalizePath($fullPath);

            // 添加路由
            $routeObj = $this->router->addRoute($method, $fullPath, $handler);

            // 添加中间件
            if (!empty($middleware)) {
                $routeObj->middleware($middleware);
            }

            // 设置路由名称（如果有）
            if (isset($route[3])) {
                $routeObj->name($route[3]);
            }
        }
    }

    /**
     * 规范化路径
     *
     * @param string $path 路径
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        // 移除重复的斜杠
        $path = preg_replace('#/+#', '/', $path);

        // 确保以/开头
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $path;
    }

    /**
     * 从缓存获取路由
     *
     * @return array|null
     */
    protected function getFromCache(): ?array
    {
        try {
            return CacheService::get(self::CACHE_KEY);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 保存路由到缓存
     *
     * @return bool
     */
    protected function saveToCache(): bool
    {
        try {
            $data = $this->serializeRoutes();
            return CacheService::set(self::CACHE_KEY, $data, self::CACHE_TTL);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 序列化路由
     *
     * @return array
     */
    protected function serializeRoutes(): array
    {
        $routes = [];

        foreach ($this->router->getRoutes() as $method => $methodRoutes) {
            foreach ($methodRoutes as $route) {
                $routes[] = [
                    'method' => $method,
                    'path' => $route->path,
                    'handler' => $route->handler,
                    'middleware' => $route->middleware,
                    'name' => $route->name,
                    'constraints' => $route->constraints,
                ];
            }
        }

        return $routes;
    }

    /**
     * 从缓存恢复路由
     *
     * @param array $cached 缓存数据
     * @return void
     */
    protected function restoreFromCache(array $cached): void
    {
        foreach ($cached as $routeData) {
            $route = $this->router->addRoute(
                $routeData['method'],
                $routeData['path'],
                $routeData['handler']
            );

            if (!empty($routeData['middleware'])) {
                $route->middleware($routeData['middleware']);
            }

            if (!empty($routeData['name'])) {
                $route->name($routeData['name']);
            }

            if (!empty($routeData['constraints'])) {
                $route->where($routeData['constraints']);
            }
        }
    }

    /**
     * 清除路由缓存
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        return CacheService::delete(self::CACHE_KEY);
    }

    /**
     * 禁用缓存
     *
     * @return static
     */
    public function withoutCache(): static
    {
        $this->cacheEnabled = false;
        return $this;
    }

    /**
     * 启用缓存
     *
     * @return static
     */
    public function withCache(): static
    {
        $this->cacheEnabled = true;
        return $this;
    }

    /**
     * 获取路由器
     *
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * 分发请求
     *
     * @return array
     */
    public function dispatch(): array
    {
        return $this->router->dispatch();
    }

    /**
     * 生成URL
     *
     * @param string $name 路由名称
     * @param array $params 参数
     * @return string
     */
    public function url(string $name, array $params = []): string
    {
        return $this->router->url($name, $params);
    }

    /**
     * 获取所有路由
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->router->getRoutes();
    }

    /**
     * 匹配当前请求
     *
     * @param string|null $method HTTP方法
     * @param string|null $uri URI
     * @return array
     */
    public function matchRequest(?string $method = null, ?string $uri = null): array
    {
        return $this->router->dispatch($method, $uri);
    }

    /**
     * 添加API资源路由
     *
     * @param string $name 资源名称
     * @param string $controller 控制器类
     * @param array $options 选项
     * @return static
     */
    public function apiResource(string $name, string $controller, array $options = []): static
    {
        $this->router->apiResource($name, $controller, $options);
        return $this;
    }

    /**
     * 添加资源路由
     *
     * @param string $name 资源名称
     * @param string $controller 控制器类
     * @param array $options 选项
     * @return static
     */
    public function resource(string $name, string $controller, array $options = []): static
    {
        $this->router->resource($name, $controller, $options);
        return $this;
    }

    /**
     * 创建路由组
     *
     * @param array $attributes 属性
     * @param callable $callback 回调
     * @return static
     */
    public function group(array $attributes, callable $callback): static
    {
        $this->router->group($attributes, $callback);
        return $this;
    }

    /**
     * 添加GET路由
     *
     * @param string $path 路径
     * @param mixed $handler 处理器
     * @return Route
     */
    public function get(string $path, mixed $handler): Route
    {
        return $this->router->get($path, $handler);
    }

    /**
     * 添加POST路由
     *
     * @param string $path 路径
     * @param mixed $handler 处理器
     * @return Route
     */
    public function post(string $path, mixed $handler): Route
    {
        return $this->router->post($path, $handler);
    }

    /**
     * 添加PUT路由
     *
     * @param string $path 路径
     * @param mixed $handler 处理器
     * @return Route
     */
    public function put(string $path, mixed $handler): Route
    {
        return $this->router->put($path, $handler);
    }

    /**
     * 添加DELETE路由
     *
     * @param string $path 路径
     * @param mixed $handler 处理器
     * @return Route
     */
    public function delete(string $path, mixed $handler): Route
    {
        return $this->router->delete($path, $handler);
    }

    /**
     * 添加PATCH路由
     *
     * @param string $path 路径
     * @param mixed $handler 处理器
     * @return Route
     */
    public function patch(string $path, mixed $handler): Route
    {
        return $this->router->patch($path, $handler);
    }

    /**
     * 添加OPTIONS路由
     *
     * @param string $path 路径
     * @param mixed $handler 处理器
     * @return Route
     */
    public function options(string $path, mixed $handler): Route
    {
        return $this->router->options($path, $handler);
    }

    /**
     * 添加多方法路由
     *
     * @param array $methods 方法数组
     * @param string $path 路径
     * @param mixed $handler 处理器
     * @return Route
     */
    public function match(array $methods, string $path, mixed $handler): Route
    {
        return $this->router->match($methods, $path, $handler);
    }

    /**
     * 添加任意方法路由
     *
     * @param string $path 路径
     * @param mixed $handler 处理器
     * @return Route
     */
    public function any(string $path, mixed $handler): Route
    {
        return $this->router->any($path, $handler);
    }

    /**
     * 添加全局中间件
     *
     * @param array $middleware 中间件
     * @return static
     */
    public function middleware(array $middleware): static
    {
        $this->router->middleware($middleware);
        return $this;
    }
}
