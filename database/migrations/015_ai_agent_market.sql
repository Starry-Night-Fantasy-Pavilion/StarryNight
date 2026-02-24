-- 创建缺失的AI智能体市场表
CREATE TABLE IF NOT EXISTS `sn_ai_agent_market` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) unsigned NOT NULL COMMENT '智能体ID',
  `title` varchar(255) NOT NULL COMMENT '市场展示标题',
  `cover_image` varchar(255) DEFAULT NULL COMMENT '封面图片',
  `short_description` varchar(500) DEFAULT NULL COMMENT '简短描述',
  `detailed_description` longtext COMMENT '详细描述',
  `tags` text COMMENT '标签JSON',
  `version` varchar(20) NOT NULL DEFAULT '1.0.0' COMMENT '版本号',
  `downloads` int(11) NOT NULL DEFAULT 0 COMMENT '下载量',
  `reviews_count` int(11) NOT NULL DEFAULT 0 COMMENT '评论数',
  `average_rating` decimal(3,2) NOT NULL DEFAULT 0.00 COMMENT '平均评分',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '状态: pending,approved,rejected',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否推荐',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_agent_id` (`agent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI智能体市场表';