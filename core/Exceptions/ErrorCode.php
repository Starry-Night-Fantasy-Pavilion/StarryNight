<?php
/**
 * 统一错误码定义
 * 
 * 错误码格式：6位数字
 * 前两位：模块代码
 * 中两位：业务代码
 * 后两位：具体错误代码
 * 
 * @package Core\Exceptions
 * @version 1.0.0
 */

namespace Core\Exceptions;

/**
 * 错误码常量定义
 */
class ErrorCode
{
    // ==================== 通用错误 00xxxx ====================
    const SUCCESS = 0;
    const UNKNOWN_ERROR = 1;
    const INVALID_REQUEST = 2;
    const INVALID_PARAMETER = 3;
    const RESOURCE_NOT_FOUND = 4;
    const METHOD_NOT_ALLOWED = 5;
    const RATE_LIMIT_EXCEEDED = 6;
    const SERVICE_UNAVAILABLE = 7;
    const MAINTENANCE_MODE = 8;

    // ==================== 认证错误 01xxxx ====================
    const AUTH_FAILED = 10001;
    const AUTH_TOKEN_EXPIRED = 10002;
    const AUTH_TOKEN_INVALID = 10003;
    const AUTH_TOKEN_MISSING = 10004;
    const AUTH_PASSWORD_WRONG = 10005;
    const AUTH_USER_NOT_FOUND = 10006;
    const AUTH_USER_DISABLED = 10007;
    const AUTH_USER_FROZEN = 10008;
    const AUTH_EMAIL_NOT_VERIFIED = 10009;
    const AUTH_PHONE_NOT_VERIFIED = 10010;
    const AUTH_LOGIN_REQUIRED = 10011;
    const AUTH_PERMISSION_DENIED = 10012;
    const AUTH_ADMIN_REQUIRED = 10013;
    const AUTH_VIP_REQUIRED = 10014;

    // ==================== 用户错误 02xxxx ====================
    const USER_NOT_FOUND = 20001;
    const USER_ALREADY_EXISTS = 20002;
    const USER_EMAIL_EXISTS = 20003;
    const USER_PHONE_EXISTS = 20004;
    const USER_PASSWORD_WEAK = 20005;
    const USER_PASSWORD_SAME = 20006;
    const USER_PROFILE_INCOMPLETE = 20007;
    const USER_AVATAR_INVALID = 20008;
    const USER_NICKNAME_INVALID = 20009;

    // ==================== 验证错误 03xxxx ====================
    const VALIDATION_FAILED = 30001;
    const VALIDATION_REQUIRED = 30002;
    const VALIDATION_EMAIL = 30003;
    const VALIDATION_PHONE = 30004;
    const VALIDATION_URL = 30005;
    const VALIDATION_LENGTH = 30006;
    const VALIDATION_RANGE = 30007;
    const VALIDATION_UNIQUE = 30008;
    const VALIDATION_EXISTS = 30009;
    const VALIDATION_DATE = 30010;
    const VALIDATION_FILE = 30011;
    const VALIDATION_IMAGE = 30012;

    // ==================== 文件错误 04xxxx ====================
    const FILE_NOT_FOUND = 40001;
    const FILE_UPLOAD_FAILED = 40002;
    const FILE_TYPE_NOT_ALLOWED = 40003;
    const FILE_SIZE_EXCEEDED = 40004;
    const FILE_DIMENSION_INVALID = 40005;
    const FILE_STORAGE_ERROR = 40006;
    const FILE_DELETE_FAILED = 40007;

    // ==================== 小说错误 05xxxx ====================
    const NOVEL_NOT_FOUND = 50001;
    const NOVEL_ACCESS_DENIED = 50002;
    const NOVEL_LIMIT_EXCEEDED = 50003;
    const NOVEL_CHAPTER_NOT_FOUND = 50004;
    const NOVEL_CHAPTER_LIMIT = 50005;
    const NOVEL_CHARACTER_NOT_FOUND = 50006;
    const NOVEL_OUTLINE_NOT_FOUND = 50007;

    // ==================== AI错误 06xxxx ====================
    const AI_SERVICE_ERROR = 60001;
    const AI_MODEL_NOT_FOUND = 60002;
    const AI_CHANNEL_DISABLED = 60003;
    const AI_QUOTA_EXCEEDED = 60004;
    const AI_TOKEN_INSUFFICIENT = 60005;
    const AI_GENERATION_FAILED = 60006;
    const AI_CONTENT_FILTERED = 60007;
    const AI_TIMEOUT = 60008;

