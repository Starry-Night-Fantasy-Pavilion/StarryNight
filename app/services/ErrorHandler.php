<?php

namespace app\services;

/**
 * 统一错误处理器
 * 提供统一的错误处理、日志记录和错误页面渲染
 */
class ErrorHandler
{
    /**
     * 处理404错误
     * @param string|null $message 错误信息
     */
    public static function handleNotFound(?string $message = null): void
    {
        $errorMessage = $message ?? '您访问的页面不存在';
        
        error_log("404 Not Found: " . ($_SERVER['REQUEST_URI'] ?? '/') . " - " . $errorMessage);
        
        self::renderErrorPage(404, $errorMessage);
        exit;
    }

    /**
     * 处理500服务器错误
     * @param \Throwable|null $exception 异常对象
     */
    public static function handleServerError(?\Throwable $exception = null): void
    {
        // 清除所有输出缓冲区
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // 设置HTTP状态码
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        
        if ($exception) {
            error_log("500 Server Error: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());
        } else {
            error_log("500 Server Error: Unknown error");
        }
        
        $errorMessage = '服务器内部错误，请稍后重试';
        
        if (get_env('APP_ENV', 'production') !== 'production' && $exception) {
            $errorMessage .= '<br><small>' . htmlspecialchars($exception->getMessage()) . '</small>';
        }
        
        self::renderErrorPage(500, $errorMessage);
        exit;
    }

    /**
     * 处理403禁止访问错误
     * @param string|null $message 错误信息
     */
    public static function handleForbidden(?string $message = null): void
    {
        $errorMessage = $message ?? '您没有权限访问此页面';
        
        error_log("403 Forbidden: " . ($_SERVER['REQUEST_URI'] ?? '/') . " - " . $errorMessage);
        
        self::renderErrorPage(403, $errorMessage);
        exit;
    }

    /**
     * 处理401未授权错误
     * @param string|null $message 错误信息
     */
    public static function handleUnauthorized(?string $message = null): void
    {
        $errorMessage = $message ?? '请先登录';
        
        error_log("401 Unauthorized: " . ($_SERVER['REQUEST_URI'] ?? '/') . " - " . $errorMessage);
        
        self::renderErrorPage(401, $errorMessage);
        exit;
    }

    /**
     * 渲染错误页面
     * @param int $code HTTP状态码
     * @param string $message 错误信息
     */
    private static function renderErrorPage(int $code, string $message): void
    {
        // 确保设置正确的HTTP状态码
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: text/html; charset=utf-8');
        }
        
        $viewPath = self::getErrorViewPath($code);
        
        if ($viewPath && file_exists($viewPath)) {
            try {
                // 如果是HTML文件，直接输出
                if (pathinfo($viewPath, PATHINFO_EXTENSION) === 'html') {
                    readfile($viewPath);
                    return;
                }
                
                // PHP文件需要extract变量
                extract([
                    'code' => $code,
                    'message' => $message,
                    'siteName' => get_env('APP_NAME', '星夜阁'),
                    'adminPath' => trim((string)get_env('ADMIN_PATH', 'admin'), '/')
                ]);
                require $viewPath;
            } catch (\Throwable $e) {
                // 如果错误页面本身出错，记录日志并使用默认页面
                error_log("Error rendering error page {$code}: " . $e->getMessage());
                echo self::getDefaultErrorPage($code, $message);
            }
        } else {
            // 如果找不到对应的错误页面文件，使用默认页面
            echo self::getDefaultErrorPage($code, $message);
        }
    }

    /**
     * 获取错误视图文件路径
     * @param int $code HTTP状态码
     * @return string|null
     */
    private static function getErrorViewPath(int $code): ?string
    {
        $possiblePaths = [
            __DIR__ . '/../views/errors/' . $code . '.html',
            __DIR__ . '/../views/errors/' . $code . '.php',
            __DIR__ . '/../admin/views/errors/' . $code . '.html',
            __DIR__ . '/../admin/views/errors/' . $code . '.php',
            __DIR__ . '/../frontend/views/errors/' . $code . '.html',
            __DIR__ . '/../frontend/views/errors/' . $code . '.php',
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }

    /**
     * 获取默认错误页面HTML
     * @param int $code HTTP状态码
     * @param string $message 错误信息
     * @return string
     */
    private static function getDefaultErrorPage(int $code, string $message): string
    {
        $siteName = htmlspecialchars(get_env('APP_NAME', '星夜阁'), ENT_QUOTES, 'UTF-8');
        $adminPath = trim((string)get_env('ADMIN_PATH', 'admin'), '/');
        
        $titles = [
            401 => '未授权',
            403 => '禁止访问',
            404 => '页面不存在',
            500 => '服务器错误',
        ];
        
        $title = $titles[$code] ?? '错误';
        $displayMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$code} - {$title} - {$siteName}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            color: #667eea;
            line-height: 1;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        .error-message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 15px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }
        .btn-secondary:hover {
            background: #e9e9e9;
        }
        .error-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">😕</div>
        <div class="error-code">{$code}</div>
        <div class="error-title">{$title}</div>
        <div class="error-message">{$displayMessage}</div>
        <div class="error-actions">
            <a href="/" class="btn btn-primary">返回首页</a>
            <a href="/{$adminPath}" class="btn btn-secondary">后台管理</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 记录错误日志
     * @param string $message 错误消息
     * @param string $level 日志级别
     */
    public static function log(string $message, string $level = 'error'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}";
        
        error_log($logMessage);
        
        $logFile = __DIR__ . '/../../storage/logs/error.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
    }

    /**
     * 注册全局异常处理器
     */
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * 处理未捕获的异常
     * @param \Throwable $exception
     */
    public static function handleException(\Throwable $exception): void
    {
        self::log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());
        
        if (php_sapi_name() === 'cli') {
            echo "Error: " . $exception->getMessage() . "\n";
            exit(1);
        }
        
        self::handleServerError($exception);
    }

    /**
     * 处理PHP错误
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorTypes = [
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
        ];
        
        $errorType = $errorTypes[$errno] ?? 'Unknown Error';
        self::log("{$errorType}: {$errstr} in {$errfile}:{$errline}");
        
        return true;
    }

    /**
     * 处理脚本终止
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleServerError(new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            ));
        }
    }
}
