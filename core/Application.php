<?php
/**
 * 应用引导入口
 * 统一管理应用生命周期
 * 
 * @package Core
 * @version 1.0.0
 */

namespace Core;

use Core\Container\Container;
use Core\Container\ServiceProviderInterface;
use Core\Exceptions\ExceptionHandler;
use Core\Routing\Router;
use Core\Security\CsrfMiddleware;
use Core\Security\XssMiddleware;
use Core\Services\LoggerService;
use Core\Services\CacheService;
use Core\Events\EventDispatcher;
use Core\Queue\QueueService;

/**
 * 应用类
 */
class Application
{
    /**
     * 应用版本
     */
    const VERSION = '1.0.0';

    /**
     * 容器实例
     */
    protected Container $container;

    /**
     * 是否已启动
     */
    protected bool $booted = false;

    /**
     * 配置
     */
    protected array $config = [];

    /**
     * 中间件
     */
    protected array $middlewares = [];

    /**
     * 服务提供者
     */
    protected array $providers = [];

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'debug' => false,
            'env' => 'production',
            'timezone' => 'Asia/Shanghai',
            'charset' => 'UTF-8',
        ], $config);

        $this->initialize();
    }

    /**
     * 初始化应用
     */
    protected function initialize(): void
    {
        // 设置时区
        date_default_timezone_set($this->config['timezone']);

        // 设置字符集
        mb_internal_encoding($this->config['charset']);

        // 设置错误报告
        $this->configureErrorReporting();

        // 初始化容器
        $this->container = Container::getInstance();

        // 注册核心服务
        $this->registerCoreServices();

        // 注册异常处理器
        $this->registerExceptionHandler();
    }

    /**
     * 配置错误报告
     */
    protected function configureErrorReporting(): void
    {
        if ($this->config['debug']) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
            ini_set('display_errors', '0');
        }
    }

    /**
     * 注册核心服务
     */
    protected function registerCoreServices(): void
    {
        // 注册应用实例
        $this->container->instance(Application::class, $this);
        $this->container->instance('app', $this);

        // 注册配置
        $this->container->instance('config', $this->config);

        // 注册日志服务
        $this->container->singleton(LoggerService::class, function () {
            $logConfig = $this->config['log'] ?? [];
            $logPath = $logConfig['path'] ?? null;
            $logLevel = $logConfig['level'] ?? 'info';
            return new LoggerService($logPath, $logLevel);
        });

        // 注册缓存服务
        $this->container->singleton(CacheService::class, function () {
            $cacheConfig = $this->config['cache'] ?? [];
            $cachePath = $cacheConfig['path'] ?? null;
            $defaultTtl = $cacheConfig['ttl'] ?? 3600;
            $prefix = $cacheConfig['prefix'] ?? 'sn_';
            return new CacheService($cachePath, $defaultTtl, $prefix);
        });

        // 注册路由器
        $this->container->singleton(Router::class, function () {
            return new Router();
        });

        // 注册CSRF中间件
        $this->container->singleton(CsrfMiddleware::class, function () {
            return new CsrfMiddleware($this->config['csrf'] ?? []);
        });

        // 注册XSS中间件
        $this->container->singleton(XssMiddleware::class, function () {
            return new XssMiddleware($this->config['xss'] ?? []);
        });

        // 注册事件分发器
        $this->container->singleton(EventDispatcher::class, function () {
            return new EventDispatcher();
        });
        $this->container->singleton('events', function () {
            return $this->container->get(EventDispatcher::class);
        });

        // 注册消息队列服务
        $this->container->singleton(QueueService::class, function () {
            return new QueueService($this->config['queue'] ?? []);
        });
        $this->container->singleton('queue', function () {
            return $this->container->get(QueueService::class);
        });
    }

    /**
     * 注册异常处理器
     */
    protected function registerExceptionHandler(): void
    {
        $handler = new ExceptionHandler(
            $this->config['debug'],
            $this->container->get(LoggerService::class)
        );
        $handler->register();
    }

    /**
     * 启动应用
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // 启动Session
        $this->startSession();

        // 执行中间件
        $this->runMiddlewares();

        // 标记为已启动
        $this->booted = true;
    }

    /**
     * 启动Session
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // 配置Session
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_samesite', 'Lax');

            session_start();
        }
    }

    /**
     * 执行中间件
     */
    protected function runMiddlewares(): void
    {
        foreach ($this->middlewares as $middleware) {
            if (is_string($middleware) && class_exists($middleware)) {
                $instance = $this->container->get($middleware);
                if (method_exists($instance, 'handle')) {
                    $instance->handle();
                }
            } elseif (is_callable($middleware)) {
                call_user_func($middleware);
            }
        }
    }

    /**
     * 注册服务提供者
     */
    public function register(ServiceProviderInterface $provider): void
    {
        $provider->register($this->container);
        $this->providers[] = $provider;
    }

    /**
     * 添加中间件
     */
    public function middleware($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * 添加多个中间件
     */
    public function middlewares(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->middleware($middleware);
        }
        return $this;
    }

    /**
     * 获取容器
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * 获取配置
     */
    public function getConfig(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? $default;
    }

    /**
     * 设置配置
     */
    public function setConfig(string $key, $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * 获取服务
     */
    public function get(string $id)
    {
        return $this->container->get($id);
    }

    /**
     * 检查服务是否存在
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * 是否调试模式
     */
    public function isDebug(): bool
    {
        return $this->config['debug'];
    }

    /**
     * 获取环境
     */
    public function getEnvironment(): string
    {
        return $this->config['env'];
    }

    /**
     * 是否生产环境
     */
    public function isProduction(): bool
    {
        return $this->config['env'] === 'production';
    }

    /**
     * 获取版本
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * 运行应用
     */
    public function run(): void
    {
        $this->boot();

        // 获取路由器并分发请求
        $router = $this->container->get(Router::class);
        $result = $router->dispatch();

        if ($result['status'] === 404) {
            http_response_code(404);
            echo json_encode([
                'code' => 404,
                'message' => '路由未找到',
            ]);
            return;
        }

        // 执行路由处理器
        $this->dispatch($result);
    }

    /**
     * 分发请求
     */
    protected function dispatch(array $result): void
    {
        $handler = $result['handler'];
        $params = $result['params'];

        try {
            if (is_array($handler)) {
                [$class, $method] = $handler;
                $instance = $this->container->get($class);
                $response = $this->container->call([$instance, $method], $params);
            } elseif (is_callable($handler)) {
                $response = $this->container->call($handler, $params);
            } else {
                throw new \RuntimeException('无效的路由处理器');
            }

            $this->sendResponse($response);
        } catch (\Throwable $e) {
            $handler = new ExceptionHandler($this->config['debug']);
            $handler->handleException($e);
        }
    }

    /**
     * 发送响应
     */
    protected function sendResponse($response): void
    {
        if (is_string($response)) {
            echo $response;
        } elseif (is_array($response) || is_object($response)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 创建应用实例（静态工厂）
     */
    public static function create(array $config = []): self
    {
        return new self($config);
    }
}
