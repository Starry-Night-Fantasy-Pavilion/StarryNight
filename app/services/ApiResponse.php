<?php

declare(strict_types=1);

namespace app\services;

use Core\Exceptions\ErrorCode;

/**
 * API响应服务
 * 统一API响应格式，整合错误码体系
 * 
 * @package app\services
 */
class ApiResponse
{
    /**
     * 返回成功响应
     *
     * @param mixed $data 响应数据
     * @param string $message 成功消息
     * @param int $code 错误码（默认为0表示成功）
     * @return array
     */
    public static function success($data = null, string $message = '操作成功', int $code = ErrorCode::SUCCESS): array
    {
        $response = [
            'code' => $code,
            'message' => $message,
            'success' => true,
            'timestamp' => time(),
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $response;
    }

    /**
     * 返回错误响应
     *
     * @param int $code 错误码
     * @param string|null $customMessage 自定义错误消息
     * @param array $data 附加数据
     * @return array
     */
    public static function error(int $code, ?string $customMessage = null, array $data = []): array
    {
        return [
            'code' => $code,
            'message' => ErrorCode::getMessage($code, $customMessage),
            'success' => false,
            'module' => ErrorCode::getModule($code),
            'data' => $data,
            'timestamp' => time(),
        ];
    }

    /**
     * 发送JSON响应并终止执行
     *
     * @param array $response 响应数据
     * @param int|null $httpStatus HTTP状态码（不指定则根据错误码自动判断）
     */
    public static function send(array $response, ?int $httpStatus = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $code = $response['code'] ?? 0;
        $httpStatus = $httpStatus ?? ErrorCode::getHttpStatus($code);
        http_response_code($httpStatus);
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 发送成功响应
     *
     * @param mixed $data 响应数据
     * @param string $message 成功消息
     */
    public static function sendSuccess($data = null, string $message = '操作成功'): void
    {
        self::send(self::success($data, $message));
    }

    /**
     * 发送错误响应
     *
     * @param int $code 错误码
     * @param string|null $customMessage 自定义错误消息
     * @param array $data 附加数据
     */
    public static function sendError(int $code, ?string $customMessage = null, array $data = []): void
    {
        self::send(self::error($code, $customMessage, $data));
    }

    /**
     * 快捷方法：参数错误
     */
    public static function paramError(?string $message = null, array $data = []): array
    {
        return self::error(ErrorCode::INVALID_PARAMETER, $message, $data);
    }

    /**
     * 快捷方法：未登录
     */
    public static function unauthorized(?string $message = null, array $data = []): array
    {
        return self::error(ErrorCode::AUTH_LOGIN_REQUIRED, $message, $data);
    }

    /**
     * 快捷方法：权限不足
     */
    public static function forbidden(?string $message = null, array $data = []): array
    {
        return self::error(ErrorCode::AUTH_PERMISSION_DENIED, $message, $data);
    }

    /**
     * 快捷方法：资源不存在
     */
    public static function notFound(?string $message = null, array $data = []): array
    {
        return self::error(ErrorCode::RESOURCE_NOT_FOUND, $message, $data);
    }

    /**
     * 快捷方法：验证失败
     */
    public static function validationFailed(?string $message = null, array $errors = []): array
    {
        return self::error(ErrorCode::VALIDATION_FAILED, $message, ['errors' => $errors]);
    }

    /**
     * 快捷方法：系统错误
     */
    public static function systemError(?string $message = null, array $data = []): array
    {
        return self::error(ErrorCode::SYSTEM_ERROR, $message, $data);
    }

    /**
     * 快捷方法：AI服务错误
     */
    public static function aiError(?string $message = null, array $data = []): array
    {
        return self::error(ErrorCode::AI_SERVICE_ERROR, $message, $data);
    }

    /**
     * 快捷方法：余额不足
     */
    public static function insufficientBalance(?string $message = null, array $data = []): array
    {
        return self::error(ErrorCode::AI_TOKEN_INSUFFICIENT, $message, $data);
    }

    /**
     * 快捷方法：VIP权限不足
     */
    public static function vipRequired(?string $message = null, array $data = []): array
    {
        return self::error(ErrorCode::AUTH_VIP_REQUIRED, $message, $data);
    }

    /**
     * 快捷方法：CSRF验证失败
     */
    public static function csrfError(?string $message = null, array $data = []): array
    {
        return self::error(ErrorCode::SECURITY_CSRF_TOKEN_MISMATCH, $message, $data);
    }

    /**
     * 快捷方法：请求过于频繁
     */
    public static function rateLimitExceeded(?string $message = null, array $data = []): array
    {
        return self::error(ErrorCode::SECURITY_RATE_LIMIT, $message, $data);
    }

    /**
     * 分页数据响应
     *
     * @param array $items 数据项
     * @param int $total 总数
     * @param int $page 当前页
     * @param int $pageSize 每页数量
     * @param string $message 消息
     * @return array
     */
    public static function paginated(array $items, int $total, int $page, int $pageSize, string $message = '获取成功'): array
    {
        return self::success([
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'total_pages' => ceil($total / $pageSize),
            ],
        ], $message);
    }

    /**
     * 发送分页响应
     */
    public static function sendPaginated(array $items, int $total, int $page, int $pageSize, string $message = '获取成功'): void
    {
        self::send(self::paginated($items, $total, $page, $pageSize, $message));
    }
}
