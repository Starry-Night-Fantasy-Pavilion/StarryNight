<?php
/**
 * 应用统一异常类
 * 
 * @package Core\Exceptions
 * @version 1.0.0
 */

namespace Core\Exceptions;

use Exception;
use Throwable;

/**
 * 应用异常基类
 */
class AppException extends Exception
{
    /**
     * 错误码
     */
    protected int $errorCode;

    /**
     * 错误数据
     */
    protected array $data;

    /**
     * HTTP状态码
     */
    protected int $httpStatus;

    /**
     * 是否记录日志
     */
    protected bool $shouldLog = true;

    /**
     * 日志级别
     */
    protected string $logLevel = 'error';

    /**
     * 构造函数
     */
    public function __construct(
        int $errorCode,
        ?string $message = null,
        array $data = [],
        ?Throwable $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->data = $data;
        $this->httpStatus = ErrorCode::getHttpStatus($errorCode);

        $message = $message ?? ErrorCode::getMessage($errorCode);

        parent::__construct($message, $errorCode, $previous);
    }

    /**
     * 获取错误码
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * 获取HTTP状态码
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * 获取错误数据
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 设置错误数据
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 是否应该记录日志
     */
    public function shouldLog(): bool
    {
        return $this->shouldLog;
    }

    /**
     * 设置是否记录日志
     */
    public function setShouldLog(bool $shouldLog): self
    {
        $this->shouldLog = $shouldLog;
        return $this;
    }

    /**
     * 获取日志级别
     */
    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return ErrorCode::formatResponse($this->errorCode, $this->getMessage(), $this->data);
    }

    /**
     * 转换为JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    // ==================== 静态工厂方法 ====================

    /**
     * 创建认证异常
     */
    public static function auth(?string $message = null, int $code = ErrorCode::AUTH_FAILED): self
    {
        return new self($code, $message);
    }

    /**
     * 创建权限异常
     */
    public static function forbidden(?string $message = null): self
    {
        return new self(ErrorCode::AUTH_PERMISSION_DENIED, $message);
    }

    /**
     * 创建验证异常
     */
    public static function validation(array $errors, ?string $message = null): self
    {
        return new self(ErrorCode::VALIDATION_FAILED, $message, ['errors' => $errors]);
    }

    /**
     * 创建资源不存在异常
     */
    public static function notFound(string $resource = '资源', ?string $message = null): self
    {
        return new self(ErrorCode::RESOURCE_NOT_FOUND, $message ?? "{$resource}不存在");
    }

    /**
     * 创建参数错误异常
     */
    public static function invalidParam(string $param, ?string $message = null): self
    {
        return new self(ErrorCode::INVALID_PARAMETER, $message, ['param' => $param]);
    }

    /**
     * 创建系统异常
     */
    public static function system(?string $message = null, ?Throwable $previous = null): self
    {
        $exception = new self(ErrorCode::SYSTEM_ERROR, $message, [], $previous);
        $exception->logLevel = 'critical';
        return $exception;
    }
}

/**
 * 认证异常
 */
class AuthenticationException extends AppException
{
    public function __construct(?string $message = null, int $code = ErrorCode::AUTH_FAILED)
    {
        parent::__construct($code, $message);
        $this->shouldLog = false;
    }
}

/**
 * 权限异常
 */
class AuthorizationException extends AppException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(ErrorCode::AUTH_PERMISSION_DENIED, $message);
        $this->shouldLog = false;
    }
}

/**
 * 验证异常
 */
class ValidationException extends AppException
{
    protected array $errors = [];

    public function __construct(array $errors, ?string $message = null)
    {
        $this->errors = $errors;
        parent::__construct(ErrorCode::VALIDATION_FAILED, $message, ['errors' => $errors]);
        $this->shouldLog = false;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * 资源不存在异常
 */
class NotFoundException extends AppException
{
    public function __construct(string $resource = '资源', ?string $message = null)
    {
        parent::__construct(ErrorCode::RESOURCE_NOT_FOUND, $message ?? "{$resource}不存在");
        $this->shouldLog = false;
    }
}

/**
 * 速率限制异常
 */
class RateLimitException extends AppException
{
    protected int $retryAfter;

    public function __construct(int $retryAfter = 60, ?string $message = null)
    {
        $this->retryAfter = $retryAfter;
        parent::__construct(ErrorCode::RATE_LIMIT_EXCEEDED, $message, ['retry_after' => $retryAfter]);
        $this->shouldLog = false;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}

/**
 * 安全异常
 */
class SecurityException extends AppException
{
    public function __construct(int $code, ?string $message = null, array $data = [])
    {
        parent::__construct($code, $message, $data);
        $this->logLevel = 'warning';
    }
}

/**
 * CSRF异常
 */
class CsrfException extends SecurityException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(ErrorCode::SECURITY_CSRF_TOKEN_MISMATCH, $message);
    }
}

/**
 * XSS异常
 */
class XssException extends SecurityException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(ErrorCode::SECURITY_XSS_DETECTED, $message);
    }
}

/**
 * 业务异常
 */
class BusinessException extends AppException
{
    public function __construct(int $code, ?string $message = null, array $data = [])
    {
        parent::__construct($code, $message, $data);
        $this->shouldLog = false;
    }
}

/**
 * AI服务异常
 */
class AIServiceException extends AppException
{
    public function __construct(int $code = ErrorCode::AI_SERVICE_ERROR, ?string $message = null, array $data = [])
    {
        parent::__construct($code, $message, $data);
    }
}

/**
 * 支付异常
 */
class PaymentException extends AppException
{
    public function __construct(int $code = ErrorCode::PAYMENT_FAILED, ?string $message = null, array $data = [])
    {
        parent::__construct($code, $message, $data);
    }
}
