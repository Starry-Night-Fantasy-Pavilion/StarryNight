-- AI 模型与计费渠道联动：每条模型记录关联 billing_channel.id；model_type 固定为 default（兼容旧列 NOT NULL）
ALTER TABLE ai_model
    ADD COLUMN billing_channel_id BIGINT NULL COMMENT '计费渠道 billing_channel.id' AFTER provider;

CREATE INDEX idx_ai_model_billing_channel_id ON ai_model (billing_channel_id);

UPDATE ai_model SET model_type = 'default' WHERE model_type IN ('outline', 'content', 'chat') OR model_type IS NULL OR model_type = '';
