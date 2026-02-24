-- 014_missing_core_tables.sql
-- 补全星夜阁项目缺失的核心表结构

-- =========================
-- AI智能体相关表
-- =========================

CREATE TABLE IF NOT EXISTS `__PREFIX__ai_agents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '智能体名称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像URL',
  `category` varchar(50) NOT NULL DEFAULT 'general' COMMENT '分类: general,novel,anime,music',
  `type` varchar(50) NOT NULL DEFAULT 'text_polish' COMMENT '类型: text_polish,content_generation,analysis',
  `description` text COMMENT '描述',
  `system_prompt` longtext COMMENT '系统提示词',
  `user_id` int(11) unsigned NOT NULL COMMENT '创建者ID',
  `model_config` text COMMENT '模型配置JSON',
  `capabilities` text COMMENT '能力配置JSON',
  `usage_count` int(11) NOT NULL DEFAULT 0 COMMENT '使用次数',
  `is_public` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否公开',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
  `download_count` int(11) NOT NULL DEFAULT 0 COMMENT '下载次数',
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00 COMMENT '评分',
  `rating_count` int(11) NOT NULL DEFAULT 0 COMMENT '评分人数',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态: 1启用 0禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_public` (`is_public`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI智能体表';

CREATE TABLE IF NOT EXISTS `__PREFIX__ai_agent_market` (
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

CREATE TABLE IF NOT EXISTS `__PREFIX__ai_agent_purchases` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '购买用户ID',
  `agent_id` int(11) unsigned NOT NULL COMMENT '智能体ID',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '购买价格',
  `transaction_id` varchar(100) DEFAULT NULL COMMENT '交易ID',
  `purchased_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_agent` (`user_id`,`agent_id`),
  KEY `idx_agent_id` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI智能体购买记录';

CREATE TABLE IF NOT EXISTS `__PREFIX__ai_agent_reviews` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) unsigned NOT NULL COMMENT '智能体ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '评论用户ID',
  `rating` int(11) NOT NULL DEFAULT 5 COMMENT '评分1-5',
  `content` text COMMENT '评论内容',
  `status` varchar(20) NOT NULL DEFAULT 'approved' COMMENT '状态: pending,approved,rejected',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_agent` (`user_id`,`agent_id`),
  KEY `idx_agent_id` (`agent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI智能体评论表';

-- =========================
-- 用户反馈系统表
-- =========================

CREATE TABLE IF NOT EXISTS `__PREFIX__user_feedback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL COMMENT '用户ID，匿名反馈可为空',
  `type` varchar(20) NOT NULL COMMENT '反馈类型: suggestion,bug_report,other',
  `title` varchar(255) NOT NULL COMMENT '反馈标题',
  `content` longtext NOT NULL COMMENT '反馈内容',
  `attachments` text COMMENT '附件JSON数组',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态: 1待处理 2处理中 3已解决 4已关闭',
  `priority` tinyint(1) NOT NULL DEFAULT 2 COMMENT '优先级: 1低 2中 3高',
  `admin_reply` text COMMENT '管理员回复',
  `reply_at` timestamp NULL DEFAULT NULL COMMENT '回复时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户反馈表';

-- =========================
-- 通知栏系统表
-- =========================

CREATE TABLE IF NOT EXISTS `__PREFIX__notice_bar` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `content` varchar(500) NOT NULL COMMENT '通知内容',
  `link` varchar(255) DEFAULT NULL COMMENT '跳转链接',
  `priority` tinyint(1) NOT NULL DEFAULT 3 COMMENT '优先级: 1-5',
  `start_time` timestamp NULL DEFAULT NULL COMMENT '开始显示时间',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '结束显示时间',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态: 1启用 0禁用',
  `lang` varchar(10) NOT NULL DEFAULT 'zh-CN' COMMENT '语言代码',
  `click_count` int(11) NOT NULL DEFAULT 0 COMMENT '点击次数',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_lang` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知栏表';

-- =========================
-- 公告系统表
-- =========================

CREATE TABLE IF NOT EXISTS `__PREFIX__announcements` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) unsigned DEFAULT NULL COMMENT '分类ID',
  `title` varchar(255) NOT NULL COMMENT '公告标题',
  `content` longtext NOT NULL COMMENT '公告内容',
  `summary` varchar(500) DEFAULT NULL COMMENT '摘要',
  `cover_image` varchar(255) DEFAULT NULL COMMENT '封面图片',
  `is_top` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否置顶',
  `is_popup` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否弹窗显示',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态: 1已发布 0草稿',
  `view_count` int(11) NOT NULL DEFAULT 0 COMMENT '查看次数',
  `published_at` timestamp NULL DEFAULT NULL COMMENT '发布时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_is_top` (`is_top`),
  KEY `idx_published_at` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告表';

