<?php

declare(strict_types=1);

namespace app\services;

/**
 * 系统事件定义
 * 
 * 定义系统中所有可用的事件名称
 * 
 * @package app\services
 */
class SystemEvents
{
    // ==================== 用户事件 ====================
    /** 用户注册成功 */
    const USER_REGISTERED = 'user.registered';
    /** 用户登录成功 */
    const USER_LOGIN = 'user.login';
    /** 用户登出 */
    const USER_LOGOUT = 'user.logout';
    /** 用户信息更新 */
    const USER_UPDATED = 'user.updated';
    /** 用户删除 */
    const USER_DELETED = 'user.deleted';
    /** 用户密码重置 */
    const USER_PASSWORD_RESET = 'user.password_reset';
    /** 用户邮箱验证 */
    const USER_EMAIL_VERIFIED = 'user.email_verified';
    /** 用户VIP升级 */
    const USER_VIP_UPGRADED = 'user.vip_upgraded';
    /** 用户VIP过期 */
    const USER_VIP_EXPIRED = 'user.vip_expired';

    // ==================== 小说事件 ====================
    /** 小说创建 */
    const NOVEL_CREATED = 'novel.created';
    /** 小说更新 */
    const NOVEL_UPDATED = 'novel.updated';
    /** 小说删除 */
    const NOVEL_DELETED = 'novel.deleted';
    /** 小说发布 */
    const NOVEL_PUBLISHED = 'novel.published';
    /** 章节创建 */
    const CHAPTER_CREATED = 'chapter.created';
    /** 章节更新 */
    const CHAPTER_UPDATED = 'chapter.updated';
    /** 章节删除 */
    const CHAPTER_DELETED = 'chapter.deleted';

    // ==================== AI事件 ====================
    /** AI请求开始 */
    const AI_REQUEST_START = 'ai.request.start';
    /** AI请求成功 */
    const AI_REQUEST_SUCCESS = 'ai.request.success';
    /** AI请求失败 */
    const AI_REQUEST_FAILED = 'ai.request.failed';
    /** AI内容生成完成 */
    const AI_CONTENT_GENERATED = 'ai.content.generated';
    /** Token消耗 */
    const AI_TOKEN_CONSUMED = 'ai.token.consumed';

    // ==================== 支付事件 ====================
    /** 订单创建 */
    const ORDER_CREATED = 'order.created';
    /** 订单支付成功 */
    const ORDER_PAID = 'order.paid';
    /** 订单取消 */
    const ORDER_CANCELLED = 'order.cancelled';
    /** 订单退款 */
    const ORDER_REFUNDED = 'order.refunded';
    /** 充值成功 */
    const RECHARGE_SUCCESS = 'recharge.success';

    // ==================== 会员事件 ====================
    /** 会员购买 */
    const MEMBERSHIP_PURCHASED = 'membership.purchased';
    /** 会员续费 */
    const MEMBERSHIP_RENEWED = 'membership.renewed';
    /** 会员过期 */
    const MEMBERSHIP_EXPIRED = 'membership.expired';

    // ==================== 内容事件 ====================
    /** 内容发布 */
    const CONTENT_PUBLISHED = 'content.published';
    /** 内容审核通过 */
    const CONTENT_APPROVED = 'content.approved';
    /** 内容审核拒绝 */
    const CONTENT_REJECTED = 'content.rejected';
    /** 评论创建 */
    const COMMENT_CREATED = 'comment.created';

    // ==================== 系统事件 ====================
    /** 系统启动 */
    const SYSTEM_BOOT = 'system.boot';
    /** 系统维护开始 */
    const SYSTEM_MAINTENANCE_START = 'system.maintenance.start';
    /** 系统维护结束 */
    const SYSTEM_MAINTENANCE_END = 'system.maintenance.end';
    /** 缓存清除 */
    const CACHE_CLEARED = 'cache.cleared';
    /** 配置更新 */
    const CONFIG_UPDATED = 'config.updated';

    // ==================== 安全事件 ====================
    /** 登录失败 */
    const SECURITY_LOGIN_FAILED = 'security.login_failed';
    /** 可疑活动检测 */
    const SECURITY_SUSPICIOUS_ACTIVITY = 'security.suspicious_activity';
    /** IP封禁 */
    const SECURITY_IP_BLOCKED = 'security.ip_blocked';
    /** CSRF验证失败 */
    const SECURITY_CSRF_FAILED = 'security.csrf_failed';
    /** XSS检测 */
    const SECURITY_XSS_DETECTED = 'security.xss_detected';

    /**
     * 获取所有事件列表
     * 
     * @return array
     */
    public static function getAllEvents(): array
    {
        return [
            // 用户事件
            self::USER_REGISTERED => '用户注册成功',
            self::USER_LOGIN => '用户登录成功',
            self::USER_LOGOUT => '用户登出',
            self::USER_UPDATED => '用户信息更新',
            self::USER_DELETED => '用户删除',
            self::USER_PASSWORD_RESET => '用户密码重置',
            self::USER_EMAIL_VERIFIED => '用户邮箱验证',
            self::USER_VIP_UPGRADED => '用户VIP升级',
            self::USER_VIP_EXPIRED => '用户VIP过期',

            // 小说事件
            self::NOVEL_CREATED => '小说创建',
            self::NOVEL_UPDATED => '小说更新',
            self::NOVEL_DELETED => '小说删除',
            self::NOVEL_PUBLISHED => '小说发布',
            self::CHAPTER_CREATED => '章节创建',
            self::CHAPTER_UPDATED => '章节更新',
            self::CHAPTER_DELETED => '章节删除',

            // AI事件
            self::AI_REQUEST_START => 'AI请求开始',
            self::AI_REQUEST_SUCCESS => 'AI请求成功',
            self::AI_REQUEST_FAILED => 'AI请求失败',
            self::AI_CONTENT_GENERATED => 'AI内容生成完成',
            self::AI_TOKEN_CONSUMED => 'Token消耗',

            // 支付事件
            self::ORDER_CREATED => '订单创建',
            self::ORDER_PAID => '订单支付成功',
            self::ORDER_CANCELLED => '订单取消',
            self::ORDER_REFUNDED => '订单退款',
            self::RECHARGE_SUCCESS => '充值成功',

            // 会员事件
            self::MEMBERSHIP_PURCHASED => '会员购买',
            self::MEMBERSHIP_RENEWED => '会员续费',
            self::MEMBERSHIP_EXPIRED => '会员过期',

            // 内容事件
            self::CONTENT_PUBLISHED => '内容发布',
            self::CONTENT_APPROVED => '内容审核通过',
            self::CONTENT_REJECTED => '内容审核拒绝',
            self::COMMENT_CREATED => '评论创建',

            // 系统事件
            self::SYSTEM_BOOT => '系统启动',
            self::SYSTEM_MAINTENANCE_START => '系统维护开始',
            self::SYSTEM_MAINTENANCE_END => '系统维护结束',
            self::CACHE_CLEARED => '缓存清除',
            self::CONFIG_UPDATED => '配置更新',

            // 安全事件
            self::SECURITY_LOGIN_FAILED => '登录失败',
            self::SECURITY_SUSPICIOUS_ACTIVITY => '可疑活动检测',
            self::SECURITY_IP_BLOCKED => 'IP封禁',
            self::SECURITY_CSRF_FAILED => 'CSRF验证失败',
            self::SECURITY_XSS_DETECTED => 'XSS检测',
        ];
    }
}
