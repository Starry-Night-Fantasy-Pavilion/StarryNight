<?php
/**
 * 日志服务
 * 实现 PSR-3 日志接口
 *
 * @package Core\Services
 */

namespace Core\Services;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

class LoggerService implements LoggerInterface
{
    private string $logPath;
    private string $logLevel;
    private array $levels = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7
    ];

    public function __construct(?string $logPath = null, string $logLevel = 'info')
    {
        // 验证日志级别
        if (!isset($this->levels[$logLevel])) {
            throw new InvalidArgumentException("无效的日志级别: {$logLevel}。有效值为: " . implode(', ', array_keys($this->levels)));
        }
        
        $this->logPath = $logPath ?? dirname(__DIR__, 2) . '/storage/logs';
        $this->logLevel = $logLevel;
        
        // 创建日志目录
        if (!is_dir($this->logPath)) {
            if (!mkdir($this->logPath, 0755, true) && !is_dir($this->logPath)) {
                throw new \RuntimeException("无法创建日志目录: {$this->logPath}");
            }
        }
        
        // 检查目录是否可写
        if (!is_writable($this->logPath)) {
            throw new \RuntimeException("日志目录不可写: {$this->logPath}");
        }
    }

    /**
     * 系统不可用
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, (string) $message, $context);
    }

    /**
     * 必须立即采取行动
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, (string) $message, $context);
    }

    /**
     * 临界条件
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, (string) $message, $context);
    }

    /**
     * 运行时错误，不需要立即处理但应该被记录
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, (string) $message, $context);
    }

    /**
     * 非错误但值得注意的事件
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, (string) $message, $context);
    }

    /**
     * 正常但值得注意的事件
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, (string) $message, $context);
    }

    /**
     * 有趣的事件
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, (string) $message, $context);
    }

    /**
     * 详细的调试信息
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, (string) $message, $context);
    }

    /**
     * 写入日志
     *
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     * @throws InvalidArgumentException 无效的日志级别
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // 验证日志级别
        $level = (string)$level;
        if (!isset($this->levels[$level])) {
            throw new InvalidArgumentException("无效的日志级别: {$level}");
        }
        
        // 检查日志级别
        if ($this->levels[$level] < $this->levels[$this->logLevel]) {
            return;
        }

        // 处理消息中的占位符
        $message = $this->interpolate($message, $context);

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        $logLine = sprintf(
            "[%s] %s.%s: %s %s\n",
            $timestamp,
            strtoupper($level),
            $this->getCallerInfo(),
            $message,
            $contextStr
        );

        $file = $this->logPath . '/' . date('Y-m-d') . '.log';
        file_put_contents($file, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * 获取调用者信息
     */
    private function getCallerInfo(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $caller = $trace[3] ?? $trace[2] ?? null;
        
        if ($caller) {
            return basename($caller['file'] ?? 'unknown') . ':' . ($caller['line'] ?? 0);
        }
        
        return 'unknown:0';
    }

    /**
     * 插值消息中的占位符
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return string
     */
    private function interpolate(string|\Stringable $message, array $context = []): string
    {
        if (empty($context)) {
            return (string) $message;
        }

        $replace = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $replace['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr((string) $message, $replace);
    }
}
