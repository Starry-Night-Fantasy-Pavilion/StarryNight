<?php
/**
 * 应用服务提供者
 * 注册核心服务到容器
 * 
 * @package Core\Providers
 */

namespace Core\Providers;

use Core\Container\Container;
use Core\Container\ServiceProviderInterface;
use Core\Routing\Router;
use Core\Services\AIConfigService;

class AppServiceProvider implements ServiceProviderInterface
{
    /**
     * 注册服务
     */
    public function register(Container $container): void
    {
        // 注册路由器
        $container->singleton(Router::class, function () {
            return new Router();
        });

        // 注册AI配置服务
        $container->singleton(AIConfigService::class, function () {
            return new AIConfigService();
        });

        // 注册配置服务
        $container->singleton('config', function () {
            return $this->loadConfig();
        });

        // 注册日志服务
        $container->singleton('logger', function () {
            return new \Core\Services\LoggerService();
        });

        // 注册缓存服务
        $container->singleton('cache', function () {
            return new \Core\Services\CacheService();
        });
    }

    /**
     * 加载配置
     */
    private function loadConfig(): array
    {
        $config = [];
        $configPath = dirname(__DIR__, 2) . '/config';

        if (is_dir($configPath)) {
            foreach (glob($configPath . '/*.php') as $file) {
                $name = basename($file, '.php');
                $config[$name] = require $file;
            }
        }

        return $config;
    }
}
