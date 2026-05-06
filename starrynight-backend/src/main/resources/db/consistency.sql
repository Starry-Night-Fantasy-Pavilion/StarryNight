-- ============================================
-- AI创作增强 - 一致性检查、伏笔追踪、节奏分析
-- ============================================

-- 伏笔记录表
CREATE TABLE IF NOT EXISTS foreshadowing_record (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '记录ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    novel_id BIGINT NOT NULL COMMENT '作品ID',
    chapter_id BIGINT COMMENT '章节ID',
    foreshadowing_type VARCHAR(30) NOT NULL COMMENT '伏笔类型: dialogue-对话伏笔, action-动作伏笔, description-描写伏笔, event-事件伏笔, object-物品伏笔',
    foreshadowing_content TEXT NOT NULL COMMENT '伏笔内容',
    hint_level TINYINT NOT NULL DEFAULT 1 COMMENT '暗示程度: 1-微弱, 2-中等, 3-明显',
    setup_position INT COMMENT '伏笔设置位置(字数)',
    expected_resolution TEXT COMMENT '预期回收方式',
    resolution_chapter_id BIGINT COMMENT '实际回收章节ID',
    resolution_status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending-待回收, resolved-已回收, abandoned-已废弃',
    resolution_quality TINYINT COMMENT '回收质量: 1-差, 2-中, 3-好',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_user_id (user_id),
    INDEX idx_novel_id (novel_id),
    INDEX idx_chapter_id (chapter_id),
    INDEX idx_status (resolution_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='伏笔记录表';

-- 伏笔分析配置表
CREATE TABLE IF NOT EXISTS foreshadowing_config (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '配置ID',
    user_id BIGINT COMMENT '用户ID(NULL表示全局配置)',
    config_key VARCHAR(100) NOT NULL COMMENT '配置键',
    config_value TEXT COMMENT '配置值',
    config_type VARCHAR(20) NOT NULL DEFAULT 'string' COMMENT '配置类型',
    description VARCHAR(500) COMMENT '配置描述',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    UNIQUE KEY uk_user_key (user_id, config_key),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='伏笔分析配置表';

-- 节奏分析记录表
CREATE TABLE IF NOT EXISTS rhythm_analysis (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '分析ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    novel_id BIGINT NOT NULL COMMENT '作品ID',
    chapter_id BIGINT COMMENT '章节ID',
    chapter_no INT COMMENT '章节号',
    analysis_type VARCHAR(20) NOT NULL COMMENT '分析类型: emotion-情绪分析, conflict-冲突分析, rhythm-节奏分析, retention-追读预测',
    anticipation_score DECIMAL(5,4) COMMENT '期待值 0-1',
    tension_score DECIMAL(5,4) COMMENT '紧张感 0-1',
    warmth_score DECIMAL(5,4) COMMENT '温馨度 0-1',
    sadness_score DECIMAL(5,4) COMMENT '悲伤度 0-1',
    conflict_count INT COMMENT '冲突事件数',
    conflict_density DECIMAL(5,4) COMMENT '冲突密度(每千字)',
    retention_score DECIMAL(5,4) COMMENT '追读预测分数 0-1',
    emotion_curve JSON COMMENT '情绪曲线JSON',
    conflict_details JSON COMMENT '冲突详情JSON',
    suggestions JSON COMMENT '建议JSON',
    word_count INT COMMENT '字数',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_user_id (user_id),
    INDEX idx_novel_id (novel_id),
    INDEX idx_chapter_id (chapter_id),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='节奏分析记录表';

-- 角色关系一致性记录表
CREATE TABLE IF NOT EXISTS character_consistency (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '记录ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    novel_id BIGINT NOT NULL COMMENT '作品ID',
    character_id BIGINT NOT NULL COMMENT '角色ID',
    consistency_type VARCHAR(30) NOT NULL COMMENT '一致性类型: personality-性格, ability-能力, relationship-关系, appearance-外貌',
    check_chapter_id BIGINT COMMENT '检查的章节ID',
    check_result VARCHAR(20) NOT NULL COMMENT '检查结果: pass-通过, warning-警告, conflict-冲突',
    issue_description TEXT COMMENT '问题描述',
    suggestion TEXT COMMENT '建议',
    severity VARCHAR(10) NOT NULL DEFAULT 'medium' COMMENT '严重程度: low, medium, high',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_user_id (user_id),
    INDEX idx_novel_id (novel_id),
    INDEX idx_character_id (character_id),
    INDEX idx_check_result (check_result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色关系一致性记录表';

-- AI一致性检查记录表
CREATE TABLE IF NOT EXISTS ai_consistency_check (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '检查ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    novel_id BIGINT NOT NULL COMMENT '作品ID',
    chapter_id BIGINT COMMENT '章节ID',
    content_type VARCHAR(30) NOT NULL COMMENT '内容类型: outline-大纲, volume_outline-卷纲, chapter_outline-细纲, content-正文',
    check_type VARCHAR(30) NOT NULL COMMENT '检查类型: rule-规则冲突, timeline-时间线, personality-性格, foreshadowing-伏笔, rhythm-节奏',
    check_result VARCHAR(20) NOT NULL COMMENT '检查结果: pass-通过, warning-警告, conflict-冲突',
    issue_count INT NOT NULL DEFAULT 0 COMMENT '问题数量',
    issues_detail JSON COMMENT '问题详情JSON',
    ai_model VARCHAR(50) COMMENT '使用的AI模型',
    processing_time_ms INT COMMENT '处理时间(毫秒)',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_user_id (user_id),
    INDEX idx_novel_id (novel_id),
    INDEX idx_check_type (check_type),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI一致性检查记录表';

-- ============================================
-- 初始化伏笔配置
-- ============================================

INSERT IGNORE INTO foreshadowing_config (user_id, config_key, config_value, config_type, description) VALUES
(NULL, 'auto_detect_enabled', 'true', 'boolean', '是否自动检测伏笔'),
(NULL, 'hint_keywords', '["注意","发现","奇怪","异常","预示","征兆","暗示"]', 'json', '伏笔检测关键词'),
(NULL, 'min_foreshadowing_length', '50', 'number', '最小伏笔长度'),
(NULL, 'resolution_window', '10', 'number', '回收窗口(章节数)'),
(NULL, 'alert_before_expiry', '3', 'number', '到期前多少章提醒');

-- 初始化默认节奏分析配置
INSERT IGNORE INTO foreshadowing_config (user_id, config_key, config_value, config_type, description) VALUES
(NULL, 'emotion_classifier_enabled', 'true', 'boolean', '是否启用情绪分类'),
(NULL, 'conflict_detector_enabled', 'true', 'boolean', '是否启用冲突检测'),
(NULL, 'retention_predict_enabled', 'true', 'boolean', '是否启用追读预测'),
(NULL, 'target_conflict_density', '3.0', 'number', '目标冲突密度(每千字)'),
(NULL, 'emotion_threshold_high', '0.8', 'number', '高情绪阈值'),
(NULL, 'emotion_threshold_low', '0.2', 'number', '低情绪阈值');
