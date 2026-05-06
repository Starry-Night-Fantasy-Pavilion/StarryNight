-- 伏笔记录表
CREATE TABLE IF NOT EXISTS foreshadowing_record (
    id VARCHAR(36) PRIMARY KEY COMMENT '伏笔ID',
    novel_id BIGINT NOT NULL COMMENT '所属小说ID',
    chapter_no INT NOT NULL COMMENT '埋设章节',
    setup_content TEXT NOT NULL COMMENT '伏笔内容',
    setup_location FLOAT COMMENT '章节内位置 0-1',
    type VARCHAR(20) COMMENT '伏笔类型：item/identity/relationship/ability/plot/world',
    status VARCHAR(20) DEFAULT 'pending' COMMENT '状态：pending/confirmed/paid_off/expired/cancelled',
    expected_chapter_no INT COMMENT '用户设置的预期回收章节',
    auto_detected_expected INT COMMENT 'AI自动推断的回收章节',
    confidence FLOAT DEFAULT 0.5 COMMENT '检测置信度 0-1',
    detected_at DATETIME NOT NULL COMMENT '检测时间',
    confirmed_at DATETIME COMMENT '用户确认时间',
    user_edited BOOLEAN DEFAULT FALSE COMMENT '是否经过用户编辑',
    paid_off_at DATETIME COMMENT '回收时间',
    paid_off_chapter_no INT COMMENT '回收章节',
    payoff_method VARCHAR(50) COMMENT '回收方式',
    payoff_content TEXT COMMENT '回收内容',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',

    INDEX idx_novel_status (novel_id, status),
    INDEX idx_novel_chapter (novel_id, chapter_no),
    INDEX idx_type (type),
    INDEX idx_expected_chapter (expected_chapter_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='伏笔记录表';