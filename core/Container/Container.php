<?php
/**
 * 依赖注入容器
 * 实现PSR-11容器接口
 * 
 * @package Core\Container
 * @version 1.0.0
 */

namespace Core\Container;

use Core\Container\Psr\ContainerInterface;
use Core\Container\Psr\NotFoundExceptionInterface;
use Core\Container\Psr\ContainerExceptionInterface;

/**
 * 容器异常类
 */
class ContainerException extends \Exception implements ContainerExceptionInterface {}

/**
 * 容器条目未找到异常
 */
class NotFoundException extends \Exception implements NotFoundExceptionInterface {}

/**
 * 依赖注入容器
 * 支持自动绑定、单例、别名、标签等功能
 */
class Container implements ContainerInterface
{
    /**
     * 容器实例（单例）
     */
    private static ?Container $instance = null;

    /**
     * 绑定列表
     */
    private array $bindings = [];

    /**
     * 单例实例列表
     */
    private array $instances = [];

    /**
     * 别名列表
     */
    private array $aliases = [];

    /**
     * 标签列表
     */
    private array $tags = [];

    /**
     * 解析栈（用于循环依赖检测）
     */
    private array $buildStack = [];

    /**
     * 获取容器单例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 私有构造函数（单例模式）
     */
    private function __construct()
    {
        // 注册容器自身
        $this->instance(ContainerInterface::class, $this);
        $this->instance(self::class, $this);
    }

    /**
     * 绑定服务
     * 
     * @param string $abstract 抽象标识
     * @param \Closure|string|null $concrete 具体实现
     * @param bool $shared 是否单例
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->dropStaleInstances($abstract);

        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * 绑定单例
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * 注册已存在的实例
     */
    public function instance(string $abstract, $instance): void
    {
        $this->dropStaleInstances($abstract);
        $this->instances[$abstract] = $instance;
    }

    /**
     * 设置别名
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * 标签绑定
     */
    public function tag(array $services, string $tag): void
    {
        foreach ($services as $service) {
            $this->tags[$tag][] = $service;
        }
    }

    /**
     * 获取标签下的所有服务
     */
    public function tagged(string $tag): array
    {
        $services = [];
        foreach ($this->tags[$tag] ?? [] as $abstract) {
            $services[] = $this->get($abstract);
        }
        return $services;
    }

    /**
     * 解析服务（PSR-11接口）
     */
    public function get(string $id)
    {
        return $this->resolve($id);
    }

    /**
     * 检查服务是否存在（PSR-11接口）
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || 
               isset($this->instances[$id]) || 
               isset($this->aliases[$id]) ||
               class_exists($id);
    }

    /**
     * 解析服务
     */
    public function resolve(string $abstract)
    {
        $abstract = $this->getAlias($abstract);

        // 检查是否已有实例
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // 获取绑定信息
        $concrete = $this->getConcrete($abstract);

        // 构建实例
        $object = $this->build($concrete);

        // 如果是单例，保存实例
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * 获取别名对应的抽象标识
     */
    private function getAlias(string $abstract): string
    {
        while (isset($this->aliases[$abstract])) {
            $abstract = $this->aliases[$abstract];
        }
        return $abstract;
    }

    /**
     * 获取具体实现
     */
    private function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }
        return $abstract;
    }

    /**
     * 构建实例
     */
    private function build($concrete)
    {
        // 如果是闭包，直接执行
        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }

        // 如果是类名，通过反射构建
        try {
            $reflector = new \ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new NotFoundException("类 {$concrete} 不存在");
        }

        // 检查是否可实例化
        if (!$reflector->isInstantiable()) {
            throw new ContainerException("类 {$concrete} 不可实例化");
        }

        // 检测循环依赖
        if (in_array($concrete, $this->buildStack)) {
            throw new ContainerException("检测到循环依赖: " . implode(' -> ', $this->buildStack) . " -> {$concrete}");
        }

        $this->buildStack[] = $concrete;

        // 获取构造函数
        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            // 无构造函数，直接实例化
            array_pop($this->buildStack);
            return new $concrete();
        }

        // 解析构造函数参数
        $dependencies = $this->resolveDependencies($constructor->getParameters());

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * 解析依赖参数
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type === null) {
                // 无类型提示，尝试使用默认值
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new ContainerException("无法解析参数 {$parameter->getName()}");
                }
            } elseif ($type->isBuiltin()) {
                // 内置类型，使用默认值
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new ContainerException("无法解析内置类型参数 {$parameter->getName()}");
                }
            } else {
                // 类类型，递归解析
                $dependencies[] = $this->resolve($type->getName());
            }
        }

        return $dependencies;
    }

    /**
     * 检查是否为单例
     */
    private function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
               (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared']);
    }

    /**
     * 清除过期实例
     */
    private function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * 清除所有绑定和实例
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->aliases = [];
        $this->tags = [];
    }

    /**
     * 注册服务提供者
     */
    public function register(ServiceProviderInterface $provider): void
    {
        $provider->register($this);
    }

    /**
     * 批量注册服务提供者
     */
    public function registerProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            if (is_string($provider) && class_exists($provider)) {
                $provider = new $provider();
            }
            if ($provider instanceof ServiceProviderInterface) {
                $this->register($provider);
            }
        }
    }

    /**
     * 调用方法并自动注入依赖
     */
    public function call($callback, array $parameters = [])
    {
        if (is_array($callback)) {
            [$class, $method] = $callback;
            if (is_string($class)) {
                $class = $this->resolve($class);
            }
            $reflector = new \ReflectionMethod($class, $method);
            $dependencies = $this->resolveMethodDependencies($reflector, $parameters);
            return $reflector->invokeArgs($class, $dependencies);
        }

        if ($callback instanceof \Closure || is_string($callback)) {
            $reflector = new \ReflectionFunction($callback);
            $dependencies = $this->resolveFunctionDependencies($reflector, $parameters);
            return $reflector->invokeArgs($dependencies);
        }

        throw new ContainerException("无法调用回调函数");
    }

    /**
     * 解析方法依赖
     */
    private function resolveMethodDependencies(\ReflectionMethod $method, array $parameters): array
    {
        $dependencies = [];
        foreach ($method->getParameters() as $param) {
            if (array_key_exists($param->getName(), $parameters)) {
                $dependencies[] = $parameters[$param->getName()];
            } else {
                $type = $param->getType();
                if ($type && !$type->isBuiltin()) {
                    $dependencies[] = $this->resolve($type->getName());
                } elseif ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                }
            }
        }
        return $dependencies;
    }

    /**
     * 解析函数依赖
     */
    private function resolveFunctionDependencies(\ReflectionFunction $function, array $parameters): array
    {
        $dependencies = [];
        foreach ($function->getParameters() as $param) {
            if (array_key_exists($param->getName(), $parameters)) {
                $dependencies[] = $parameters[$param->getName()];
            } else {
                $type = $param->getType();
                if ($type && !$type->isBuiltin()) {
                    $dependencies[] = $this->resolve($type->getName());
                } elseif ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                }
            }
        }
        return $dependencies;
    }
}
