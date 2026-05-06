-- 社区帖子表：已有库执行一次；全新安装请同步 schema.sql / table.sql
CREATE TABLE IF NOT EXISTS community_post (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '帖子ID',
    user_id BIGINT NOT NULL COMMENT '发布者 auth_user.id',
    title VARCHAR(200) NULL COMMENT '标题（可选）',
    content TEXT NOT NULL COMMENT '正文',
    content_type VARCHAR(20) NOT NULL DEFAULT 'text' COMMENT '类型: text 等',
    topic_id BIGINT NULL COMMENT '话题ID（预留）',
    audit_status TINYINT NOT NULL DEFAULT 0 COMMENT '审核: 0待审 1通过 2驳回',
    reject_reason VARCHAR(500) NULL COMMENT '驳回原因',
    like_count INT NOT NULL DEFAULT 0 COMMENT '点赞数',
    comment_count INT NOT NULL DEFAULT 0 COMMENT '评论数',
    view_count INT NOT NULL DEFAULT 0 COMMENT '浏览数',
    online_status TINYINT NOT NULL DEFAULT 1 COMMENT '上架: 1展示 0运营下架',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_user_id (user_id),
    INDEX idx_audit_status (audit_status),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区帖子表';
