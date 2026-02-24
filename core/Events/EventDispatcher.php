<?php
/**
 * 事件分发器
 *
 * @package Core\Events
 * @version 1.0.0
 */

namespace Core\Events;

/**
 * 事件分发器
 */
class EventDispatcher
{
    /**
     * 已注册的监听器
     */
    protected array $listeners = [];

    /**
     * 已注册的订阅者
     */
    protected array $subscribers = [];

    /**
     * 通配符监听器
     */
    protected array $wildcardListeners = [];

    /**
     * 是否按优先级排序
     */
    protected bool $sorted = false;

    /**
     * 添加监听器
     */
    public function listen(string $eventName, $listener, int $priority = 0): self
    {
        // 支持通配符
        if (strpos($eventName, '*') !== false) {
            $this->wildcardListeners[$eventName][] = [
                'listener' => $listener,
                'priority' => $priority,
            ];
            return $this;
        }

        $this->listeners[$eventName][$priority][] = $listener;
        $this->sorted = false;

        return $this;
    }

    /**
     * 添加多个监听器
     */
    public function listenMany(array $events): self
    {
        foreach ($events as $eventName => $listeners) {
            if (is_array($listeners) && isset($listeners[0])) {
                foreach ($listeners as $listener) {
                    $priority = is_array($listener) ? ($listener['priority'] ?? 0) : 0;
                    $callback = is_array($listener) ? $listener['listener'] : $listener;
                    $this->listen($eventName, $callback, $priority);
                }
            } else {
                $this->listen($eventName, $listeners);
            }
        }
        return $this;
    }

    /**
     * 注册订阅者
     */
    public function subscribe(SubscriberInterface $subscriber): self
    {
        $events = $subscriber->getSubscribedEvents();

        foreach ($events as $eventName => $method) {
            if (is_array($method)) {
                // ['methodName', priority]
                $this->listen($eventName, [$subscriber, $method[0]], $method[1] ?? 0);
            } else {
                $this->listen($eventName, [$subscriber, $method]);
            }
        }

        $this->subscribers[] = $subscriber;
        return $this;
    }

    /**
     * 分发事件
     */
    public function dispatch(Event $event): Event
    {
        $eventName = $event->getName();

        // 获取监听器
        $listeners = $this->getListeners($eventName);

        // 按优先级排序
        if (!$this->sorted) {
            $this->sortListeners();
        }

        // 执行监听器
        foreach ($listeners as $listener) {
            if ($event->isPropagationStopped()) {
                break;
            }

            $this->invokeListener($listener, $event);
        }

        return $event;
    }

    /**
     * 触发事件（快捷方法）
     */
    public function fire(string $eventName, array $data = []): Event
    {
        $event = new GenericEvent($eventName, $data);
        return $this->dispatch($event);
    }

    /**
     * 获取事件监听器
     */
    public function getListeners(string $eventName): array
    {
        $listeners = $this->listeners[$eventName] ?? [];

        // 合并通配符监听器
        foreach ($this->wildcardListeners as $pattern => $wildcardListeners) {
            if ($this->matchWildcard($pattern, $eventName)) {
                foreach ($wildcardListeners as $listener) {
                    $listeners[$listener['priority']][] = $listener['listener'];
                }
            }
        }

        // 按优先级排序并合并
        krsort($listeners);
        $result = [];
        foreach ($listeners as $priority => $items) {
            $result = array_merge($result, $items);
        }

        return $result;
    }

    /**
     * 检查是否有监听器
     */
    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]) || $this->hasWildcardListeners($eventName);
    }

    /**
     * 检查是否有通配符监听器
     */
    protected function hasWildcardListeners(string $eventName): bool
    {
        foreach ($this->wildcardListeners as $pattern => $listeners) {
            if ($this->matchWildcard($pattern, $eventName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 移除监听器
     */
    public function forget(string $eventName): self
    {
        unset($this->listeners[$eventName]);
        return $this;
    }

    /**
     * 清空所有监听器
     */
    public function flush(): self
    {
        $this->listeners = [];
        $this->wildcardListeners = [];
        $this->subscribers = [];
        return $this;
    }

    /**
     * 排序监听器
     */
    protected function sortListeners(): void
    {
        foreach ($this->listeners as $eventName => $priorities) {
            krsort($this->listeners[$eventName]);
        }
        $this->sorted = true;
    }

    /**
     * 调用监听器
     */
    protected function invokeListener($listener, Event $event): void
    {
        if ($listener instanceof \Closure) {
            $listener($event);
        } elseif (is_array($listener)) {
            [$class, $method] = $listener;
            if (is_string($class)) {
                $class = new $class();
            }
            $class->$method($event);
        } elseif (is_string($listener) && class_exists($listener)) {
            (new $listener())->handle($event);
        } elseif (is_object($listener) && method_exists($listener, 'handle')) {
            $listener->handle($event);
        }
    }

    /**
     * 匹配通配符
     */
    protected function matchWildcard(string $pattern, string $subject): bool
    {
        $regex = str_replace('*', '.*', preg_quote($pattern, '#'));
        return preg_match('#^' . $regex . '$#', $subject);
    }
}
