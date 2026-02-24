<?php

declare(strict_types=1);

namespace app\services;

/**
 * 事件系统
 * 
 * 基于架构文档实现的事件分发系统，支持插件间通信和系统扩展
 * PHP 8.0+ 兼容版本
 */
class EventSystem
{
    /**
     * @var array<string, array<int, array{priority: int, callback: callable}>> 存储所有事件监听器
     */
    private static array $listeners = [];

    /**
     * @var array<int, array{event: string, data: array, time: float}> 存储已触发的事件历史
     */
    private static array $events = [];

    /**
     * 监听事件
     *
     * @param string $event 事件名称
     * @param callable $callback 回调函数
     * @param int $priority 优先级（数字越小优先级越高）
     * @return void
     */
    public static function listen(string $event, callable $callback, int $priority = 10): void
    {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }

        self::$listeners[$event][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // 按优先级排序
        usort(self::$listeners[$event], static function (array $a, array $b): int {
            return $a['priority'] <=> $b['priority'];
        });
    }

    /**
     * 分发事件
     *
     * @param string $event 事件名称
     * @param array $data 事件数据
     * @return void
     */
    public static function dispatch(string $event, array $data = []): void
    {
        // 记录事件历史
        self::$events[] = [
            'event' => $event,
            'data' => $data,
            'time' => microtime(true)
        ];

        if (!isset(self::$listeners[$event])) {
            return;
        }

        foreach (self::$listeners[$event] as $listener) {
            $result = call_user_func($listener['callback'], $data);

            // 如果回调返回 false，停止后续监听器的执行
            if ($result === false) {
                break;
            }
        }
    }

    /**
     * 获取事件历史
     *
     * @return array<int, array{event: string, data: array, time: float}>
     */
    public static function getEventHistory(): array
    {
        return self::$events;
    }

    /**
     * 移除事件监听器
     *
     * @param string $event 事件名称
     * @param callable|null $callback 要移除的回调，null则移除所有
     * @return void
     */
    public static function removeListener(string $event, ?callable $callback = null): void
    {
        if (!isset(self::$listeners[$event])) {
            return;
        }

        if ($callback === null) {
            unset(self::$listeners[$event]);
            return;
        }

        self::$listeners[$event] = array_filter(
            self::$listeners[$event],
            static function (array $listener) use ($callback): bool {
                return $listener['callback'] !== $callback;
            }
        );

        // 重新索引数组
        self::$listeners[$event] = array_values(self::$listeners[$event]);
    }

    /**
     * 检查事件是否有监听器
     *
     * @param string $event 事件名称
     * @return bool
     */
    public static function hasListeners(string $event): bool
    {
        return isset(self::$listeners[$event]) && !empty(self::$listeners[$event]);
    }

    /**
     * 清除所有事件监听器和历史
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$listeners = [];
        self::$events = [];
    }

    /**
     * 获取特定事件的所有监听器
     *
     * @param string $event 事件名称
     * @return array<int, array{priority: int, callback: callable}>
     */
    public static function getListeners(string $event): array
    {
        return self::$listeners[$event] ?? [];
    }
}