    // ==================== 支付错误 07xxxx ====================
    const PAYMENT_FAILED = 70001;
    const PAYMENT_ORDER_NOT_FOUND = 70002;
    const PAYMENT_ORDER_EXPIRED = 70003;
    const PAYMENT_ORDER_PAID = 70004;
    const PAYMENT_ORDER_CANCELLED = 70005;
    const PAYMENT_AMOUNT_INVALID = 70006;
    const PAYMENT_CHANNEL_ERROR = 70007;
    const PAYMENT_REFUND_FAILED = 70008;

    // ==================== 会员错误 08xxxx ====================
    const VIP_NOT_MEMBER = 80001;
    const VIP_EXPIRED = 80002;
    const VIP_LEVEL_INSUFFICIENT = 80003;
    const VIP_BENEFIT_NOT_AVAILABLE = 80004;
    const VIP_PURCHASE_FAILED = 80005;

    // ==================== 存储错误 09xxxx ====================
    const STORAGE_QUOTA_EXCEEDED = 90001;
    const STORAGE_FILE_NOT_FOUND = 90002;
    const STORAGE_UPLOAD_FAILED = 90003;

    // ==================== 安全错误 10xxxx ====================
    const SECURITY_CSRF_TOKEN_MISMATCH = 100001;
    const SECURITY_XSS_DETECTED = 100002;
    const SECURITY_SQL_INJECTION = 100003;
    const SECURITY_RATE_LIMIT = 100004;
    const SECURITY_IP_BLOCKED = 100005;
    const SECURITY_SUSPICIOUS_ACTIVITY = 100006;

    // ==================== 系统错误 99xxxx ====================
    const SYSTEM_ERROR = 990001;
    const SYSTEM_DATABASE_ERROR = 990002;
    const SYSTEM_CACHE_ERROR = 990003;
    const SYSTEM_CONFIG_ERROR = 990004;
    const SYSTEM_DEPENDENCY_ERROR = 990005;

