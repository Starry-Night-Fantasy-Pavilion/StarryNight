-- 用户客服工单主表
CREATE TABLE IF NOT EXISTS support_ticket (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '工单ID',
    ticket_no VARCHAR(32) NOT NULL UNIQUE COMMENT '工单编号（如 TK20260506001）',
    user_id BIGINT NOT NULL COMMENT '提交用户 auth_user.id',
    category VARCHAR(32) NOT NULL DEFAULT 'OTHER' COMMENT '工单分类：BUG/ACCOUNT/BILLING/CONTENT/FEATURE/OTHER',
    title VARCHAR(200) NOT NULL COMMENT '工单标题',
    content TEXT NOT NULL COMMENT '工单详细描述',
    status VARCHAR(20) NOT NULL DEFAULT 'OPEN' COMMENT '状态：OPEN/IN_PROGRESS/RESOLVED/CLOSED',
    priority VARCHAR(10) NOT NULL DEFAULT 'NORMAL' COMMENT '优先级：LOW/NORMAL/HIGH/URGENT',
    assigned_to BIGINT NULL COMMENT '指派给哪个运营账号（ops_account.id）',
    close_reason VARCHAR(500) NULL COMMENT '关闭原因',
    resolved_at DATETIME NULL COMMENT '解决时间',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记: 0-未删除, 1-已删除',
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_create_time (create_time),
    INDEX idx_assigned_to (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户客服工单';

-- 工单回复表（用户和运营均可回复）
CREATE TABLE IF NOT EXISTS support_ticket_reply (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '回复ID',
    ticket_id BIGINT NOT NULL COMMENT '工单ID support_ticket.id',
    author_type VARCHAR(10) NOT NULL COMMENT '回复者类型：USER/OPS',
    author_id BIGINT NOT NULL COMMENT '回复者ID（USER=auth_user.id，OPS=ops_account.id）',
    author_name VARCHAR(100) NULL COMMENT '回复者名称（冗余，便于展示）',
    content TEXT NOT NULL COMMENT '回复内容',
    is_internal TINYINT NOT NULL DEFAULT 0 COMMENT '是否内部备注（0-用户可见，1-仅运营可见）',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_author (author_type, author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单回复';
