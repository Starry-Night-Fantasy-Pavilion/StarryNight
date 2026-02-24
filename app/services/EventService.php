<?php

declare(strict_types=1);

namespace app\services;

/**
 * 事件服务
 * 
 * 简化事件系统的使用，提供便捷的静态方法
 * 
 * @package app\services
 */
class EventService
{
    /**
     * 已注册的订阅者
     *
     * @var array
     */
    private static array $registeredSubscribers = [];

    /**
     * 分发事件
     *
     * @param string $event 事件名称
     * @param array $data 事件数据
     * @return void
     */
    public static function dispatch(string $event, array $data = []): void
    {
        EventSystem::dispatch($event, $data);
    }

    /**
     * 监听事件
     *
     * @param string $event 事件名称
     * @param callable $callback 回调函数
     * @param int $priority 优先级
     * @return void
     */
    public static function listen(string $event, callable $callback, int $priority = 10): void
    {
        EventSystem::listen($event, $callback, $priority);
    }

    /**
     * 注册订阅者
     *
     * @param string $subscriberClass 订阅者类名
     * @return void
     */
    public static function subscribe(string $subscriberClass): void
    {
        if (in_array($subscriberClass, self::$registeredSubscribers, true)) {
            return;
        }

        if (!class_exists($subscriberClass)) {
            error_log("订阅者类不存在: {$subscriberClass}");
            return;
        }

        $subscriber = new $subscriberClass();
        
        if (!method_exists($subscriber, 'getSubscribedEvents')) {
            error_log("订阅者类缺少 getSubscribedEvents 方法: {$subscriberClass}");
            return;
        }

        $events = $subscriber->getSubscribedEvents();
        
        foreach ($events as $eventName => $methodInfo) {
            if (is_string($methodInfo)) {
                // 简单格式: ['事件名' => '方法名']
                $callback = [$subscriber, $methodInfo];
                $priority = 10;
            } elseif (is_array($methodInfo) && count($methodInfo) >= 1) {
                // 复杂格式: ['事件名' => ['方法名', 优先级]]
                $callback = [$subscriber, $methodInfo[0]];
                $priority = $methodInfo[1] ?? 10;
            } else {
                continue;
            }

            if (is_callable($callback)) {
                self::listen($eventName, $callback, $priority);
            }
        }

        self::$registeredSubscribers[] = $subscriberClass;
    }

    /**
     * 批量注册订阅者
     *
     * @param array $subscriberClasses 订阅者类名数组
     * @return void
     */
    public static function subscribeMany(array $subscriberClasses): void
    {
        foreach ($subscriberClasses as $class) {
            self::subscribe($class);
        }
    }

    /**
     * 触发用户注册事件
     *
     * @param array $userData 用户数据
     */
    public static function userRegistered(array $userData): void
    {
        self::dispatch(SystemEvents::USER_REGISTERED, $userData);
    }

    /**
     * 触发用户登录事件
     *
     * @param array $userData 用户数据
     */
    public static function userLogin(array $userData): void
    {
        self::dispatch(SystemEvents::USER_LOGIN, $userData);
    }

    /**
     * 触发用户登出事件
     *
     * @param int $userId 用户ID
     */
    public static function userLogout(int $userId): void
    {
        self::dispatch(SystemEvents::USER_LOGOUT, ['user_id' => $userId]);
    }

    /**
     * 触发AI请求事件
     *
     * @param string $action 操作类型
     * @param array $params 参数
     * @param int $userId 用户ID
     */
    public static function aiRequest(string $action, array $params, int $userId): void
    {
        self::dispatch(SystemEvents::AI_REQUEST_START, [
            'action' => $action,
            'params' => $params,
            'user_id' => $userId,
            'timestamp' => time(),
        ]);
    }

    /**
     * 触发AI请求成功事件
     *
     * @param string $action 操作类型
     * @param int $tokensUsed 消耗的Token数
     * @param int $userId 用户ID
     */
    public static function aiRequestSuccess(string $action, int $tokensUsed, int $userId): void
    {
        self::dispatch(SystemEvents::AI_REQUEST_SUCCESS, [
            'action' => $action,
            'tokens_used' => $tokensUsed,
            'user_id' => $userId,
            'timestamp' => time(),
        ]);

        if ($tokensUsed > 0) {
            self::dispatch(SystemEvents::AI_TOKEN_CONSUMED, [
                'user_id' => $userId,
                'tokens' => $tokensUsed,
                'action' => $action,
            ]);
        }
    }

    /**
     * 触发订单支付成功事件
     *
     * @param array $orderData 订单数据
     */
    public static function orderPaid(array $orderData): void
    {
        self::dispatch(SystemEvents::ORDER_PAID, $orderData);
    }

    /**
     * 触发安全事件
     *
     * @param string $event 事件类型
     * @param array $data 事件数据
     */
    public static function securityEvent(string $event, array $data): void
    {
        self::dispatch($event, array_merge($data, [
            'timestamp' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]));
    }

    /**
     * 检查事件是否有监听器
     *
     * @param string $event 事件名称
     * @return bool
     */
    public static function hasListeners(string $event): bool
    {
        return EventSystem::hasListeners($event);
    }

    /**
     * 获取事件历史
     *
     * @return array
     */
    public static function getHistory(): array
    {
        return EventSystem::getEventHistory();
    }

    /**
     * 清除所有监听器
     *
     * @return void
     */
    public static function clear(): void
    {
        EventSystem::clear();
        self::$registeredSubscribers = [];
    }
}
