-- ============================================
-- VIP会员系统 - 数据库表
-- ============================================

-- VIP套餐表
CREATE TABLE IF NOT EXISTS vip_package (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '套餐ID',
    package_code VARCHAR(50) NOT NULL UNIQUE COMMENT '套餐编码',
    package_name VARCHAR(100) NOT NULL COMMENT '套餐名称',
    description VARCHAR(500) COMMENT '套餐描述',
    member_level TINYINT NOT NULL COMMENT '会员等级: 1-普通, 2-VIP, 3-SVIP',
    duration_days INT NOT NULL COMMENT '时长(天)',
    price DECIMAL(10,2) NOT NULL COMMENT '价格(元)',
    original_price DECIMAL(10,2) COMMENT '原价',
    daily_free_quota BIGINT NOT NULL DEFAULT 10000 COMMENT '每日免费额度',
    features JSON COMMENT '功能权益JSON',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '排序',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '状态: 0-下架, 1-上架',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_member_level (member_level),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VIP套餐表';

-- 会员订阅记录表
CREATE TABLE IF NOT EXISTS member_subscription (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '订阅ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    package_id BIGINT NOT NULL COMMENT '套餐ID',
    member_level TINYINT NOT NULL COMMENT '会员等级',
    start_time DATETIME NOT NULL COMMENT '开始时间',
    expire_time DATETIME NOT NULL COMMENT '到期时间',
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' COMMENT '状态: ACTIVE-生效中, EXPIRED-已过期, CANCELLED-已取消',
    auto_renew TINYINT NOT NULL DEFAULT 0 COMMENT '是否自动续费: 0-否, 1-是',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_expire_time (expire_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员订阅记录表';

-- 会员权益配置表
CREATE TABLE IF NOT EXISTS member_benefit_config (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '配置ID',
    member_level TINYINT NOT NULL COMMENT '会员等级: 1-普通, 2-VIP, 3-SVIP',
    benefit_key VARCHAR(100) NOT NULL COMMENT '权益键',
    benefit_name VARCHAR(100) NOT NULL COMMENT '权益名称',
    benefit_value JSON COMMENT '权益值JSON',
    description VARCHAR(500) COMMENT '权益描述',
    enabled TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用: 0-禁用, 1-启用',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    UNIQUE KEY uk_level_key (member_level, benefit_key),
    INDEX idx_member_level (member_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员权益配置表';

-- ============================================
-- 初始化VIP套餐数据
-- ============================================

INSERT IGNORE INTO vip_package (package_code, package_name, description, member_level, duration_days, price, original_price, daily_free_quota, features, sort_order) VALUES
('VIP_MONTHLY', 'VIP月卡', 'VIP会员一个月', 2, 30, 30.00, 50.00, 50000, '{"outline_limit": 100, "content_limit": 500, "knowledge_limit": 5, "priority_support": true}', 1),
('VIP_YEARLY', 'VIP年卡', 'VIP会员一年', 2, 365, 298.00, 596.00, 50000, '{"outline_limit": 100, "content_limit": 500, "knowledge_limit": 5, "priority_support": true}', 2),
('SVIP_MONTHLY', 'SVIP月卡', 'SVIP高级会员一个月', 3, 30, 68.00, 100.00, 100000, '{"outline_limit": 500, "content_limit": 2000, "knowledge_limit": 20, "priority_support": true, "exclusive_channel": true}', 3),
('SVIP_YEARLY', 'SVIP年卡', 'SVIP高级会员一年', 3, 365, 598.00, 1196.00, 100000, '{"outline_limit": 500, "content_limit": 2000, "knowledge_limit": 20, "priority_support": true, "exclusive_channel": true}', 4);

INSERT IGNORE INTO member_benefit_config (member_level, benefit_key, benefit_name, benefit_value, description) VALUES
(1, 'daily_free_quota', '每日免费额度', '{"value": 10000}', '普通用户每日免费创作点'),
(1, 'outline_per_day', '大纲生成次数', '{"value": 10}', '每日大纲生成限制'),
(1, 'content_per_day', '正文生成次数', '{"value": 50}', '每日正文生成限制'),
(1, 'knowledge_library_limit', '知识库数量', '{"value": 1}', '可创建知识库数量'),
(2, 'daily_free_quota', '每日免费额度', '{"value": 50000}', 'VIP用户每日免费创作点'),
(2, 'outline_per_day', '大纲生成次数', '{"value": 100}', '每日大纲生成限制'),
(2, 'content_per_day', '正文生成次数', '{"value": 500}', '每日正文生成限制'),
(2, 'knowledge_library_limit', '知识库数量', '{"value": 5}', '可创建知识库数量'),
(2, 'priority_support', '优先客服支持', '{"value": true}', '享有优先客服支持'),
(3, 'daily_free_quota', '每日免费额度', '{"value": 100000}', 'SVIP用户每日免费创作点'),
(3, 'outline_per_day', '大纲生成次数', '{"value": 500}', '每日大纲生成限制'),
(3, 'content_per_day', '正文生成次数', '{"value": 2000}', '每日正文生成限制'),
(3, 'knowledge_library_limit', '知识库数量', '{"value": 20}', '可创建知识库数量'),
(3, 'priority_support', '优先客服支持', '{"value": true}', '享有优先客服支持'),
(3, 'exclusive_channel', '专属渠道', '{"value": true}', '使用专属AI渠道');
