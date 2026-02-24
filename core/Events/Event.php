<?php
/**
 * 事件系统
 * 实现观察者模式，支持事件分发和监听
 * 
 * @package Core\Events
 * @version 1.0.0
 */

namespace Core\Events;

/**
 * 事件基类
 */
abstract class Event
{
    /**
     * 事件名称
     */
    protected string $name;

    /**
     * 事件数据
     */
    protected array $data = [];

    /**
     * 是否停止传播
     */
    protected bool $propagationStopped = false;

    /**
     * 事件触发时间
     */
    protected float $timestamp;

    /**
     * 构造函数
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->timestamp = microtime(true);
    }

    /**
     * 获取事件名称
     */
    public function getName(): string
    {
        return $this->name ?? static::class;
    }

    /**
     * 获取事件数据
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 获取单个数据项
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * 设置事件数据
     */
    public function set(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * 停止事件传播
     */
    public function stopPropagation(): self
    {
        $this->propagationStopped = true;
        return $this;
    }

    /**
     * 是否停止传播
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * 获取触发时间
     */
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }
}

/**
 * 监听器接口
 */
interface ListenerInterface
{
    /**
     * 处理事件
     */
    public function handle(Event $event): void;
}

/**
 * 订阅者接口
 */
interface SubscriberInterface
{
    /**
     * 获取订阅的事件
     * 返回格式：['事件名' => '处理方法名']
     */
    public function getSubscribedEvents(): array;
}

/**
 * 可触发事件的Trait
 */
trait Dispatchable
{
    /**
     * 分发事件
     */
    protected static function dispatchEvent(Event $event): Event
    {
        return app('events')->dispatch($event);
    }

    /**
     * 触发事件
     */
    protected static function fireEvent(string $eventName, array $data = []): Event
    {
        return app('events')->fire($eventName, $data);
    }
}
