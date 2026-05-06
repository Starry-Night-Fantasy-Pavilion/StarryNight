-- ============================================
-- 用户成长体系 - 数据库表
-- ============================================

-- 签到记录表
CREATE TABLE IF NOT EXISTS checkin_record (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '记录ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    checkin_date DATE NOT NULL COMMENT '签到日期',
    checkin_time DATETIME NOT NULL COMMENT '签到时间',
    reward_type VARCHAR(20) NOT NULL COMMENT '奖励类型: free_quota-免费额度, platform_currency-平台币',
    reward_amount BIGINT NOT NULL COMMENT '奖励数量(创作点数)',
    continuous_days INT NOT NULL DEFAULT 1 COMMENT '连续签到天数',
    is_first_checkin TINYINT NOT NULL DEFAULT 0 COMMENT '是否首次签到: 0-否, 1-是',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    UNIQUE KEY uk_user_date (user_id, checkin_date),
    INDEX idx_user_id (user_id),
    INDEX idx_checkin_date (checkin_date),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到记录表';

-- 签到配置表
CREATE TABLE IF NOT EXISTS checkin_config (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '配置ID',
    config_key VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
    config_value TEXT COMMENT '配置值',
    config_type VARCHAR(20) NOT NULL DEFAULT 'string' COMMENT '配置类型',
    description VARCHAR(500) COMMENT '配置描述',
    editable TINYINT NOT NULL DEFAULT 1 COMMENT '是否可编辑: 0-否, 1-是',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='签到配置表';

-- 创作点变动记录表
CREATE TABLE IF NOT EXISTS points_transaction (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '记录ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    transaction_type VARCHAR(30) NOT NULL COMMENT '变动类型: checkin-签到, reward-奖励, consume-消费, expire-过期, adjust-调整',
    points_change INT NOT NULL COMMENT '变动点数(正数为获得,负数为消耗)',
    balance_before BIGINT NOT NULL COMMENT '变动前余额',
    balance_after BIGINT NOT NULL COMMENT '变动后余额',
    source_id BIGINT COMMENT '关联来源ID(如签到记录ID)',
    description VARCHAR(500) COMMENT '变动描述',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_user_id (user_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='创作点变动记录表';

-- 任务配置表
CREATE TABLE IF NOT EXISTS task_config (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '任务ID',
    task_code VARCHAR(50) NOT NULL UNIQUE COMMENT '任务编码',
    task_name VARCHAR(100) NOT NULL COMMENT '任务名称',
    task_type VARCHAR(20) NOT NULL COMMENT '任务类型: daily-每日任务, achievement-成就任务',
    description VARCHAR(500) COMMENT '任务描述',
    trigger_action VARCHAR(50) COMMENT '触发动作',
    reward_type VARCHAR(20) NOT NULL DEFAULT 'free_quota' COMMENT '奖励类型',
    reward_amount BIGINT NOT NULL COMMENT '奖励数量',
    condition_value INT COMMENT '完成条件值',
    condition_operator VARCHAR(10) DEFAULT 'eq' COMMENT '条件操作符: eq-等于, gte-大于等于',
    max_daily_times INT COMMENT '每日最多完成次数(NULL表示不限)',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '排序',
    enabled TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用: 0-禁用, 1-启用',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_task_code (task_code),
    INDEX idx_task_type (task_type),
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='任务配置表';

-- 用户任务完成记录表
CREATE TABLE IF NOT EXISTS task_completion (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '记录ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    task_id BIGINT NOT NULL COMMENT '任务ID',
    task_code VARCHAR(50) NOT NULL COMMENT '任务编码',
    completion_date DATE NOT NULL COMMENT '完成日期',
    completion_count INT NOT NULL DEFAULT 1 COMMENT '完成次数',
    reward_claimed TINYINT NOT NULL DEFAULT 0 COMMENT '奖励是否已领取: 0-未领取, 1-已领取',
    reward_claim_time DATETIME COMMENT '奖励领取时间',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    UNIQUE KEY uk_user_task_date (user_id, task_id, completion_date),
    INDEX idx_user_id (user_id),
    INDEX idx_task_id (task_id),
    INDEX idx_completion_date (completion_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户任务完成记录表';

-- ============================================
-- 初始化签到配置数据
-- ============================================

INSERT IGNORE INTO checkin_config (config_key, config_value, config_type, description) VALUES
('daily_checkin_reward', '50', 'number', '每日签到基础奖励(创作点数)'),
('checkin_streak_7_bonus', '100', 'number', '连续签到7天额外奖励'),
('checkin_streak_15_bonus', '300', 'number', '连续签到15天额外奖励'),
('checkin_streak_30_bonus', '500', 'number', '连续签到30天额外奖励'),
('checkin_streak_bonus_type', 'free_quota', 'string', '连续签到奖励类型'),
('first_checkin_reward', '200', 'number', '首次签到奖励(创作点数)'),
('max_continuous_days', '365', 'number', '最大连续签到天数(超过后重新计算)');

-- 初始化任务配置数据
INSERT IGNORE INTO task_config (task_code, task_name, task_type, description, trigger_action, reward_type, reward_amount, condition_value, max_daily_times, sort_order) VALUES
('DAILY_CHECKIN', '每日签到', 'daily', '每天签到一次', 'user_checkin', 'free_quota', 10, 1, 1, 1),
('FIRST_CHECKIN', '首次签到', 'achievement', '完成首次签到', 'user_checkin', 'free_quota', 50, 1, 1, 2),
('CREATE_OUTLINE', '生成大纲', 'daily', '每天生成大纲', 'create_outline', 'free_quota', 20, 1, 5, 10),
('CREATE_CHAPTER', '生成章节', 'daily', '每天生成章节', 'create_chapter', 'free_quota', 30, 1, 10, 11),
('EDIT_CONTENT', '编辑内容', 'daily', '每天编辑内容', 'edit_content', 'free_quota', 10, 1, 20, 12),
('CREATE_NOVEL', '创建作品', 'achievement', '创建新作品', 'create_novel', 'free_quota', 100, 1, NULL, 20),
('COMPLETE_OUTLINE', '完成大纲', 'achievement', '完整大纲', 'complete_outline', 'free_quota', 200, 1, NULL, 21);
