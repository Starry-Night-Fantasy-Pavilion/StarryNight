<?php
/**
 * 事件服务提供者
 * 
 * 注册事件订阅者和监听器
 * 
 * @package Core\Providers
 */

namespace Core\Providers;

use Core\Container\ServiceProviderInterface;
use Core\Container\Container;
use Core\Events\EventDispatcher;

/**
 * 事件服务提供者
 */
class EventServiceProvider implements ServiceProviderInterface
{
    /**
     * 注册服务
     */
    public function register(Container $container): void
    {
        // 注册事件分发器（如果还没有注册）
        if (!$container->has(EventDispatcher::class)) {
            $container->singleton(EventDispatcher::class, function () {
                return new EventDispatcher();
            });
        }
        
        if (!$container->has('events')) {
            $container->singleton('events', function () use ($container) {
                return $container->get(EventDispatcher::class);
            });
        }
    }
    
    /**
     * 启动服务
     */
    public function boot(Container $container): void
    {
        $dispatcher = $container->get(EventDispatcher::class);
        
        // 注册事件订阅者
        $this->registerSubscribers($dispatcher);
        
        // 注册事件监听器
        $this->registerListeners($dispatcher);
    }
    
    /**
     * 注册订阅者
     */
    protected function registerSubscribers(EventDispatcher $dispatcher): void
    {
        // 用户事件订阅者
        if (class_exists(\App\Subscribers\UserEventSubscriber::class)) {
            $dispatcher->subscribe(new \App\Subscribers\UserEventSubscriber());
        }
        
        // 可以添加更多订阅者
        // if (class_exists(\App\Subscribers\PaymentEventSubscriber::class)) {
        //     $dispatcher->subscribe(new \App\Subscribers\PaymentEventSubscriber());
        // }
    }
    
    /**
     * 注册监听器
     */
    protected function registerListeners(EventDispatcher $dispatcher): void
    {
        // 注册全局事件日志监听器
        $dispatcher->listen('*', function ($event) {
            if ($event instanceof \Core\Events\Event) {
                // 记录事件日志（可选）
                // error_log("事件触发: " . $event->getName());
            }
        });
    }
}