    /**
     * 错误码消息映射
     */
    private static array $messages = [
        // 通用
        self::SUCCESS => '操作成功',
        self::UNKNOWN_ERROR => '未知错误',
        self::INVALID_REQUEST => '无效的请求',
        self::INVALID_PARAMETER => '参数错误',
        self::RESOURCE_NOT_FOUND => '资源不存在',
        self::METHOD_NOT_ALLOWED => '请求方法不允许',
        self::RATE_LIMIT_EXCEEDED => '请求过于频繁',
        self::SERVICE_UNAVAILABLE => '服务暂不可用',
        self::MAINTENANCE_MODE => '系统维护中',

        // 认证
        self::AUTH_FAILED => '认证失败',
        self::AUTH_TOKEN_EXPIRED => '登录已过期',
        self::AUTH_TOKEN_INVALID => '无效的登录凭证',
        self::AUTH_TOKEN_MISSING => '缺少登录凭证',
        self::AUTH_PASSWORD_WRONG => '密码错误',
        self::AUTH_USER_NOT_FOUND => '用户不存在',
        self::AUTH_USER_DISABLED => '账号已被禁用',
        self::AUTH_USER_FROZEN => '账号已被冻结',
        self::AUTH_EMAIL_NOT_VERIFIED => '邮箱未验证',
        self::AUTH_PHONE_NOT_VERIFIED => '手机未验证',
        self::AUTH_LOGIN_REQUIRED => '请先登录',
        self::AUTH_PERMISSION_DENIED => '权限不足',
        self::AUTH_ADMIN_REQUIRED => '需要管理员权限',
        self::AUTH_VIP_REQUIRED => '需要VIP会员',

        // 用户
        self::USER_NOT_FOUND => '用户不存在',
        self::USER_ALREADY_EXISTS => '用户已存在',
        self::USER_EMAIL_EXISTS => '邮箱已被使用',
        self::USER_PHONE_EXISTS => '手机号已被使用',
        self::USER_PASSWORD_WEAK => '密码强度不足',
        self::USER_PASSWORD_SAME => '新密码不能与旧密码相同',
        self::USER_PROFILE_INCOMPLETE => '用户资料不完整',
        self::USER_AVATAR_INVALID => '头像格式无效',
        self::USER_NICKNAME_INVALID => '昵称格式无效',

        // 验证
        self::VALIDATION_FAILED => '验证失败',
        self::VALIDATION_REQUIRED => '必填字段不能为空',
        self::VALIDATION_EMAIL => '邮箱格式不正确',
        self::VALIDATION_PHONE => '手机号格式不正确',
        self::VALIDATION_URL => 'URL格式不正确',
        self::VALIDATION_LENGTH => '长度不符合要求',
        self::VALIDATION_RANGE => '数值超出范围',
        self::VALIDATION_UNIQUE => '该值已存在',
        self::VALIDATION_EXISTS => '该值不存在',
        self::VALIDATION_DATE => '日期格式不正确',
        self::VALIDATION_FILE => '文件验证失败',
        self::VALIDATION_IMAGE => '图片验证失败',

        // 文件
        self::FILE_NOT_FOUND => '文件不存在',
        self::FILE_UPLOAD_FAILED => '文件上传失败',
        self::FILE_TYPE_NOT_ALLOWED => '不支持的文件类型',
        self::FILE_SIZE_EXCEEDED => '文件大小超出限制',
        self::FILE_DIMENSION_INVALID => '图片尺寸不符合要求',
        self::FILE_STORAGE_ERROR => '文件存储错误',
        self::FILE_DELETE_FAILED => '文件删除失败',

        // 小说
        self::NOVEL_NOT_FOUND => '小说不存在',
        self::NOVEL_ACCESS_DENIED => '无权访问该小说',
        self::NOVEL_LIMIT_EXCEEDED => '已达到小说数量上限',
        self::NOVEL_CHAPTER_NOT_FOUND => '章节不存在',
        self::NOVEL_CHAPTER_LIMIT => '已达到章节数量上限',
        self::NOVEL_CHARACTER_NOT_FOUND => '角色不存在',
        self::NOVEL_OUTLINE_NOT_FOUND => '大纲不存在',

        // AI
        self::AI_SERVICE_ERROR => 'AI服务错误',
        self::AI_MODEL_NOT_FOUND => 'AI模型不存在',
        self::AI_CHANNEL_DISABLED => 'AI渠道已禁用',
        self::AI_QUOTA_EXCEEDED => 'AI配额已用尽',
        self::AI_TOKEN_INSUFFICIENT => '星夜币余额不足',
        self::AI_GENERATION_FAILED => 'AI生成失败',
        self::AI_CONTENT_FILTERED => '内容被过滤',
        self::AI_TIMEOUT => 'AI请求超时',

        // 支付
        self::PAYMENT_FAILED => '支付失败',
        self::PAYMENT_ORDER_NOT_FOUND => '订单不存在',
        self::PAYMENT_ORDER_EXPIRED => '订单已过期',
        self::PAYMENT_ORDER_PAID => '订单已支付',
        self::PAYMENT_ORDER_CANCELLED => '订单已取消',
        self::PAYMENT_AMOUNT_INVALID => '金额无效',
        self::PAYMENT_CHANNEL_ERROR => '支付渠道错误',
        self::PAYMENT_REFUND_FAILED => '退款失败',

        // 会员
        self::VIP_NOT_MEMBER => '非会员用户',
        self::VIP_EXPIRED => '会员已过期',
        self::VIP_LEVEL_INSUFFICIENT => '会员等级不足',
        self::VIP_BENEFIT_NOT_AVAILABLE => '该权益不可用',
        self::VIP_PURCHASE_FAILED => '会员购买失败',

        // 存储
        self::STORAGE_QUOTA_EXCEEDED => '存储空间不足',
        self::STORAGE_FILE_NOT_FOUND => '存储文件不存在',
        self::STORAGE_UPLOAD_FAILED => '存储上传失败',

        // 安全
        self::SECURITY_CSRF_TOKEN_MISMATCH => 'CSRF验证失败，请刷新页面重试',
        self::SECURITY_XSS_DETECTED => '检测到非法内容',
        self::SECURITY_SQL_INJECTION => '检测到非法请求',
        self::SECURITY_RATE_LIMIT => '操作过于频繁，请稍后再试',
        self::SECURITY_IP_BLOCKED => 'IP已被封禁',
        self::SECURITY_SUSPICIOUS_ACTIVITY => '检测到可疑活动',

        // 系统
        self::SYSTEM_ERROR => '系统错误',
        self::SYSTEM_DATABASE_ERROR => '数据库错误',
        self::SYSTEM_CACHE_ERROR => '缓存错误',
        self::SYSTEM_CONFIG_ERROR => '配置错误',
        self::SYSTEM_DEPENDENCY_ERROR => '依赖错误',
    ];

