<?php
/**
 * 服务提供者接口
 * 
 * @package Core\Container
 * @version 1.0.0
 */

namespace Core\Container;

interface ServiceProviderInterface
{
    /**
     * 注册服务
     * 
     * @param Container $container 容器实例
     */
    public function register(Container $container): void;
}
