-- ============================================
-- 智能扣费系统 - 数据库表
-- ============================================

-- 用户余额表
CREATE TABLE IF NOT EXISTS user_balance (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    free_quota BIGINT NOT NULL DEFAULT 0 COMMENT '免费创作点余额',
    free_quota_date DATE COMMENT '免费额度日期(每日重置)',
    platform_currency DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '平台币余额',
    enable_mixed_payment TINYINT NOT NULL DEFAULT 1 COMMENT '混合支付开关: 0-关闭, 1-开启',
    total_free_used BIGINT NOT NULL DEFAULT 0 COMMENT '累计免费创作点消耗',
    total_paid_used BIGINT NOT NULL DEFAULT 0 COMMENT '累计付费创作点消耗',
    total_recharged BIGINT NOT NULL DEFAULT 0 COMMENT '累计充值平台币',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    UNIQUE KEY uk_user_id (user_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户余额表';

-- 计费渠道表（与 BillingChannel 实体、MyBatis-Plus 列名一致）
CREATE TABLE IF NOT EXISTS billing_channel (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '渠道ID',
    channel_code VARCHAR(50) NOT NULL UNIQUE COMMENT '渠道编码',
    channel_name VARCHAR(100) NOT NULL COMMENT '渠道名称',
    channel_type VARCHAR(20) NOT NULL DEFAULT 'token' COMMENT '计费类型: token-按Token, per_call-按次, per_second-按秒, hybrid-混合底费',
    api_base_url VARCHAR(500) NULL COMMENT 'API 基地址',
    api_key VARCHAR(500) NULL COMMENT 'API 密钥',
    model_name VARCHAR(200) NULL COMMENT '模型名称',
    cost_per1k_input DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT '每千输入Token成本(元)',
    cost_per1k_output DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT '每千输出Token成本(元)',
    cost_per_call DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT '单次调用成本(元)',
    cost_per_second DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT '每秒成本(元)',
    base_cost DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT '混合底费(元)',
    is_free TINYINT NOT NULL DEFAULT 0 COMMENT '是否免费渠道: 0-付费, 1-免费',
    status VARCHAR(20) NOT NULL DEFAULT 'NORMAL' COMMENT '状态: NORMAL-正常, WARNING-警告, CIRCUIT_BROKEN-熔断',
    failure_count INT NOT NULL DEFAULT 0 COMMENT '连续失败次数',
    last_failure_time DATETIME COMMENT '最后失败时间',
    circuit_open_time DATETIME COMMENT '熔断打开时间',
    enabled TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用: 0-禁用, 1-启用',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '排序',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_channel_type (channel_type),
    INDEX idx_is_free (is_free),
    INDEX idx_status (status),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='计费渠道表';

-- 计费记录表
CREATE TABLE IF NOT EXISTS billing_record (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '记录ID',
    record_no VARCHAR(64) NOT NULL UNIQUE COMMENT '记录编号',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    channel_id BIGINT COMMENT '渠道ID',
    content_type VARCHAR(30) NOT NULL COMMENT '内容类型: outline-大纲, volume_outline-卷纲, chapter_outline-细纲, content-正文, image-图片, chat-对话',
    content_id BIGINT COMMENT '关联内容ID',
    input_tokens INT DEFAULT 0 COMMENT '输入Token数',
    output_tokens INT DEFAULT 0 COMMENT '输出Token数',
    total_tokens INT DEFAULT 0 COMMENT '总Token数',
    channel_cost DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT '渠道成本(元)',
    profit_margin DECIMAL(5,4) NOT NULL DEFAULT 0.3000 COMMENT '利润率',
    user_price DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT '用户价格(元)',
    creation_points INT NOT NULL DEFAULT 0 COMMENT '消耗创作点数',
    free_points_used INT NOT NULL DEFAULT 0 COMMENT '使用免费点数',
    paid_points_used INT NOT NULL DEFAULT 0 COMMENT '使用付费点数',
    platform_currency_used DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '使用平台币',
    generation_success TINYINT NOT NULL DEFAULT 1 COMMENT '生成是否成功: 0-失败, 1-成功',
    error_message TEXT COMMENT '错误信息',
    rollback_record_no VARCHAR(64) COMMENT '回退记录编号',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_user_id (user_id),
    INDEX idx_channel_id (channel_id),
    INDEX idx_content_type (content_type),
    INDEX idx_record_no (record_no),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='计费记录表';

-- 每日免费额度发放记录表
CREATE TABLE IF NOT EXISTS daily_free_quota_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '记录ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    quota_date DATE NOT NULL COMMENT '额度日期',
    granted_quota BIGINT NOT NULL DEFAULT 0 COMMENT '发放额度',
    user_group VARCHAR(20) NOT NULL DEFAULT 'NORMAL' COMMENT '用户组: NORMAL-普通, VIP-VIP, SVIP-SVIP, TEST-测试',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    UNIQUE KEY uk_user_date (user_id, quota_date),
    INDEX idx_user_id (user_id),
    INDEX idx_quota_date (quota_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日免费额度发放记录表';

-- 计费配置表
CREATE TABLE IF NOT EXISTS billing_config (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '配置ID',
    config_key VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
    config_value TEXT COMMENT '配置值',
    config_type VARCHAR(20) NOT NULL DEFAULT 'string' COMMENT '配置类型: string, number, boolean, json',
    config_name VARCHAR(100) COMMENT '配置名称',
    description VARCHAR(500) COMMENT '配置描述',
    editable TINYINT NOT NULL DEFAULT 1 COMMENT '是否可编辑: 0-否, 1-是',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='计费配置表';

-- 差异化利润率配置表
CREATE TABLE IF NOT EXISTS billing_margin_config (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '配置ID',
    config_type VARCHAR(30) NOT NULL COMMENT '配置类型: content_type-按内容类型, user_group-按用户组, activity-按活动',
    config_key VARCHAR(50) NOT NULL COMMENT '配置键: outline/vip/svip/activity_xxx等',
    content_type VARCHAR(30) COMMENT '内容类型(当config_type=content_type时)',
    user_group VARCHAR(20) COMMENT '用户组(当config_type=user_group时)',
    profit_margin DECIMAL(5,4) NOT NULL DEFAULT 0.3000 COMMENT '利润率',
    enabled TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用: 0-禁用, 1-启用',
    priority INT NOT NULL DEFAULT 0 COMMENT '优先级(数字越大优先级越高)',
    start_time DATETIME COMMENT '生效开始时间',
    end_time DATETIME COMMENT '生效结束时间',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_config_type (config_type),
    INDEX idx_enabled (enabled),
    INDEX idx_priority (priority),
    UNIQUE KEY uk_margin_type_key (config_type, config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='差异化利润率配置表';

-- 用户充值记录表
CREATE TABLE IF NOT EXISTS recharge_record (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '记录ID',
    record_no VARCHAR(64) NOT NULL UNIQUE COMMENT '充值记录编号',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    amount DECIMAL(10,2) NOT NULL COMMENT '充值金额(元)',
    platform_currency DECIMAL(12,2) NOT NULL COMMENT '获得平台币',
    bonus_currency DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '赠送平台币',
    pay_method VARCHAR(20) COMMENT '支付方式: alipay-支付宝, wechat-微信, stripe-Stripe',
    pay_status VARCHAR(20) NOT NULL DEFAULT 'PENDING' COMMENT '支付状态: PENDING-待支付, SUCCESS-成功, FAILED-失败, REFUNDED-已退款',
    pay_time DATETIME COMMENT '支付时间',
    transaction_id VARCHAR(100) COMMENT '第三方交易号',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_user_id (user_id),
    INDEX idx_pay_status (pay_status),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户充值记录表';

-- ============================================
-- 初始化计费配置数据
-- ============================================

INSERT IGNORE INTO billing_config (config_key, config_value, config_type, config_name, description) VALUES
('daily_free_quota', '10000', 'number', '每日免费额度', '每日免费发放的创作点数'),
('default_profit_margin', '0.30', 'number', '默认利润率', '默认利润率30%'),
('mixed_payment_default', 'true', 'boolean', '混合支付默认', '默认开启混合支付'),
('free_quota_reset_hour', '0', 'number', '免费额度重置小时', '每日免费额度重置小时(0-23)'),
('platform_currency_rate', '10', 'number', '平台币汇率', '1元兑换平台币数量'),
('creation_point_rate', '1000', 'number', '创作点汇率', '1平台币兑换创作点数量'),
('recharge_bonus_rule', '{"tiers":[{"min_amount":30,"bonus":15},{"min_amount":68,"bonus":68},{"min_amount":128,"bonus":128}]}', 'json', '充值赠送规则', '充值赠送规则JSON');

-- 与 BillingMarginConfigMapper 一致：按 content_type / user_group 查询，无 description 列
INSERT IGNORE INTO billing_margin_config (config_type, config_key, content_type, user_group, profit_margin) VALUES
('content_type', 'outline', 'outline', NULL, 0.30),
('content_type', 'volume_outline', 'volume_outline', NULL, 0.30),
('content_type', 'chapter_outline', 'chapter_outline', NULL, 0.30),
('content_type', 'content', 'content', NULL, 0.30),
('user_group', 'NORMAL', NULL, 'NORMAL', 0.30),
('user_group', 'VIP', NULL, 'VIP', 0.20),
('user_group', 'SVIP', NULL, 'SVIP', 0.15),
('user_group', 'TEST', NULL, 'TEST', 0.30);

-- 初始化示例渠道数据
INSERT IGNORE INTO billing_channel (channel_code, channel_name, channel_type, cost_per1k_input, cost_per1k_output, is_free, status, enabled, sort_order) VALUES
('FREE_DEFAULT', '免费默认渠道', 'token', 0.0000, 0.0000, 1, 'NORMAL', 1, 1),
('GPT4', 'GPT-4', 'token', 0.0300, 0.0900, 0, 'NORMAL', 1, 10),
('GPT35', 'GPT-3.5-Turbo', 'token', 0.0010, 0.0020, 0, 'NORMAL', 1, 20),
('CLAUDE', 'Claude', 'token', 0.0250, 0.0250, 0, 'NORMAL', 1, 15);

-- 若库中 billing_channel 为旧版 DDL（缺 api_base_url 等），CREATE IF NOT EXISTS 不会改表结构，请在库中手工执行：
-- ALTER TABLE billing_channel ADD COLUMN api_base_url VARCHAR(500) NULL COMMENT 'API 基地址';
-- ALTER TABLE billing_channel ADD COLUMN api_key VARCHAR(500) NULL COMMENT 'API 密钥';
-- ALTER TABLE billing_channel ADD COLUMN model_name VARCHAR(200) NULL COMMENT '模型名称';
