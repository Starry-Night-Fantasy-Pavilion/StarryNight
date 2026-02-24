-- AI 渠道与调用日志表

CREATE TABLE IF NOT EXISTS `__PREFIX__ai_channels` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '渠道名称',
  `type` varchar(50) NOT NULL DEFAULT 'custom' COMMENT '渠道类型，例如 openai, azure, custom',
  `model_group` varchar(100) DEFAULT NULL COMMENT '模型分组，例如 text, image, audio',
  `status` varchar(20) NOT NULL DEFAULT 'enabled' COMMENT 'enabled / disabled',
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT '优先级，越大越优先',
  `weight` int(11) NOT NULL DEFAULT 100 COMMENT '负载权重',
  `base_url` varchar(255) DEFAULT NULL COMMENT 'API Base URL',
  `api_key` text DEFAULT NULL COMMENT 'API Key',
  `models_text` text DEFAULT NULL COMMENT '可用模型列表（多行文本）',
  `config_json` text DEFAULT NULL COMMENT '其他配置 JSON',
  `concurrency_limit` int(11) NOT NULL DEFAULT 0 COMMENT '并发限制，为0表示不限',
  `is_free` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否免费渠道',
  `is_user_custom` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否允许用户自定义',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 渠道配置表';

CREATE TABLE IF NOT EXISTS `__PREFIX__ai_channel_call_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) unsigned DEFAULT NULL COMMENT '所属渠道ID',
  `endpoint` varchar(255) DEFAULT NULL COMMENT '调用的接口或功能',
  `success` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否成功',
  `latency_ms` int(11) DEFAULT NULL COMMENT '耗时（毫秒）',
  `error_code` varchar(100) DEFAULT NULL COMMENT '错误代码',
  `error_message` text DEFAULT NULL COMMENT '错误信息',
  `request_id` varchar(100) DEFAULT NULL COMMENT '请求ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_channel_time` (`channel_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 渠道调用日志';

