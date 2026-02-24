<?php

declare(strict_types=1);

namespace app\subscribers;

/**
 * 事件订阅者接口
 * 
 * @package app\subscribers
 */
interface EventSubscriberInterface
{
    /**
     * 获取订阅的事件列表
     * 返回格式: ['事件名' => '处理方法名', ...] 或 ['事件名' => ['方法名', 优先级], ...]
     * 
     * @return array
     */
    public static function getSubscribedEvents(): array;

    /**
     * 注册订阅者
     * 
     * @return void
     */
    public static function register(): void;
}
