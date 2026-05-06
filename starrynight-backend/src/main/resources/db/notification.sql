-- ============================================
-- 通知系统 - 数据库表
-- ============================================

-- 通知消息表
CREATE TABLE IF NOT EXISTS notification_message (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '通知ID',
    user_id BIGINT NOT NULL COMMENT '接收用户ID',
    notification_type VARCHAR(30) NOT NULL COMMENT '通知类型: SYSTEM-系统通知, ACCOUNT-账号通知, ORDER-订单通知, ACTIVITY-活动通知, INTERACTION-互动通知, AI创作-AI创作通知',
    title VARCHAR(200) NOT NULL COMMENT '通知标题',
    content TEXT NOT NULL COMMENT '通知内容',
    link_url VARCHAR(500) COMMENT '跳转链接',
    link_params VARCHAR(500) COMMENT '跳转参数JSON',
    is_read TINYINT NOT NULL DEFAULT 0 COMMENT '是否已读: 0-未读, 1-已读',
    read_time DATETIME COMMENT '阅读时间',
    priority VARCHAR(20) NOT NULL DEFAULT 'NORMAL' COMMENT '优先级: LOW-低, NORMAL-普通, HIGH-高, URGENT-紧急',
    expire_time DATETIME COMMENT '过期时间',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_user_id (user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_read (is_read),
    INDEX idx_create_time (create_time),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知消息表';

-- 通知模板表
CREATE TABLE IF NOT EXISTS notification_template (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '模板ID',
    template_code VARCHAR(50) NOT NULL UNIQUE COMMENT '模板编码',
    template_name VARCHAR(100) NOT NULL COMMENT '模板名称',
    notification_type VARCHAR(30) NOT NULL COMMENT '通知类型',
    title_template VARCHAR(200) NOT NULL COMMENT '标题模板',
    content_template TEXT NOT NULL COMMENT '内容模板',
    variables JSON COMMENT '变量定义JSON',
    enabled TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用: 0-禁用, 1-启用',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_template_code (template_code),
    INDEX idx_notification_type (notification_type),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知模板表';

-- 用户通知设置表
CREATE TABLE IF NOT EXISTS notification_setting (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '设置ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    notification_type VARCHAR(30) NOT NULL COMMENT '通知类型',
    push_enabled TINYINT NOT NULL DEFAULT 1 COMMENT '是否推送: 0-关闭, 1-开启',
    email_enabled TINYINT NOT NULL DEFAULT 0 COMMENT '是否邮件: 0-关闭, 1-开启',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    UNIQUE KEY uk_user_type (user_id, notification_type),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户通知设置表';

-- ============================================
-- 初始化通知模板数据
-- ============================================

INSERT IGNORE INTO notification_template (template_code, template_name, notification_type, title_template, content_template, variables) VALUES
('MEMBERSHIP_EXPIRED', '会员到期提醒', 'ACCOUNT', '您的{{memberLevel}}会员即将到期', '亲爱的用户，您的{{memberLevel}}会员将于{{expireTime}}到期，到期后将恢复为普通用户，每日免费额度将调整为10,000创作点。', '{"memberLevel": "string", "expireTime": "datetime"}'),
('MEMBERSHIP_RENEWED', '会员续费成功', 'ACCOUNT', '会员续费成功', '恭喜！您的{{memberLevel}}会员已续费成功，有效期至{{expireTime}}。', '{"memberLevel": "string", "expireTime": "datetime"}'),
('ORDER_COMPLETED', '订单完成', 'ORDER', '订单支付成功', '您的订单已支付成功，订单号：{{orderNo}}，充值{{platformCurrency}}平台币已到账。', '{"orderNo": "string", "platformCurrency": "number"}'),
('RECHARGE_COMPLETED', '充值到账', 'ORDER', '充值已到账', '您已成功充值{{amount}}元，获得{{platformCurrency}}平台币{{bonusCurrency}}赠送，已到账。', '{"amount": "number", "platformCurrency": "number", "bonusCurrency": "number"}'),
('DAILY_CHECKIN', '每日签到', 'ACTIVITY', '签到成功，获得奖励', '恭喜您完成今日签到，获得{{points}}积分奖励！连续签到{{days}}天，再坚持{{remaining}}天可获得额外奖励。', '{"points": "number", "days": "number", "remaining": "number"}'),
('CHECKIN_STREAK', '连续签到奖励', 'ACTIVITY', '连续签到{{days}}天奖励', '您已连续签到{{days}}天，获得额外{{bonusPoints}}积分奖励！', '{"days": "number", "bonusPoints": "number"}'),
('AI_GENERATION_FAILED', 'AI生成失败', 'AI创作', 'AI创作任务失败', '您的AI创作任务因{{reason}}失败，已返还{{points}}创作点到账户。', '{"reason": "string", "points": "number"}'),
('SYSTEM_ANNOUNCEMENT', '系统公告', 'SYSTEM', '{{title}}', '{{content}}', '{"title": "string", "content": "string"}'),
('VIP_UPGRADE', '会员升级', 'ACCOUNT', '恭喜升级为{{memberLevel}}', '您已成功升级为{{memberLevel}}，可享受每日{{quota}}创作点免费额度等特权。', '{"memberLevel": "string", "quota": "number"}');