    /**
     * HTTP状态码映射
     */
    private static array $httpStatus = [
        // 2xx
        self::SUCCESS => 200,

        // 4xx
        self::INVALID_REQUEST => 400,
        self::INVALID_PARAMETER => 400,
        self::RESOURCE_NOT_FOUND => 404,
        self::METHOD_NOT_ALLOWED => 405,
        self::RATE_LIMIT_EXCEEDED => 429,

        // 认证相关 401
        self::AUTH_FAILED => 401,
        self::AUTH_TOKEN_EXPIRED => 401,
        self::AUTH_TOKEN_INVALID => 401,
        self::AUTH_TOKEN_MISSING => 401,
        self::AUTH_PASSWORD_WRONG => 401,
        self::AUTH_LOGIN_REQUIRED => 401,

        // 权限相关 403
        self::AUTH_PERMISSION_DENIED => 403,
        self::AUTH_ADMIN_REQUIRED => 403,
        self::AUTH_VIP_REQUIRED => 403,
        self::AUTH_USER_DISABLED => 403,
        self::AUTH_USER_FROZEN => 403,

        // 用户相关 404/409
        self::AUTH_USER_NOT_FOUND => 404,
        self::USER_NOT_FOUND => 404,
        self::USER_ALREADY_EXISTS => 409,
        self::USER_EMAIL_EXISTS => 409,
        self::USER_PHONE_EXISTS => 409,

        // 验证相关 422
        self::VALIDATION_FAILED => 422,
        self::VALIDATION_REQUIRED => 422,
        self::VALIDATION_EMAIL => 422,
        self::VALIDATION_PHONE => 422,
        self::VALIDATION_URL => 422,
        self::VALIDATION_LENGTH => 422,
        self::VALIDATION_RANGE => 422,
        self::VALIDATION_UNIQUE => 422,
        self::VALIDATION_EXISTS => 422,
        self::VALIDATION_DATE => 422,
        self::VALIDATION_FILE => 422,
        self::VALIDATION_IMAGE => 422,

        // 文件相关 400/404/413
        self::FILE_NOT_FOUND => 404,
        self::FILE_UPLOAD_FAILED => 400,
        self::FILE_TYPE_NOT_ALLOWED => 400,
        self::FILE_SIZE_EXCEEDED => 413,

        // 安全相关 400/403/429
        self::SECURITY_CSRF_TOKEN_MISMATCH => 403,
        self::SECURITY_XSS_DETECTED => 400,
        self::SECURITY_SQL_INJECTION => 400,
        self::SECURITY_RATE_LIMIT => 429,
        self::SECURITY_IP_BLOCKED => 403,
        self::SECURITY_SUSPICIOUS_ACTIVITY => 403,

        // 系统相关 500
        self::SYSTEM_ERROR => 500,
        self::SYSTEM_DATABASE_ERROR => 500,
        self::SYSTEM_CACHE_ERROR => 500,
        self::SYSTEM_CONFIG_ERROR => 500,
        self::SYSTEM_DEPENDENCY_ERROR => 500,
        self::UNKNOWN_ERROR => 500,
        self::SERVICE_UNAVAILABLE => 503,
        self::MAINTENANCE_MODE => 503,
    ];

    /**
     * 获取错误消息
     */
    public static function getMessage(int $code, ?string $customMessage = null): string
    {
        if ($customMessage !== null) {
            return $customMessage;
        }
        return self::$messages[$code] ?? self::$messages[self::UNKNOWN_ERROR];
    }

    /**
     * 获取HTTP状态码
     */
    public static function getHttpStatus(int $code): int
    {
        return self::$httpStatus[$code] ?? 500;
    }

    /**
     * 获取错误码模块
     */
    public static function getModule(int $code): string
    {
        $moduleCode = (int)($code / 10000);
        $modules = [
            0 => 'common',
            1 => 'auth',
            2 => 'user',
            3 => 'validation',
            4 => 'file',
            5 => 'novel',
            6 => 'ai',
            7 => 'payment',
            8 => 'vip',
            9 => 'storage',
            10 => 'security',
            99 => 'system',
        ];
        return $modules[$moduleCode] ?? 'unknown';
    }

    /**
     * 判断是否为客户端错误
     */
    public static function isClientError(int $code): bool
    {
        $httpStatus = self::getHttpStatus($code);
        return $httpStatus >= 400 && $httpStatus < 500;
    }

    /**
     * 判断是否为服务端错误
     */
    public static function isServerError(int $code): bool
    {
        $httpStatus = self::getHttpStatus($code);
        return $httpStatus >= 500;
    }

    /**
     * 格式化错误响应
     */
    public static function formatResponse(int $code, ?string $message = null, array $data = []): array
    {
        return [
            'code' => $code,
            'message' => self::getMessage($code, $message),
            'module' => self::getModule($code),
            'data' => $data,
            'timestamp' => time(),
        ];
    }
}
