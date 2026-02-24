<?php
/**
 * 队列服务提供者
 * 
 * 注册队列服务
 * 
 * @package Core\Providers
 */

namespace Core\Providers;

use Core\Container\ServiceProviderInterface;
use Core\Container\Container;
use Core\Queue\QueueService;

/**
 * 队列服务提供者
 */
class QueueServiceProvider implements ServiceProviderInterface
{
    /**
     * 注册服务
     */
    public function register(Container $container): void
    {
        // 注册队列服务
        $container->singleton(QueueService::class, function () use ($container) {
            $config = [];
            
            if ($container->has('config')) {
                $appConfig = $container->get('config');
                $config = $appConfig['queue'] ?? [];
            }
            
            return new QueueService($config);
        });
        
        $container->singleton('queue', function () use ($container) {
            return $container->get(QueueService::class);
        });
    }
    
    /**
     * 启动服务
     */
    public function boot(Container $container): void
    {
        // 队列服务不需要特殊启动逻辑
    }
}
