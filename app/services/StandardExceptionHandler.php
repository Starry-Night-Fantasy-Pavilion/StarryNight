<?php

namespace app\services;

/**
 * 标准异常处理器
 * 提供统一的异常处理和错误响应格式
 */
class StandardExceptionHandler
{
    /**
     * 处理异常并返回适当的响应
     *
     * @param \Throwable $exception 异常对象
     * @param string $context 上下文信息（可选）
     * @return array 错误响应数组
     */
    public static function handle(\Throwable $exception, string $context = ''): array
    {
        // 记录详细日志
        self::logException($exception, $context);
        
        // 根据环境决定返回内容
        $isProduction = get_env('APP_ENV', 'production') === 'production';
        
        $errorCode = self::getErrorCode($exception);
        $errorMessage = $isProduction 
            ? self::getUserFriendlyMessage($exception)
            : $exception->getMessage();
        
        return [
            'success' => false,
            'error' => $errorMessage,
            'code' => $errorCode,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * 记录异常日志
     *
     * @param \Throwable $exception
     * @param string $context
     */
    private static function logException(\Throwable $exception, string $context = ''): void
    {
        $logData = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
        
        $logMessage = sprintf(
            "[Exception] %s in %s:%d\nContext: %s\nRequest: %s %s\nTrace:\n%s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $context ?: 'N/A',
            $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            $_SERVER['REQUEST_URI'] ?? 'N/A',
            $exception->getTraceAsString()
        );
        
        error_log($logMessage);
        
        // 写入日志文件
        $logFile = __DIR__ . '/../../storage/logs/exceptions.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        @file_put_contents(
            $logFile,
            date('Y-m-d H:i:s') . ' ' . $logMessage . PHP_EOL . PHP_EOL,
            FILE_APPEND
        );
    }
    
    /**
     * 获取错误代码
     *
     * @param \Throwable $exception
     * @return int
     */
    private static function getErrorCode(\Throwable $exception): int
    {
        // 如果异常有代码，使用异常代码
        if ($exception->getCode() > 0) {
            return $exception->getCode();
        }
        
        // 根据异常类型返回默认代码
        $exceptionClass = get_class($exception);
        
        if (strpos($exceptionClass, 'Database') !== false || 
            strpos($exceptionClass, 'PDO') !== false) {
            return 50001; // 数据库错误
        }
        
        if (strpos($exceptionClass, 'Validation') !== false) {
            return 40001; // 验证错误
        }
        
        if (strpos($exceptionClass, 'NotFound') !== false) {
            return 40401; // 资源未找到
        }
        
        if (strpos($exceptionClass, 'Permission') !== false || 
            strpos($exceptionClass, 'Forbidden') !== false) {
            return 40301; // 权限错误
        }
        
        return 50000; // 通用服务器错误
    }
    
    /**
     * 获取用户友好的错误消息
     *
     * @param \Throwable $exception
     * @return string
     */
    private static function getUserFriendlyMessage(\Throwable $exception): string
    {
        $exceptionClass = get_class($exception);
        
        // 数据库相关错误
        if (strpos($exceptionClass, 'Database') !== false || 
            strpos($exceptionClass, 'PDO') !== false) {
            return '数据库操作失败，请稍后重试';
        }
        
        // 网络相关错误
        if (strpos($exceptionClass, 'Network') !== false || 
            strpos($exceptionClass, 'Curl') !== false) {
            return '网络连接失败，请检查网络设置';
        }
        
        // 文件相关错误
        if (strpos($exceptionClass, 'File') !== false || 
            strpos($exceptionClass, 'Upload') !== false) {
            return '文件操作失败，请检查文件权限';
        }
        
        // 权限相关错误
        if (strpos($exceptionClass, 'Permission') !== false || 
            strpos($exceptionClass, 'Forbidden') !== false) {
            return '您没有权限执行此操作';
        }
        
        // 资源未找到
        if (strpos($exceptionClass, 'NotFound') !== false) {
            return '请求的资源不存在';
        }
        
        // 默认消息
        return '系统繁忙，请稍后再试';
    }
    
    /**
     * 返回JSON格式的错误响应
     *
     * @param \Throwable $exception
     * @param string $context
     * @return void
     */
    public static function handleJson(\Throwable $exception, string $context = ''): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        
        $response = self::handle($exception, $context);
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * 返回HTML格式的错误响应
     *
     * @param \Throwable $exception
     * @param string $context
     * @return void
     */
    public static function handleHtml(\Throwable $exception, string $context = ''): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        
        $response = self::handle($exception, $context);
        $isProduction = get_env('APP_ENV', 'production') === 'production';
        
        if ($isProduction) {
            ErrorHandler::handleServerError($exception);
        } else {
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>错误</title></head><body>';
            echo '<h1>系统错误</h1>';
            echo '<p>' . htmlspecialchars($response['error']) . '</p>';
            echo '<p><small>错误代码: ' . $response['code'] . '</small></p>';
            if ($context) {
                echo '<p><small>上下文: ' . htmlspecialchars($context) . '</small></p>';
            }
            echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
            echo '</body></html>';
        }
        exit;
    }
}
