<?php

declare(strict_types=1);

namespace app\providers;

use app\services\EventService;
use app\subscribers\UserEventSubscriber;
use app\subscribers\SecurityEventSubscriber;

/**
 * 事件服务提供者
 * 
 * 注册所有事件订阅者
 * 
 * @package app\providers
 */
class EventServiceProvider
{
    /**
     * 默认订阅者列表
     *
     * @var array
     */
    protected static array $subscribers = [
        UserEventSubscriber::class,
        SecurityEventSubscriber::class,
    ];

    /**
     * 注册所有订阅者
     *
     * @return void
     */
    public static function register(): void
    {
        foreach (self::$subscribers as $subscriber) {
            EventService::subscribe($subscriber);
        }
    }

    /**
     * 添加订阅者
     *
     * @param string $subscriberClass 订阅者类名
     * @return void
     */
    public static function addSubscriber(string $subscriberClass): void
    {
        if (!in_array($subscriberClass, self::$subscribers, true)) {
            self::$subscribers[] = $subscriberClass;
            EventService::subscribe($subscriberClass);
        }
    }

    /**
     * 获取已注册的订阅者列表
     *
     * @return array
     */
    public static function getSubscribers(): array
    {
        return self::$subscribers;
    }
}
