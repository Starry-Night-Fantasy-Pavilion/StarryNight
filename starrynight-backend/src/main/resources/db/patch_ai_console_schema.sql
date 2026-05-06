-- 运营端 AI 配置：补齐 ai_model.billing_channel_id、创建 ai_template（billing_channel_id 行需 MySQL 8.0.12+）
-- 若尚无 idx_ai_model_billing_channel_id，可另执行 db/patch_ai_model_billing_channel.sql 中的 CREATE INDEX 语句
ALTER TABLE ai_model
    ADD COLUMN IF NOT EXISTS billing_channel_id BIGINT NULL COMMENT '计费渠道 billing_channel.id' AFTER provider;

UPDATE ai_model SET model_type = 'default'
WHERE model_type IN ('outline', 'content', 'chat') OR model_type IS NULL OR model_type = '';

CREATE TABLE IF NOT EXISTS ai_template (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '模板ID',
    name VARCHAR(200) NOT NULL COMMENT '模板名称',
    type VARCHAR(50) NOT NULL COMMENT '类型',
    description VARCHAR(500) COMMENT '描述',
    content MEDIUMTEXT NOT NULL COMMENT '模板正文',
    enabled TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
    usage_count INT NOT NULL DEFAULT 0 COMMENT '使用次数',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_ai_template_type (type),
    INDEX idx_ai_template_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI提示模板表';
