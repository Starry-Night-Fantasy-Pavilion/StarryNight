<?php
/**
 * 安全服务提供者
 * 
 * 注册CSRF和XSS安全中间件
 * 
 * @package Core\Providers
 */

namespace Core\Providers;

use Core\Container\ServiceProviderInterface;
use Core\Container\Container;
use Core\Security\CsrfMiddleware;
use Core\Security\XssMiddleware;

/**
 * 安全服务提供者
 */
class SecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * 注册服务
     */
    public function register(Container $container): void
    {
        // 注册CSRF中间件
        $container->singleton(CsrfMiddleware::class, function () use ($container) {
            $config = [];
            
            if ($container->has('config')) {
                $appConfig = $container->get('config');
                $config = $appConfig['csrf'] ?? [];
            }
            
            return new CsrfMiddleware($config);
        });
        
        // 注册XSS中间件
        $container->singleton(XssMiddleware::class, function () use ($container) {
            $config = [];
            
            if ($container->has('config')) {
                $appConfig = $container->get('config');
                $config = $appConfig['xss'] ?? [];
            }
            
            return new XssMiddleware($config);
        });
    }
    
    /**
     * 启动服务
     */
    public function boot(Container $container): void
    {
        // 安全中间件不需要特殊启动逻辑
    }
}
