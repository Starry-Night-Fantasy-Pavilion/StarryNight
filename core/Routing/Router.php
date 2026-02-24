<?php
/**
 * 路由器类
 * 支持RESTful路由、中间件、路由分组
 * 
 * @package Core\Routing
 * @version 1.0.0
 */

namespace Core\Routing;

/**
 * 路由类
 */
class Route
{
    public string $method;
    public string $path;
    public $handler;
    public array $middleware = [];
    public string $name = '';
    public array $constraints = [];

    public function __construct(string $method, string $path, $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    public function middleware(array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function where(array $constraints): self
    {
        $this->constraints = $constraints;
        return $this;
    }
}

/**
 * 路由组类
 */
class RouteGroup
{
    private Router $router;
    private string $prefix;
    private array $middleware = [];
    private array $routes = [];

    public function __construct(Router $router, string $prefix = '', array $middleware = [])
    {
        $this->router = $router;
        $this->prefix = $prefix;
        $this->middleware = $middleware;
    }

    public function get(string $path, $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, $handler): Route
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function patch(string $path, $handler): Route
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    private function addRoute(string $method, string $path, $handler): Route
    {
        $fullPath = $this->prefix . $path;
        $route = $this->router->addRoute($method, $fullPath, $handler);
        if (!empty($this->middleware)) {
            $route->middleware($this->middleware);
        }
        $this->routes[] = $route;
        return $route;
    }

    public function group(callable $callback): void
    {
        $callback($this);
    }
}

/**
 * 路由器类
 */
class Router
{
    /**
     * 路由列表
     */
    private array $routes = [];

    /**
     * 命名路由列表
     */
    private array $namedRoutes = [];

    /**
     * 全局中间件
     */
    private array $globalMiddleware = [];

    /**
     * 路由匹配结果缓存
     */
    private array $matchedRoutes = [];

    /**
     * 添加GET路由
     */
    public function get(string $path, $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * 添加POST路由
     */
    public function post(string $path, $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * 添加PUT路由
     */
    public function put(string $path, $handler): Route
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * 添加DELETE路由
     */
    public function delete(string $path, $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * 添加PATCH路由
     */
    public function patch(string $path, $handler): Route
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * 添加OPTIONS路由
     */
    public function options(string $path, $handler): Route
    {
        return $this->addRoute('OPTIONS', $path, $handler);
    }

    /**
     * 添加支持多种HTTP方法的路由
     */
    public function match(array $methods, string $path, $handler): Route
    {
        $route = null;
        foreach ($methods as $method) {
            $route = $this->addRoute(strtoupper($method), $path, $handler);
        }
        return $route;
    }

    /**
     * 添加任意HTTP方法的路由
     */
    public function any(string $path, $handler): Route
    {
        return $this->match(['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], $path, $handler);
    }

    /**
     * 添加路由
     */
    public function addRoute(string $method, string $path, $handler): Route
    {
        $route = new Route($method, $path, $handler);
        $this->routes[$method][] = $route;
        return $route;
    }

    /**
     * 创建路由组
     */
    public function group(array $attributes, callable $callback): RouteGroup
    {
        $prefix = $attributes['prefix'] ?? '';
        $middleware = $attributes['middleware'] ?? [];

        $group = new RouteGroup($this, $prefix, $middleware);
        $callback($group);

        return $group;
    }

    /**
     * 添加全局中间件
     */
    public function middleware(array $middleware): self
    {
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
        return $this;
    }

    /**
     * 解析当前请求
     */
    public function dispatch(?string $method = null, ?string $uri = null): array
    {
        $method = $method ?? $_SERVER['REQUEST_METHOD'];
        $uri = $uri ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // 移除查询字符串
        $uri = strtok($uri, '?');

        // 查找匹配的路由
        $route = $this->findRoute($method, $uri);

        if ($route === null) {
            return [
                'status' => 404,
                'error' => '路由未找到',
                'handler' => null,
                'params' => [],
                'middleware' => []
            ];
        }

        return [
            'status' => 200,
            'route' => $route,
            'handler' => $route->handler,
            'params' => $this->extractParams($route, $uri),
            'middleware' => array_merge($this->globalMiddleware, $route->middleware)
        ];
    }

    /**
     * 查找匹配的路由
     */
    private function findRoute(string $method, string $uri): ?Route
    {
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if ($this->matchRoute($route, $uri)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * 匹配路由
     */
    private function matchRoute(Route $route, string $uri): bool
    {
        $pattern = $this->compilePattern($route->path, $route->constraints);
        return preg_match($pattern, $uri);
    }

    /**
     * 编译路由模式
     */
    private function compilePattern(string $path, array $constraints): string
    {
        // 转义特殊字符
        $pattern = preg_quote($path, '#');

        // 替换参数占位符
        // {id} -> (?P<id>[^/]+)
        // {id:\d+} -> (?P<id>\d+)
        $pattern = preg_replace_callback(
            '#\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\\\\\}#',
            function ($matches) use ($constraints) {
                $name = $matches[1];
                $regex = $matches[2] ?? $constraints[$name] ?? '[^/]+';
                return "(?P<{$name}>{$regex})";
            },
            $pattern
        );

        return "#^{$pattern}$#";
    }

    /**
     * 提取路由参数
     */
    private function extractParams(Route $route, string $uri): array
    {
        $pattern = $this->compilePattern($route->path, $route->constraints);
        preg_match($pattern, $uri, $matches);

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * 生成URL
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("路由名称 '{$name}' 不存在");
        }

        $route = $this->namedRoutes[$name];
        $url = $route->path;

        foreach ($params as $key => $value) {
            $url = str_replace("{{$key}}", $value, $url);
        }

        return $url;
    }

    /**
     * 获取所有路由
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * 注册命名路由
     */
    public function registerNamedRoute(string $name, Route $route): void
    {
        $this->namedRoutes[$name] = $route;
    }

    /**
     * 创建RESTful资源路由
     */
    public function resource(string $name, string $controller, array $options = []): void
    {
        $only = $options['only'] ?? ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'];
        $except = $options['except'] ?? [];

        $actions = array_diff($only, $except);

        $routes = [
            'index' => ['GET', "/{$name}", 'index'],
            'show' => ['GET', "/{$name}/{id}", 'show'],
            'create' => ['GET', "/{$name}/create", 'create'],
            'store' => ['POST', "/{$name}", 'store'],
            'edit' => ['GET', "/{$name}/{id}/edit", 'edit'],
            'update' => ['PUT', "/{$name}/{id}", 'update'],
            'destroy' => ['DELETE', "/{$name}/{id}", 'destroy'],
        ];

        foreach ($actions as $action) {
            if (isset($routes[$action])) {
                [$method, $path, $method_name] = $routes[$action];
                $this->$method($path, [$controller, $method_name])->name("{$name}.{$action}");
            }
        }
    }

    /**
     * API资源路由（排除create和edit）
     */
    public function apiResource(string $name, string $controller, array $options = []): void
    {
        $options['except'] = array_merge($options['except'] ?? [], ['create', 'edit']);
        $this->resource($name, $controller, $options);
    }
}
