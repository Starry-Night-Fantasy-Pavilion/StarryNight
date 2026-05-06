-- ============================================
-- 运营活动与兑换码
-- ============================================

CREATE TABLE IF NOT EXISTS ops_campaign (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '活动ID',
    title VARCHAR(200) NOT NULL COMMENT '标题',
    summary VARCHAR(1000) COMMENT '摘要',
    link_url VARCHAR(500) COMMENT '跳转链接',
    cover_url VARCHAR(500) COMMENT '封面图URL',
    status TINYINT NOT NULL DEFAULT 0 COMMENT '0草稿 1已发布 2已结束',
    start_time DATETIME COMMENT '开始时间',
    end_time DATETIME COMMENT '结束时间',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '排序',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '逻辑删除',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_status_time (status, deleted, start_time, end_time),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='运营活动';

CREATE TABLE IF NOT EXISTS redeem_code (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '兑换码ID',
    code VARCHAR(64) NOT NULL COMMENT '兑换码',
    batch_label VARCHAR(100) COMMENT '批次/备注',
    reward_type VARCHAR(32) NOT NULL DEFAULT 'free_quota' COMMENT 'free_quota创作点 platform_currency平台币',
    reward_points BIGINT NOT NULL DEFAULT 0 COMMENT '创作点奖励(reward_type=free_quota)',
    reward_currency DECIMAL(12, 2) NOT NULL DEFAULT 0 COMMENT '平台币奖励(reward_type=platform_currency)',
    max_total_redemptions INT COMMENT '全站总兑换上限 NULL不限',
    redemption_count INT NOT NULL DEFAULT 0 COMMENT '已兑换次数',
    max_per_user INT NOT NULL DEFAULT 1 COMMENT '单用户上限',
    valid_start DATETIME COMMENT '生效时间',
    valid_end DATETIME COMMENT '失效时间',
    enabled TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    campaign_id BIGINT COMMENT '关联活动(可选)',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    UNIQUE KEY uk_code (code),
    INDEX idx_enabled (enabled),
    INDEX idx_campaign (campaign_id),
    INDEX idx_batch (batch_label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='兑换码';

CREATE TABLE IF NOT EXISTS redeem_redemption (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '记录ID',
    redeem_code_id BIGINT NOT NULL COMMENT '兑换码ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    reward_type VARCHAR(32) NOT NULL COMMENT '实际发放类型',
    points_granted BIGINT COMMENT '发放创作点',
    currency_granted DECIMAL(12, 2) COMMENT '发放平台币',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '兑换时间',
    INDEX idx_code (redeem_code_id),
    INDEX idx_user (user_id),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='兑换记录';
