<?php
/**
 * 核心辅助函数
 * 
 * @package Core
 * @version 1.0.0
 */

if (!function_exists('app')) {
    /**
     * 获取应用实例或服务
     * 
     * @param string|null $id 服务标识
     * @return mixed
     */
    function app(?string $id = null)
    {
        $container = \Core\Container\Container::getInstance();
        
        if ($id === null) {
            return $container->get('app');
        }
        
        return $container->get($id);
    }
}

if (!function_exists('config')) {
    /**
     * 获取配置值
     * 
     * @param string|null $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    function config(?string $key = null, $default = null)
    {
        $app = app();
        
        if ($key === null) {
            return $app->getConfig();
        }
        
        return $app->getConfig($key, $default);
    }
}

if (!function_exists('event')) {
    /**
     * 触发事件
     * 
     * @param string $eventName 事件名称
     * @param array $data 事件数据
     * @return \Core\Events\Event
     */
    function event(string $eventName, array $data = [])
    {
        return app('events')->fire($eventName, $data);
    }
}

if (!function_exists('dispatch')) {
    /**
     * 分发事件
     * 
     * @param \Core\Events\Event $event 事件实例
     * @return \Core\Events\Event
     */
    function dispatch(\Core\Events\Event $event)
    {
        return app('events')->dispatch($event);
    }
}

if (!function_exists('queue')) {
    /**
     * 推送任务到队列
     * 
     * @param mixed $job 任务
     * @param array $data 任务数据
     * @param string|null $queue 队列名称
     * @return string 任务ID
     */
    function queue($job, array $data = [], ?string $queue = null)
    {
        return app('queue')->push($job, $data, $queue);
    }
}

if (!function_exists('queue_later')) {
    /**
     * 延迟推送任务到队列
     * 
     * @param int $delay 延迟秒数
     * @param mixed $job 任务
     * @param array $data 任务数据
     * @param string|null $queue 队列名称
     * @return string 任务ID
     */
    function queue_later(int $delay, $job, array $data = [], ?string $queue = null)
    {
        return app('queue')->later($delay, $job, $data, $queue);
    }
}

if (!function_exists('logger')) {
    /**
     * 获取日志服务
     * 
     * @return \Core\Services\LoggerService
     */
    function logger()
    {
        return app(\Core\Services\LoggerService::class);
    }
}

if (!function_exists('cache')) {
    /**
     * 获取缓存服务
     * 
     * @return \Core\Services\CacheService
     */
    function cache()
    {
        return app(\Core\Services\CacheService::class);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * 获取CSRF Token
     * 
     * @return string
     */
    function csrf_token(): string
    {
        return app(\Core\Security\CsrfMiddleware::class)->getToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * 生成CSRF隐藏字段
     * 
     * @return string
     */
    function csrf_field(): string
    {
        return app(\Core\Security\CsrfMiddleware::class)->field();
    }
}

if (!function_exists('xss_clean')) {
    /**
     * XSS清理
     * 
     * @param string $input 输入字符串
     * @return string
     */
    function xss_clean(string $input): string
    {
        return app(\Core\Security\XssMiddleware::class)->clean($input);
    }
}

if (!function_exists('e')) {
    /**
     * HTML转义
     * 
     * @param string $value 值
     * @return string
     */
    function e(string $value): string
    {
        return \Core\Security\XssMiddleware::escape($value);
    }
}

if (!function_exists('response')) {
    /**
     * 创建响应
     * 
     * @param mixed $data 数据
     * @param int $status HTTP状态码
     * @param array $headers 头信息
     * @return void
     */
    function response($data = null, int $status = 200, array $headers = [])
    {
        http_response_code($status);
        
        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($data, JSON_UNESCAPED_UNICODE);
            } else {
                echo $data;
            }
        }
    }
}

if (!function_exists('json_response')) {
    /**
     * 返回JSON响应
     * 
     * @param mixed $data 数据
     * @param int $code 错误码
     * @param string $message 消息
     * @return void
     */
    function json_response($data = null, int $code = 0, string $message = 'success')
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time(),
        ], JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('error_response')) {
    /**
     * 返回错误响应
     * 
     * @param int $code 错误码
     * @param string|null $message 消息
     * @param array $data 附加数据
     * @return void
     */
    function error_response(int $code, ?string $message = null, array $data = [])
    {
        $message = $message ?? \Core\Exceptions\ErrorCode::getMessage($code);
        $httpStatus = \Core\Exceptions\ErrorCode::getHttpStatus($code);
        
        http_response_code($httpStatus);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time(),
        ], JSON_UNESCAPED_UNICODE);
    }
}