CREATE TABLE IF NOT EXISTS `__PREFIX__announcement_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '分类名称',
  `description` varchar(255) DEFAULT NULL COMMENT '分类描述',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告分类表';

CREATE TABLE IF NOT EXISTS `__PREFIX__user_announcement_reads` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `announcement_id` int(11) unsigned NOT NULL COMMENT '公告ID',
  `read_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_announcement` (`user_id`,`announcement_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_announcement_id` (`announcement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户公告阅读记录';

-- =========================
-- 存储配置和清理日志表
-- =========================

CREATE TABLE IF NOT EXISTS `__PREFIX__storage_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL COMMENT '配置键名',
  `config_value` text COMMENT '配置值',
  `description` varchar(255) DEFAULT NULL COMMENT '配置描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='存储配置表';

CREATE TABLE IF NOT EXISTS `__PREFIX__storage_cleanup_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cleanup_type` varchar(50) NOT NULL COMMENT '清理类型: temp_files,expired_drafts,cache',
  `deleted_count` int(11) NOT NULL DEFAULT 0 COMMENT '删除文件数',
  `freed_space` bigint(20) NOT NULL DEFAULT 0 COMMENT '释放空间(字节)',
  `execution_time` decimal(10,3) NOT NULL DEFAULT 0.000 COMMENT '执行时间(秒)',
  `details_json` longtext COMMENT '详细信息JSON',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cleanup_type` (`cleanup_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='存储清理日志表';

-- =========================
-- 文件哈希和缓存表
-- =========================

CREATE TABLE IF NOT EXISTS `__PREFIX__file_hashes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `file_path` varchar(500) NOT NULL COMMENT '文件路径',
  `file_hash` varchar(64) NOT NULL COMMENT '文件哈希值(SHA256)',
  `file_size` bigint(20) NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
  `mime_type` varchar(100) DEFAULT NULL COMMENT 'MIME类型',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_file_path` (`file_path`),
  KEY `idx_file_hash` (`file_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件哈希表';

CREATE TABLE IF NOT EXISTS `__PREFIX__cache` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) NOT NULL COMMENT '缓存键',
  `cache_value` longtext COMMENT '缓存值',
  `expire_time` int(11) NOT NULL COMMENT '过期时间戳',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cache_key` (`cache_key`),
  KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通用缓存表';

-- =========================
-- 系统设置和配置表
-- =========================

CREATE TABLE IF NOT EXISTS `__PREFIX__settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(100) NOT NULL DEFAULT 'general' COMMENT '设置组名',
  `setting_key` varchar(100) NOT NULL COMMENT '设置键名',
  `setting_value` longtext COMMENT '设置值',
  `data_type` varchar(20) NOT NULL DEFAULT 'string' COMMENT '数据类型: string,number,boolean,array,json',
  `description` varchar(255) DEFAULT NULL COMMENT '设置描述',
  `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否系统设置',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_group_key` (`group_name`,`setting_key`),
  KEY `idx_group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表';

-- =========================
-- 用户偏好和限制表
-- =========================

CREATE TABLE IF NOT EXISTS `__PREFIX__user_preferences` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `preference_key` varchar(100) NOT NULL COMMENT '偏好键名',
  `preference_value` longtext COMMENT '偏好值',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_preference` (`user_id`,`preference_key`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户偏好设置表';

CREATE TABLE IF NOT EXISTS `__PREFIX__user_limits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `limit_key` varchar(100) NOT NULL COMMENT '限制键名',
  `current_value` int(11) NOT NULL DEFAULT 0 COMMENT '当前值',
  `max_value` int(11) NOT NULL DEFAULT 0 COMMENT '最大值',
  `reset_interval` varchar(20) DEFAULT NULL COMMENT '重置间隔: daily,weekly,monthly,never',
  `last_reset_at` timestamp NULL DEFAULT NULL COMMENT '上次重置时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_limit` (`user_id`,`limit_key`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户使用限制表';

-- =========================
-- 会员和充值相关表
-- =========================

CREATE TABLE IF NOT EXISTS `__PREFIX__membership_levels` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '等级名称',
  `level` int(11) NOT NULL DEFAULT 1 COMMENT '等级数值',
  `description` text COMMENT '等级描述',
  `price_monthly` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '月费价格',
  `price_yearly` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '年费价格',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员等级表';

CREATE TABLE IF NOT EXISTS `__PREFIX__membership_packages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '套餐名称',
  `membership_level_id` int(11) unsigned NOT NULL COMMENT '会员等级ID',
  `duration_months` int(11) NOT NULL DEFAULT 1 COMMENT '时长(月)',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
  `original_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '原价',
  `coin_bonus` int(11) NOT NULL DEFAULT 0 COMMENT '赠送星夜币',
  `description` text COMMENT '套餐描述',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_membership_level` (`membership_level_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员套餐表';

CREATE TABLE IF NOT EXISTS `__PREFIX__membership_purchase_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `package_id` int(11) unsigned NOT NULL COMMENT '套餐ID',
  `order_id` int(11) unsigned DEFAULT NULL COMMENT '订单ID',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '实际支付价格',
  `coins_received` int(11) NOT NULL DEFAULT 0 COMMENT '获得的星夜币',
  `start_date` datetime NOT NULL COMMENT '生效开始时间',
  `end_date` datetime NOT NULL COMMENT '生效结束时间',
  `status` varchar(20) NOT NULL DEFAULT 'completed' COMMENT '状态: pending,completed,expired,cancelled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_package_id` (`package_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员购买记录表';

CREATE TABLE IF NOT EXISTS `__PREFIX__vip_benefits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `benefit_key` varchar(100) NOT NULL COMMENT '权益键名',
  `benefit_name` varchar(100) NOT NULL COMMENT '权益名称',
  `benefit_type` varchar(50) NOT NULL DEFAULT 'feature' COMMENT '权益类型: feature,quota,discount',
  `description` text COMMENT '权益描述',
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_benefit_key` (`benefit_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='VIP权益表';

CREATE TABLE IF NOT EXISTS `__PREFIX__membership_level_benefits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `membership_level_id` int(11) unsigned NOT NULL COMMENT '会员等级ID',
  `benefit_id` int(11) unsigned NOT NULL COMMENT '权益ID',
  `config_json` longtext COMMENT '权益配置JSON',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_level_benefit` (`membership_level_id`,`benefit_id`),
  KEY `idx_benefit_id` (`benefit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员等级权益关联表';

-- =========================
-- Token消耗记录表
-- =========================

CREATE TABLE IF NOT EXISTS `__PREFIX__token_consumption_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `service_type` varchar(50) NOT NULL COMMENT '服务类型: ai_completion,embedding,translation',
  `model_name` varchar(100) NOT NULL COMMENT '模型名称',
  `input_tokens` int(11) NOT NULL DEFAULT 0 COMMENT '输入token数',
  `output_tokens` int(11) NOT NULL DEFAULT 0 COMMENT '输出token数',
  `cost_coins` int(11) NOT NULL DEFAULT 0 COMMENT '消耗星夜币数',
  `related_id` int(11) unsigned DEFAULT NULL COMMENT '关联资源ID',
  `related_type` varchar(50) DEFAULT NULL COMMENT '关联资源类型',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_service_type` (`service_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Token消耗记录表';

-- =========================
-- 用户Token余额表
-- =========================

CREATE TABLE IF NOT EXISTS `__PREFIX__user_token_balances` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `balance` int(11) NOT NULL DEFAULT 0 COMMENT '当前余额',
  `total_earned` int(11) NOT NULL DEFAULT 0 COMMENT '累计获得',
  `total_spent` int(11) NOT NULL DEFAULT 0 COMMENT '累计消耗',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_id` (`user_id`),
  KEY `idx_balance` (`balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户Token余额表';