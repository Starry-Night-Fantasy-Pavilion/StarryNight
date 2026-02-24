<?php

namespace app\services;

/**
 * 轻量级路由器
 *
 * 负责从插件收集路由规则，并根据用户的请求URI进行分发。
 * 支持GET和POST方法，以及后台路由分组。
 * @package app\services
 */
class Router
{
    /**
     * @var array 存储所有已注册的路由规则
     * 结构: ['GET' => [ ['pattern' => string, 'callback' => mixed], ... ], 'POST' => [...]]
     */
    protected $routes = [
        'GET' => [],
        'POST' => []
    ];

    /**
     * @var string 当前路由分组的前缀 (主要用于后台)
     */
    protected $prefix = '';

    /**
     * 注册一个GET路由
     *
     * @param string $uri 路由URI (例如: '/users/{id}')
     * @param callable|array $callback 回调函数或 [Controller::class, 'method'] 数组
     */
    public function get($uri, $callback)
    {
        $uri = $this->prefix ? $this->prefix . '/' . trim($uri, '/') : trim($uri, '/');
        // Normalize URI by removing duplicate slashes, but keep single slash for root
        $uri = preg_replace('#/+#', '/', $uri);
        $this->routes['GET'][] = [
            'pattern' => trim($uri, '/'),
            'callback' => $callback,
        ];
    }

    /**
     * 注册一个POST路由
     *
     * @param string $uri 路由URI
     * @param callable|array $callback 回调函数或 [Controller::class, 'method'] 数组
     */
    public function post($uri, $callback)
    {
        $uri = $this->prefix ? $this->prefix . '/' . trim($uri, '/') : trim($uri, '/');
        $uri = preg_replace('#/+#', '/', $uri);
        $this->routes['POST'][] = [
            'pattern' => trim($uri, '/'),
            'callback' => $callback,
        ];
    }

    /**
     * 创建一个路由分组
     *
     * 所有在该闭包内定义的路由，其URI都会被自动添加指定的前缀。
     * 主要用于后台路由，例如 /admin/*
     *
     * @param string $prefix 路由前缀 (例如: 'admin')
     * @param callable $callback 一个接收Router实例作为参数的闭包
     */
    public function group($prefix, $callback)
    {
        $this->prefix = trim($prefix, '/');
        $callback($this);
        $this->prefix = ''; // Reset prefix
    }


    /**
     * 核心分发方法
     *
     * 解析当前请求的URI和方法，并在已注册的路由中查找匹配项。
     * 如果找到，则执行对应的回调。如果找不到，则显示404页面。
     *
     * @return void
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        // 在部分服务器（尤其是 IIS / FastCGI）环境下，PATH_INFO 可能始终为 "/" 或不准确，
        // 会导致例如访问 /admin/login 时被误识别为根路径，从而总是命中前台首页路由。
        // 为了保证路由稳定性，这里统一基于 REQUEST_URI 进行解析。
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        $uri = trim($path, '/');

        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route) {
                $pattern = $route['pattern'];
                $callback = $route['callback'];

                if ($pattern === $uri) {
                    $this->executeCallback($callback, []);
                    return; // 找到并执行后，立即返回
                }

                if (strpos($pattern, '{') !== false && strpos($pattern, '}') !== false) {
                    $paramNames = [];
                    $quoted = preg_quote($pattern, '#');
                    $regex = preg_replace_callback('/\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}/', function ($matches) use (&$paramNames) {
                        $paramNames[] = $matches[1];
                        return '([^/]+)';
                    }, $quoted);

                    $regex = '#^' . $regex . '$#';
                    if (preg_match($regex, $uri, $matches)) {
                        array_shift($matches);
                        $params = [];
                        foreach ($matches as $index => $value) {
                            $params[] = urldecode($value);
                        }
                        $this->executeCallback($callback, $params);
                        return;
                    }
                }
            }
        }

        // 如果循环结束仍未找到匹配的路由，则显示404页面
        $this->handleNotFound();
    }

    /**
     * 执行路由回调
     *
     * @param callable|array $callback
     * @return void
     */
    private function executeCallback($callback, array $params)
    {
        if (is_callable($callback)) {
            call_user_func_array($callback, $params);
            return;
        }

        // 检查是否为 [Controller::class, 'method'] 格式
        if (is_array($callback) && count($callback) === 2) {
            list($controller_name, $method_name) = $callback;

            if (class_exists($controller_name)) {
                $controller = new $controller_name();
                if (method_exists($controller, $method_name)) {
                    call_user_func_array([$controller, $method_name], $params);
                    return;
                }
            }
        }

        // 如果回调无效，记录错误并显示500页面
        error_log("Invalid callback for route.");
        $this->handleServerError();
    }

    /**
     * 处理 404 Not Found 错误
     */
    private function handleNotFound()
    {
        \app\services\ErrorHandler::handleNotFound();
    }

    /**
     * 处理 500 Server Error 错误
     */
    private function handleServerError()
    {
        \app\services\ErrorHandler::handleServerError();
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
