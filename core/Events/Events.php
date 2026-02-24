<?php
/**
 * 预定义事件类型
 *
 * @package Core\Events
 * @version 1.0.0
 */

namespace Core\Events;

/**
 * 预定义事件常量
 */
class Events
{
    // 用户事件
    const USER_REGISTERED = 'user.registered';
    const USER_LOGIN = 'user.login';
    const USER_LOGOUT = 'user.logout';
    const USER_UPDATED = 'user.updated';
    const USER_DELETED = 'user.deleted';

    // 小说事件
    const NOVEL_CREATED = 'novel.created';
    const NOVEL_UPDATED = 'novel.updated';
    const NOVEL_PUBLISHED = 'novel.published';
    const NOVEL_DELETED = 'novel.deleted';
    const CHAPTER_CREATED = 'novel.chapter.created';
    const CHAPTER_UPDATED = 'novel.chapter.updated';

    // AI事件
    const AI_GENERATION_STARTED = 'ai.generation.started';
    const AI_GENERATION_COMPLETED = 'ai.generation.completed';
    const AI_GENERATION_FAILED = 'ai.generation.failed';
    const AI_TOKEN_CONSUMED = 'ai.token.consumed';

    // 支付事件
    const PAYMENT_CREATED = 'payment.created';
    const PAYMENT_SUCCESS = 'payment.success';
    const PAYMENT_FAILED = 'payment.failed';
    const PAYMENT_REFUNDED = 'payment.refunded';

    // 会员事件
    const VIP_PURCHASED = 'vip.purchased';
    const VIP_RENEWED = 'vip.renewed';
    const VIP_EXPIRED = 'vip.expired';
    const VIP_CANCELLED = 'vip.cancelled';

    // 系统事件
    const SYSTEM_STARTUP = 'system.startup';
    const SYSTEM_SHUTDOWN = 'system.shutdown';
    const SYSTEM_ERROR = 'system.error';
    const SYSTEM_MAINTENANCE = 'system.maintenance';

    // 文件事件
    const FILE_UPLOADED = 'file.uploaded';
    const FILE_DELETED = 'file.deleted';

    // 通知事件
    const NOTIFICATION_SENT = 'notification.sent';
    const EMAIL_SENT = 'email.sent';
}
