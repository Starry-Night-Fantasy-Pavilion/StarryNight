<?php

declare(strict_types=1);

namespace Core\Api;

use Core\Exceptions\ErrorCode;
use JsonSerializable;

/**
 * API响应类
 * 提供统一的API响应格式
 */
class ApiResponse implements JsonSerializable
{
    /**
     * 成功状态
     */
    protected bool $success = true;

    /**
     * 响应数据
     */
    protected mixed $data = null;

    /**
     * 错误码
     */
    protected int $code = 0;

    /**
     * 消息
     */
    protected string $message = '';

    /**
     * 错误详情
     */
    protected array $errors = [];

    /**
     * 分页信息
     */
    protected ?array $pagination = null;

    /**
     * 附加元数据
     */
    protected array $meta = [];

    /**
     * HTTP状态码
     */
    protected int $httpStatus = 200;

    /**
     * 响应头
     */
    protected array $headers = [];

    /**
     * API版本
     */
    protected string $version = '1.0';

    /**
     * 时间戳
     */
    protected int $timestamp;

    /**
     * 请求ID
     */
    protected ?string $requestId = null;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->timestamp = time();
    }

    /**
     * 创建成功响应
     *
     * @param mixed $data 数据
     * @param string $message 消息
     * @return static
     */
    public static function success(mixed $data = null, string $message = '操作成功'): static
    {
        $response = new static();
        $response->success = true;
        $response->data = $data;
        $response->message = $message;
        $response->httpStatus = 200;
        return $response;
    }

    /**
     * 创建错误响应
     *
     * @param int $code 错误码
     * @param string|null $message 消息
     * @param array $errors 错误详情
     * @return static
     */
    public static function error(int $code, ?string $message = null, array $errors = []): static
    {
        $response = new static();
        $response->success = false;
        $response->code = $code;
        $response->message = $message ?? ErrorCode::getMessage($code) ?? '操作失败';
        $response->errors = $errors;
        $response->httpStatus = self::getHttpStatus($code);
        return $response;
    }

    /**
     * 创建分页响应
     *
     * @param array $items 数据项
     * @param int $total 总数
     * @param int $page 当前页
     * @param int $perPage 每页数量
     * @param string $message 消息
     * @return static
     */
    public static function paginated(array $items, int $total, int $page, int $perPage, string $message = '获取成功'): static
    {
        $response = new static();
        $response->success = true;
        $response->data = $items;
        $response->message = $message;
        $response->pagination = [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 0,
            'has_more' => ($page * $perPage) < $total,
        ];
        return $response;
    }

    /**
     * 创建创建成功响应
     *
     * @param mixed $data 创建的数据
     * @param string $message 消息
     * @return static
     */
    public static function created(mixed $data = null, string $message = '创建成功'): static
    {
        $response = self::success($data, $message);
        $response->httpStatus = 201;
        return $response;
    }

    /**
     * 创建无内容响应
     *
     * @return static
     */
    public static function noContent(): static
    {
        $response = new static();
        $response->success = true;
        $response->httpStatus = 204;
        return $response;
    }

    /**
     * 创建验证错误响应
     *
     * @param array $errors 验证错误
     * @param string $message 消息
     * @return static
     */
    public static function validationError(array $errors, string $message = '验证失败'): static
    {
        return self::error(ErrorCode::VALIDATION_FAILED, $message, $errors);
    }

    /**
     * 创建未授权响应
     *
     * @param string $message 消息
     * @return static
     */
    public static function unauthorized(string $message = '未授权访问'): static
    {
        return self::error(ErrorCode::AUTH_FAILED, $message);
    }

    /**
     * 创建禁止访问响应
     *
     * @param string $message 消息
     * @return static
     */
    public static function forbidden(string $message = '禁止访问'): static
    {
        return self::error(ErrorCode::AUTH_PERMISSION_DENIED, $message);
    }

    /**
     * 创建未找到响应
     *
     * @param string $message 消息
     * @return static
     */
    public static function notFound(string $message = '资源未找到'): static
    {
        return self::error(ErrorCode::RESOURCE_NOT_FOUND, $message);
    }

    /**
     * 创建服务器错误响应
     *
     * @param string $message 消息
     * @return static
     */
    public static function serverError(string $message = '服务器内部错误'): static
    {
        return self::error(ErrorCode::SYSTEM_ERROR, $message);
    }

    /**
     * 设置数据
     *
     * @param mixed $data 数据
     * @return static
     */
    public function data(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 设置消息
     *
     * @param string $message 消息
     * @return static
     */
    public function message(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * 设置错误码
     *
     * @param int $code 错误码
     * @return static
     */
    public function code(int $code): static
    {
        $this->code = $code;
        return $this;
    }

    /**
     * 设置错误详情
     *
     * @param array $errors 错误详情
     * @return static
     */
    public function errors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * 设置分页信息
     *
     * @param int $total 总数
     * @param int $page 当前页
     * @param int $perPage 每页数量
     * @return static
     */
    public function pagination(int $total, int $page, int $perPage): static
    {
        $this->pagination = [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 0,
            'has_more' => ($page * $perPage) < $total,
        ];
        return $this;
    }

    /**
     * 设置元数据
     *
     * @param array $meta 元数据
     * @return static
     */
    public function meta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }

    /**
     * 设置HTTP状态码
     *
     * @param int $status HTTP状态码
     * @return static
     */
    public function status(int $status): static
    {
        $this->httpStatus = $status;
        return $this;
    }

    /**
     * 设置响应头
     *
     * @param string $key 键
     * @param string $value 值
     * @return static
     */
    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * 设置API版本
     *
     * @param string $version 版本
     * @return static
     */
    public function version(string $version): static
    {
        $this->version = $version;
        return $this;
    }

    /**
     * 设置请求ID
     *
     * @param string $requestId 请求ID
     * @return static
     */
    public function requestId(string $requestId): static
    {
        $this->requestId = $requestId;
        return $this;
    }

    /**
     * 转换为数组
     *
     * @return array
     */
    public function toArray(): array
    {
        $response = [
            'success' => $this->success,
            'code' => $this->code,
            'message' => $this->message,
            'timestamp' => $this->timestamp,
            'version' => $this->version,
        ];

        if ($this->requestId !== null) {
            $response['request_id'] = $this->requestId;
        }

        if ($this->success && $this->data !== null) {
            $response['data'] = $this->data;
        }

        if (!$this->success && !empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        if ($this->pagination !== null) {
            $response['pagination'] = $this->pagination;
        }

        if (!empty($this->meta)) {
            $response['meta'] = $this->meta;
        }

        return $response;
    }

    /**
     * JSON序列化
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 转换为JSON字符串
     *
     * @param int $options JSON选项
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this, $options);
    }

    /**
     * 发送响应
     *
     * @return void
     */
    public function send(): void
    {
        // 设置响应头
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        // 设置JSON响应头
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($this->httpStatus);

        // 输出JSON
        echo $this->toJson();

        // 终止执行
        exit;
    }

    /**
     * 发送成功响应
     *
     * @param mixed $data 数据
     * @param string $message 消息
     * @return void
     */
    public static function sendSuccess(mixed $data = null, string $message = '操作成功'): void
    {
        self::success($data, $message)->send();
    }

    /**
     * 发送错误响应
     *
     * @param int $code 错误码
     * @param string|null $message 消息
     * @param array $errors 错误详情
     * @return void
     */
    public static function sendError(int $code, ?string $message = null, array $errors = []): void
    {
        self::error($code, $message, $errors)->send();
    }

    /**
     * 发送分页响应
     *
     * @param array $items 数据项
     * @param int $total 总数
     * @param int $page 当前页
     * @param int $perPage 每页数量
     * @param string $message 消息
     * @return void
     */
    public static function sendPaginated(array $items, int $total, int $page, int $perPage, string $message = '获取成功'): void
    {
        self::paginated($items, $total, $page, $perPage, $message)->send();
    }

    /**
     * 根据错误码获取HTTP状态码
     *
     * @param int $code 错误码
     * @return int
     */
    protected static function getHttpStatus(int $code): int
    {
        // 根据错误码范围确定HTTP状态码
        if ($code >= 1000 && $code < 2000) {
            return 400; // 客户端错误
        }
        if ($code >= 2000 && $code < 3000) {
            return 401; // 认证错误
        }
        if ($code >= 3000 && $code < 4000) {
            return 403; // 权限错误
        }
        if ($code >= 4000 && $code < 5000) {
            return 404; // 资源错误
        }
        if ($code >= 5000 && $code < 6000) {
            return 500; // 服务器错误
        }

        return 400;
    }

    /**
     * 转换为字符串
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}
