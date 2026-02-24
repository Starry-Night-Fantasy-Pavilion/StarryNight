<?php
/**
 * 统一异常处理器
 * 
 * @package Core\Exceptions
 * @version 1.0.0
 */

namespace Core\Exceptions;

use Core\Services\LoggerService;
use Throwable;

/**
 * 异常处理器
 */
class ExceptionHandler
{
    /**
     * 是否调试模式
     */
    protected bool $debug;

    /**
     * 日志服务
     */
    protected ?LoggerService $logger;

    /**
     * 已注册的异常处理器
     */
    protected array $handlers = [];

    /**
     * 构造函数
     */
    public function __construct(bool $debug = false, ?LoggerService $logger = null)
    {
        $this->debug = $debug;
        $this->logger = $logger;
    }

    /**
     * 注册异常处理器
     */
    public function register(): void
    {
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * 处理异常
     */
    public function handleException(Throwable $exception): void
    {
        // 记录日志
        $this->logException($exception);

        // 发送响应
        $this->sendResponse($exception);
    }

    /**
     * 处理错误
     */
    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        // 将错误转换为异常
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        return true;
    }

    /**
     * 处理致命错误
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $exception = new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            $this->handleException($exception);
        }
    }

    /**
     * 记录异常日志
     */
    protected function logException(Throwable $exception): void
    {
        if (!$this->logger) {
            return;
        }

        // AppException可以控制是否记录日志
        if ($exception instanceof AppException && !$exception->shouldLog()) {
            return;
        }

        $level = $this->getLogLevel($exception);
        $context = $this->getLogContext($exception);

        $this->logger->log($level, $exception->getMessage(), $context);
    }

    /**
     * 获取日志级别
     */
    protected function getLogLevel(Throwable $exception): string
    {
        if ($exception instanceof AppException) {
            return $exception->getLogLevel();
        }

        // 根据异常类型判断日志级别
        if ($exception instanceof \ErrorException) {
            return 'error';
        }

        return 'error';
    }

    /**
     * 获取日志上下文
     */
    protected function getLogContext(Throwable $exception): array
    {
        $context = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        if ($exception instanceof AppException) {
            $context['error_code'] = $exception->getErrorCode();
            $context['data'] = $exception->getData();
        }

        // 添加请求信息
        if (isset($_SERVER['REQUEST_URI'])) {
            $context['request_uri'] = $_SERVER['REQUEST_URI'];
            $context['request_method'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        return $context;
    }

    /**
     * 发送响应
     */
    protected function sendResponse(Throwable $exception): void
    {
        // 确保没有输出
        if (!headers_sent()) {
            // 清除之前的输出缓冲
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // 设置HTTP状态码
            $httpStatus = $this->getHttpStatus($exception);
            http_response_code($httpStatus);

            // 设置内容类型
            header('Content-Type: application/json; charset=utf-8');
        }

        // 构建响应数据
        $response = $this->buildResponse($exception);

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * 获取HTTP状态码
     */
    protected function getHttpStatus(Throwable $exception): int
    {
        if ($exception instanceof AppException) {
            return $exception->getHttpStatus();
        }

        return 500;
    }

    /**
     * 构建响应数据
     */
    protected function buildResponse(Throwable $exception): array
    {
        if ($exception instanceof AppException) {
            $response = $exception->toArray();
        } else {
            $response = ErrorCode::formatResponse(
                ErrorCode::SYSTEM_ERROR,
                $this->debug ? $exception->getMessage() : '系统错误，请稍后重试'
            );
        }

        // 调试模式下添加额外信息
        if ($this->debug) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        return $response;
    }

    /**
     * 注册自定义异常处理器
     */
    public function registerHandler(string $exceptionClass, callable $handler): self
    {
        $this->handlers[$exceptionClass] = $handler;
        return $this;
    }

    /**
     * 设置调试模式
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * 设置日志服务
     */
    public function setLogger(LoggerService $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * 将异常转换为响应数组
     */
    public static function toResponse(Throwable $exception, bool $debug = false): array
    {
        if ($exception instanceof AppException) {
            $response = $exception->toArray();
        } else {
            $response = ErrorCode::formatResponse(
                ErrorCode::SYSTEM_ERROR,
                $debug ? $exception->getMessage() : '系统错误，请稍后重试'
            );
        }

        if ($debug) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return $response;
    }
}
