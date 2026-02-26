SET FOREIGN_KEY_CHECKS=0;

-- Restored core schema from current database
-- Generated at 2026-02-13 01:05:37

CREATE TABLE `sn_admin_admin_roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) unsigned NOT NULL,
  `role_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_role` (`admin_id`,`role_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员与角色关联表';

CREATE TABLE `sn_admin_admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '用户名',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密码',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '邮箱',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '头像',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'normal' COMMENT '状态:normal/locked',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '最后登录IP',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台管理员表';

CREATE TABLE `sn_admin_exception_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'error',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `context_json` longtext COLLATE utf8mb4_unicode_ci,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_level` (`level`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台异常日志';

CREATE TABLE `sn_admin_login_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) unsigned DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台登录日志';

CREATE TABLE `sn_admin_operation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) unsigned DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `result` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_module` (`admin_id`,`module`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台操作日志';

CREATE TABLE `sn_admin_plugin_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_plugin_id` (`plugin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件额外配置（保留扩展用）';

CREATE TABLE `sn_admin_plugins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `author` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `namespace` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `main_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'uninstalled',
  `config_json` text COLLATE utf8mb4_unicode_ci,
  `dependencies_json` text COLLATE utf8mb4_unicode_ci,
  `requirements_json` text COLLATE utf8mb4_unicode_ci,
  `install_sql_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uninstall_sql_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frontend_entry` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_entry` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `installed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_plugin_id` (`plugin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_admin_role_permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) unsigned NOT NULL,
  `permission_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_permission_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台角色权限表';

CREATE TABLE `sn_admin_roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统内置角色',
  `data_scope` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all' COMMENT '数据范围',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台角色表';

CREATE TABLE `sn_ai_agent_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '购买者用户ID',
  `agent_id` int(11) NOT NULL COMMENT '智能体ID',
  `type` enum('purchase','rental') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '购买类型：购买/租赁',
  `price` decimal(10,2) NOT NULL COMMENT '支付价格',
  `rental_days` int(11) DEFAULT NULL COMMENT '租赁天数',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '租赁到期时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_agent_id` (`agent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `sn_ai_agent_purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_ai_agent_purchases_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `sn_ai_agents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='智能体购买记录表';

CREATE TABLE `sn_ai_agent_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL COMMENT '智能体ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '评价用户ID',
  `rating` tinyint(1) NOT NULL COMMENT '评分：1-5星',
  `comment` text COLLATE utf8mb4_unicode_ci COMMENT '评价内容',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_agent_user` (`agent_id`,`user_id`),
  KEY `idx_agent_id` (`agent_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `sn_ai_agent_reviews_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `sn_ai_agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_ai_agent_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='智能体评价表';

CREATE TABLE `sn_ai_agents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `creator_id` int(11) unsigned NOT NULL COMMENT '创建者用户ID',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '智能体名称',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '智能体描述',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '智能体头像URL',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '智能体分类',
  `role` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色设定',
  `abilities` json DEFAULT NULL COMMENT '能力配置',
  `prompt_template` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '提示词模板',
  `price_type` enum('free','paid','rental') COLLATE utf8mb4_unicode_ci DEFAULT 'free' COMMENT '价格类型：免费/购买/租赁',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格（星夜币）',
  `rental_daily_price` decimal(10,2) DEFAULT '0.00' COMMENT '日租金（星夜币）',
  `usage_count` int(11) DEFAULT '0' COMMENT '使用次数',
  `rating` decimal(3,2) DEFAULT '0.00' COMMENT '平均评分',
  `rating_count` int(11) DEFAULT '0' COMMENT '评分人数',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1-已发布 0-草稿 -1-已下架',
  `is_featured` tinyint(1) DEFAULT '0' COMMENT '是否推荐',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_creator_id` (`creator_id`),
  KEY `idx_category` (`category`),
  KEY `idx_price_type` (`price_type`),
  KEY `idx_status` (`status`),
  KEY `idx_is_featured` (`is_featured`),
  KEY `idx_rating` (`rating`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `sn_ai_agents_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI智能体表';

CREATE TABLE `sn_ai_channel_call_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) unsigned DEFAULT NULL COMMENT '所属渠道ID',
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '调用的接口或功能',
  `success` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否成功',
  `latency_ms` int(11) DEFAULT NULL COMMENT '耗时（毫秒）',
  `error_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '错误代码',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT '错误信息',
  `request_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '请求ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_channel_time` (`channel_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 渠道调用日志';


CREATE TABLE `sn_ai_embedding_models` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'openai',
  `base_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` text COLLATE utf8mb4_unicode_ci,
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `is_user_customizable` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name_type` (`name`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='嵌入模型配置表';

CREATE TABLE `sn_ai_model_prices` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) unsigned NOT NULL,
  `model_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_coin_per_1k` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `output_coin_per_1k` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `profit_percent` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_channel_model` (`channel_id`,`model_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 模型价格表';

CREATE TABLE `sn_ai_music_arrangement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `arrangement_data` longtext COLLATE utf8mb4_unicode_ci COMMENT '编曲数据（包含乐器、和弦、节奏等）',
  `style` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '编曲风格',
  `instrument_config` json DEFAULT NULL COMMENT '乐器配置',
  `chord_progression` json DEFAULT NULL COMMENT '和弦进行',
  `rhythm_pattern` json DEFAULT NULL COMMENT '节奏型',
  `density` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'medium' COMMENT '织体密度: sparse=稀疏, medium=中等, dense=密集',
  `is_ai_generated` tinyint(1) DEFAULT '0' COMMENT '是否AI生成',
  `generation_parameters` json DEFAULT NULL COMMENT '生成参数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  CONSTRAINT `fk_arrangement_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐编曲表';

CREATE TABLE `sn_ai_music_asset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '素材名称',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '素材描述',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '素材类型（loop/sample/preset/effect）',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '素材分类',
  `style` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '音乐风格',
  `instrument` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '乐器',
  `key_signature` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '调性',
  `tempo` int(11) DEFAULT NULL COMMENT '速度（BPM）',
  `duration` int(11) DEFAULT NULL COMMENT '时长（秒）',
  `file_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件URL',
  `file_size` bigint(20) DEFAULT NULL COMMENT '文件大小（字节）',
  `file_format` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件格式',
  `sample_rate` int(11) DEFAULT NULL COMMENT '采样率',
  `bit_depth` int(11) DEFAULT NULL COMMENT '位深度',
  `channels` int(11) DEFAULT NULL COMMENT '声道数',
  `bpm` int(11) DEFAULT NULL COMMENT 'BPM',
  `tags` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标签，逗号分隔',
  `is_premium` tinyint(1) DEFAULT '0' COMMENT '是否付费素材',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格',
  `usage_count` int(11) DEFAULT '0' COMMENT '使用次数',
  `rating` decimal(3,2) DEFAULT '0.00' COMMENT '评分',
  `rating_count` int(11) DEFAULT '0' COMMENT '评分人数',
  `uploaded_by` int(11) unsigned DEFAULT NULL COMMENT '上传者ID',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_category` (`category`),
  KEY `idx_style` (`style`),
  KEY `idx_instrument` (`instrument`),
  KEY `idx_is_premium` (`is_premium`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_type_category` (`type`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐素材库表';

CREATE TABLE `sn_ai_music_collaboration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '协作者用户ID',
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'collaborator' COMMENT '角色（owner/collaborator/viewer）',
  `permissions` json DEFAULT NULL COMMENT '权限（编辑/查看/特定音轨编辑）',
  `invited_by` int(11) unsigned DEFAULT NULL COMMENT '邀请人ID',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '状态（pending/accepted/declined/removed）',
  `last_activity` datetime DEFAULT NULL COMMENT '最后活动时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_user` (`project_id`,`user_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_project_status` (`project_id`,`status`),
  CONSTRAINT `fk_collab_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_collab_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐协作表';

CREATE TABLE `sn_ai_music_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '评论者ID',
  `parent_id` int(11) DEFAULT NULL COMMENT '父评论ID（回复）',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '评论内容',
  `track_id` int(11) DEFAULT NULL COMMENT '关联音轨ID（音轨特定评论）',
  `timestamp` int(11) DEFAULT NULL COMMENT '时间戳（秒，音轨特定评论）',
  `is_public` tinyint(1) DEFAULT '1' COMMENT '是否公开',
  `like_count` int(11) DEFAULT '0' COMMENT '点赞数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_track_id` (`track_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_project_created` (`project_id`,`created_at`),
  CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_id`) REFERENCES `sn_ai_music_comment` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_comment_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_track` FOREIGN KEY (`track_id`) REFERENCES `sn_ai_music_track` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐评论表';

CREATE TABLE `sn_ai_music_export` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `mix_master_id` int(11) DEFAULT NULL COMMENT '混音母带ID',
  `format` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '导出格式（MP3/WAV/FLAC/AAC）',
  `quality` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '音质（128kbps/256kbps/320kbps/lossless）',
  `file_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '导出文件URL',
  `file_size` bigint(20) DEFAULT NULL COMMENT '文件大小（字节）',
  `duration` int(11) DEFAULT NULL COMMENT '时长（秒）',
  `sample_rate` int(11) DEFAULT NULL COMMENT '采样率',
  `bit_rate` int(11) DEFAULT NULL COMMENT '比特率',
  `channels` int(11) DEFAULT '2' COMMENT '声道数',
  `export_settings` json DEFAULT NULL COMMENT '导出设置',
  `download_count` int(11) DEFAULT '0' COMMENT '下载次数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_mix_master_id` (`mix_master_id`),
  KEY `idx_format` (`format`),
  KEY `idx_project_format` (`project_id`,`format`),
  CONSTRAINT `fk_export_mixmaster` FOREIGN KEY (`mix_master_id`) REFERENCES `sn_ai_music_mix_master` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_export_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐导出记录表';

CREATE TABLE `sn_ai_music_favorite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_project` (`user_id`,`project_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_project_id` (`project_id`),
  CONSTRAINT `fk_favorite_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_favorite_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐收藏表';

CREATE TABLE `sn_ai_music_lyrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '歌词内容',
  `emotion_analysis` json DEFAULT NULL COMMENT '情感分析结果',
  `structure` json DEFAULT NULL COMMENT '歌词结构（主歌、副歌、桥段等）',
  `rhyme_scheme` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '韵律方案',
  `syllable_count` int(11) DEFAULT NULL COMMENT '音节数',
  `language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'zh-CN' COMMENT '语言',
  `is_ai_generated` tinyint(1) DEFAULT '0' COMMENT '是否AI生成',
  `generation_prompt` text COLLATE utf8mb4_unicode_ci COMMENT '生成提示词',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  CONSTRAINT `fk_lyrics_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐歌词表';

CREATE TABLE `sn_ai_music_melody` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `midi_data` longtext COLLATE utf8mb4_unicode_ci COMMENT 'MIDI数据（或JSON表示的音符数据）',
  `notation_data` json DEFAULT NULL COMMENT '乐谱数据',
  `tempo` int(11) DEFAULT NULL COMMENT '速度（BPM）',
  `key_signature` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '调性',
  `time_signature` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '4/4' COMMENT '拍号',
  `melody_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'generated' COMMENT '旋律类型: generated=AI生成, humming=哼唱识别, manual=手动创建',
  `source_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '源文件路径（哼唱录音等）',
  `is_ai_generated` tinyint(1) DEFAULT '0' COMMENT '是否AI生成',
  `generation_parameters` json DEFAULT NULL COMMENT '生成参数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  CONSTRAINT `fk_melody_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐旋律表';

CREATE TABLE `sn_ai_music_mix_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型（mix/master）',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '混音/母带版本名称',
  `settings` json DEFAULT NULL COMMENT '混音/母带参数',
  `output_audio_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '混音/母带成品URL',
  `output_format` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'wav' COMMENT '输出格式',
  `output_quality` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'high' COMMENT '输出质量',
  `loudness` decimal(8,2) DEFAULT NULL COMMENT '响度（LUFS）',
  `peak_level` decimal(8,2) DEFAULT NULL COMMENT '峰值电平（dB）',
  `is_ai_processed` tinyint(1) DEFAULT '0' COMMENT '是否AI处理',
  `processing_time` int(11) DEFAULT NULL COMMENT '处理时间（秒）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_type` (`type`),
  CONSTRAINT `fk_mixmaster_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐混音母带表';

CREATE TABLE `sn_ai_music_project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '音乐项目标题',
  `genre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '音乐风格',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '音乐项目简介',
  `status` tinyint(1) DEFAULT '1' COMMENT '项目状态: 1=草稿, 2=进行中, 3=已完成, 4=已发布',
  `bpm` int(11) DEFAULT NULL COMMENT '音乐速度（BPM）',
  `key_signature` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '调性',
  `duration` int(11) DEFAULT NULL COMMENT '音乐时长（秒）',
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '封面图片URL',
  `tags` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标签，逗号分隔',
  `is_public` tinyint(1) DEFAULT '0' COMMENT '是否公开',
  `view_count` int(11) DEFAULT '0' COMMENT '查看次数',
  `like_count` int(11) DEFAULT '0' COMMENT '点赞次数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_genre` (`genre`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_genre_status` (`genre`,`status`),
  CONSTRAINT `fk_project_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐项目表';

CREATE TABLE `sn_ai_music_share` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '分享者ID',
  `platform` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '分享平台',
  `share_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分享链接',
  `share_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分享码',
  `access_count` int(11) DEFAULT '0' COMMENT '访问次数',
  `download_count` int(11) DEFAULT '0' COMMENT '下载次数',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否有效',
  `expires_at` datetime DEFAULT NULL COMMENT '过期时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_share_code` (`share_code`),
  CONSTRAINT `fk_share_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_share_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐分享记录表';

CREATE TABLE `sn_ai_music_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板名称',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '模板描述',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板分类',
  `style` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '音乐风格',
  `genre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '音乐类型',
  `mood` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '情绪',
  `tempo` int(11) DEFAULT NULL COMMENT '建议速度（BPM）',
  `key_signature` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '建议调性',
  `time_signature` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '4/4' COMMENT '拍号',
  `duration` int(11) DEFAULT NULL COMMENT '建议时长（秒）',
  `instrument_config` json DEFAULT NULL COMMENT '乐器配置',
  `chord_progression` json DEFAULT NULL COMMENT '和弦进行',
  `structure` json DEFAULT NULL COMMENT '歌曲结构',
  `preview_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '预览音频URL',
  `thumbnail_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '缩略图URL',
  `is_premium` tinyint(1) DEFAULT '0' COMMENT '是否付费模板',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格',
  `usage_count` int(11) DEFAULT '0' COMMENT '使用次数',
  `rating` decimal(3,2) DEFAULT '0.00' COMMENT '评分',
  `rating_count` int(11) DEFAULT '0' COMMENT '评分人数',
  `created_by` int(11) unsigned DEFAULT NULL COMMENT '创建者ID',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_style` (`style`),
  KEY `idx_genre` (`genre`),
  KEY `idx_mood` (`mood`),
  KEY `idx_is_premium` (`is_premium`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_category_style` (`category`,`style`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐模板库表';

CREATE TABLE `sn_ai_music_track` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '音轨名称',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '音轨类型（melody/chord/drums/bass/vocal/effect）',
  `instrument` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '乐器名称/音色',
  `audio_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '音频文件URL（如果适用）',
  `midi_data` longtext COLLATE utf8mb4_unicode_ci COMMENT 'MIDI数据（如果适用）',
  `waveform_data` json DEFAULT NULL COMMENT '波形数据',
  `volume` decimal(5,2) DEFAULT '0.00' COMMENT '音量（-100到100）',
  `pan` decimal(5,2) DEFAULT '0.00' COMMENT '声像（-100左到100右）',
  `mute` tinyint(1) DEFAULT '0' COMMENT '是否静音',
  `solo` tinyint(1) DEFAULT '0' COMMENT '是否独奏',
  `effects` json DEFAULT NULL COMMENT '效果器链配置',
  `automation` json DEFAULT NULL COMMENT '参数自动化曲线',
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '音轨颜色',
  `height` int(11) DEFAULT '100' COMMENT '音轨高度（像素）',
  `position` int(11) DEFAULT '0' COMMENT '音轨位置顺序',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_type` (`type`),
  KEY `idx_position` (`position`),
  KEY `idx_project_type` (`project_id`,`type`),
  CONSTRAINT `fk_track_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐音轨表';

CREATE TABLE `sn_ai_music_usage_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `project_id` int(11) DEFAULT NULL COMMENT '音乐项目ID',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '操作类型',
  `details` json DEFAULT NULL COMMENT '操作详情',
  `duration` int(11) DEFAULT NULL COMMENT '操作时长（秒）',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户代理',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_action_date` (`user_id`,`action`,`created_at`),
  CONSTRAINT `fk_usage_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_usage_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐使用统计表';

CREATE TABLE `sn_ai_music_version_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `version_number` int(11) NOT NULL COMMENT '版本号',
  `version_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '版本名称',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '版本描述',
  `change_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '变更类型（create/update/delete/major/minor）',
  `changed_by` int(11) unsigned NOT NULL COMMENT '变更者ID',
  `snapshot_data` longtext COLLATE utf8mb4_unicode_ci COMMENT '项目快照数据',
  `backup_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '备份文件URL',
  `is_autosave` tinyint(1) DEFAULT '0' COMMENT '是否自动保存',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_version_number` (`version_number`),
  KEY `idx_changed_by` (`changed_by`),
  KEY `idx_project_version` (`project_id`,`version_number`),
  CONSTRAINT `fk_version_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_version_user` FOREIGN KEY (`changed_by`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐版本历史表';

CREATE TABLE `sn_ai_music_video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `export_id` int(11) DEFAULT NULL COMMENT '关联导出记录ID',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '视频标题',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '视频描述',
  `video_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '视频文件URL',
  `thumbnail_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '视频缩略图URL',
  `duration` int(11) DEFAULT NULL COMMENT '视频时长（秒）',
  `resolution` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '1920x1080' COMMENT '视频分辨率',
  `fps` int(11) DEFAULT '30' COMMENT '帧率',
  `style` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '视频风格',
  `generation_prompt` text COLLATE utf8mb4_unicode_ci COMMENT 'AI生成提示词',
  `is_ai_generated` tinyint(1) DEFAULT '0' COMMENT '是否AI生成',
  `view_count` int(11) DEFAULT '0' COMMENT '观看次数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_export_id` (`export_id`),
  CONSTRAINT `fk_video_export` FOREIGN KEY (`export_id`) REFERENCES `sn_ai_music_export` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_video_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐视频表';

CREATE TABLE `sn_ai_music_vocal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '音乐项目ID',
  `track_id` int(11) DEFAULT NULL COMMENT '关联音轨ID',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '人声类型（recorded/synthesized/uploaded）',
  `audio_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '人声音频文件URL',
  `lyrics_id` int(11) DEFAULT NULL COMMENT '关联歌词ID',
  `voice_model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'AI语音模型（合成人声时使用）',
  `pitch_correction` tinyint(1) DEFAULT '0' COMMENT '是否应用音准修正',
  `noise_reduction` tinyint(1) DEFAULT '0' COMMENT '是否应用降噪',
  `effects` json DEFAULT NULL COMMENT '人声效果器配置',
  `recording_device` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '录音设备',
  `sample_rate` int(11) DEFAULT '44100' COMMENT '采样率',
  `bit_depth` int(11) DEFAULT '16' COMMENT '位深度',
  `duration` int(11) DEFAULT NULL COMMENT '时长（秒）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_track_id` (`track_id`),
  KEY `idx_lyrics_id` (`lyrics_id`),
  CONSTRAINT `fk_vocal_lyrics` FOREIGN KEY (`lyrics_id`) REFERENCES `sn_ai_music_lyrics` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vocal_project` FOREIGN KEY (`project_id`) REFERENCES `sn_ai_music_project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vocal_track` FOREIGN KEY (`track_id`) REFERENCES `sn_ai_music_track` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI音乐人声表';

CREATE TABLE `sn_ai_preset_models` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `default_channel_id` int(11) unsigned DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='预设模型名称';

CREATE TABLE `sn_ai_prompt_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `variables` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `tags` text COLLATE utf8mb4_unicode_ci,
  `usage_count` int(11) NOT NULL DEFAULT '0',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `download_count` int(11) NOT NULL DEFAULT '0',
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `rating_count` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_category` (`category`),
  KEY `idx_public_status` (`is_public`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 提示词模板';

CREATE TABLE `sn_ai_resource_audit_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `queue_id` int(11) unsigned NOT NULL,
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'approve/reject',
  `reviewer_id` int(11) unsigned DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_queue` (`queue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 资源审核操作日志';

CREATE TABLE `sn_ai_resource_audit_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `resource_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_id` int(11) unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending/approved/rejected',
  `payload_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`resource_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 资源审核队列表';

CREATE TABLE `sn_anime` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `views` int(11) NOT NULL DEFAULT '0',
  `likes` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动漫作品表（兼容 Anime 模型）';

CREATE TABLE `sn_anime_ai_generations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `prompt` text COLLATE utf8mb4_unicode_ci,
  `result_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动漫 AI 生成功能记录';

CREATE TABLE `sn_anime_animations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `file_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'generating',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='生成的动画资源';

CREATE TABLE `sn_anime_audio_productions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `file_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'generating',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动漫音频';

CREATE TABLE `sn_anime_characters` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动漫角色';

CREATE TABLE `sn_anime_episode_scripts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `episode_no` int(11) NOT NULL DEFAULT '1',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `script` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_episode` (`project_id`,`episode_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动漫分集剧本';

CREATE TABLE `sn_anime_production_progress` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `stage` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `progress` int(11) NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='制作进度';

CREATE TABLE `sn_anime_projects` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `meta_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动漫项目';

CREATE TABLE `sn_anime_publications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `platform` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publish_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `publish_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动漫发布记录';

CREATE TABLE `sn_anime_scenes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `script` longtext COLLATE utf8mb4_unicode_ci,
  `order_index` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动漫场景';

CREATE TABLE `sn_anime_short_dramas` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `script` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='短剧脚本';

CREATE TABLE `sn_anime_storyboards` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `scene_id` int(11) unsigned DEFAULT NULL,
  `order_index` int(11) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scene` (`scene_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分镜';

CREATE TABLE `sn_anime_video_compositions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `file_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'generating',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动漫视频合成记录';

CREATE TABLE `sn_anime_world_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='世界观设定';

CREATE TABLE `sn_animes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='兼容备用动漫表名 animes';

CREATE TABLE `sn_announcement_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告分类';

CREATE TABLE `sn_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '公告标题',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '公告内容（富文本）',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '公告分类：system_update/activity_notice/maintenance',
  `is_top` tinyint(1) DEFAULT '0' COMMENT '是否置顶',
  `is_popup` tinyint(1) DEFAULT '0' COMMENT '是否弹窗显示',
  `status` tinyint(1) DEFAULT '1' COMMENT '发布状态：1-已发布 0-草稿',
  `published_at` timestamp NULL DEFAULT NULL COMMENT '发布时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_is_top` (`is_top`),
  KEY `idx_published_at` (`published_at`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告系统表';

CREATE TABLE `sn_api_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '所属用户',
  `name` varchar(50) NOT NULL COMMENT '密钥名称',
  `key` varchar(64) NOT NULL COMMENT 'API密钥（sk-xxx格式）',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用 2-禁用 3-已过期',
  `models` json DEFAULT NULL COMMENT '允许使用的模型列表（空表示全部）',
  `allow_channels` json DEFAULT NULL COMMENT '允许使用的渠道ID列表',
  `deny_channels` json DEFAULT NULL COMMENT '禁止使用的渠道ID列表',
  `unlimited_quota` tinyint(4) DEFAULT '0' COMMENT '是否无限配额',
  `remain_quota` bigint(20) DEFAULT '0' COMMENT '剩余配额（星夜币，字符数）',
  `used_quota` bigint(20) DEFAULT '0' COMMENT '已使用配额（星夜币，字符数）',
  `request_count` int(11) DEFAULT '0' COMMENT '总请求次数',
  `rate_limit` int(11) DEFAULT '0' COMMENT '每分钟请求限制（0表示无限制）',
  `rate_limit_window` int(11) DEFAULT '60' COMMENT '限流时间窗口（秒）',
  `expired_at` timestamp NULL DEFAULT NULL COMMENT '过期时间（NULL表示永久有效）',
  `ip_whitelist` json DEFAULT NULL COMMENT 'IP白名单（空表示不限制）',
  `last_used_at` timestamp NULL DEFAULT NULL COMMENT '最后使用时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_key` (`key`),
  CONSTRAINT `sn_api_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sn_api_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `endpoint` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_code` int(11) NOT NULL,
  `latency_ms` int(11) DEFAULT NULL,
  `request_body` longtext COLLATE utf8mb4_unicode_ci,
  `response_body` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API 调用日志';

CREATE TABLE `sn_books` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `views` int(11) NOT NULL DEFAULT '0',
  `likes` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通用书籍/作品表';

CREATE TABLE `sn_channels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(30) NOT NULL COMMENT '渠道类型：openai/claude/gemini/deepseek等',
  `name` varchar(50) NOT NULL COMMENT '渠道名称',
  `base_url` varchar(255) NOT NULL COMMENT 'API基础URL',
  `key` varchar(500) NOT NULL COMMENT 'API密钥（加密存储）',
  `is_free` tinyint(4) DEFAULT '0' COMMENT '是否免费通道：0-否 1-是',
  `is_user_custom` tinyint(4) DEFAULT '0' COMMENT '是否用户自定义渠道：0-否 1-是',
  `models` json NOT NULL COMMENT '支持的模型列表',
  `config` json DEFAULT NULL COMMENT '额外配置参数',
  `proxy` varchar(255) DEFAULT NULL COMMENT '代理地址',
  `priority` int(11) DEFAULT '0' COMMENT '优先级（越大越优先）',
  `weight` int(11) DEFAULT '100' COMMENT '负载均衡权重',
  `status` tinyint(4) DEFAULT '1' COMMENT '1-启用 2-禁用 3-自动禁用',
  `auto_disable` tinyint(4) DEFAULT '1' COMMENT '连续失败后自动禁用',
  `max_retries` int(11) DEFAULT '3' COMMENT '最大重试次数',
  `balance` decimal(10,2) DEFAULT NULL COMMENT 'API余额（美元）',
  `balance_updated_at` timestamp NULL DEFAULT NULL COMMENT '余额更新时间',
  `used_quota` bigint(20) DEFAULT '0' COMMENT '已使用配额',
  `test_model` varchar(50) DEFAULT NULL COMMENT '测试用模型',
  `last_test_at` timestamp NULL DEFAULT NULL COMMENT '最后测试时间',
  `test_status` tinyint(4) DEFAULT NULL COMMENT '测试状态：1-成功 0-失败',
  `test_response_time` int(11) DEFAULT NULL COMMENT '测试响应时间（毫秒）',
  `request_count` int(11) DEFAULT '0' COMMENT '总请求次数',
  `success_count` int(11) DEFAULT '0' COMMENT '成功次数',
  `fail_count` int(11) DEFAULT '0' COMMENT '失败次数',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`type`,`status`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sn_coin_packages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '充值金额',
  `coin_amount` int(11) NOT NULL DEFAULT '0' COMMENT '获得的代币数量',
  `valid_days` int(11) NOT NULL DEFAULT '0' COMMENT '有效期天数，为0表示永久',
  `sale_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on_sale' COMMENT 'on_sale/off_sale',
  `is_limited_offer` tinyint(1) NOT NULL DEFAULT '0',
  `offer_start_at` datetime DEFAULT NULL,
  `offer_end_at` datetime DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值套餐（星夜币包）';

CREATE TABLE `sn_coin_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'recharge/spend/adjust',
  `amount` int(11) NOT NULL COMMENT '变动数量，可为负',
  `balance_after` int(11) NOT NULL DEFAULT '0' COMMENT '变动后的余额',
  `related_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '关联业务类型',
  `related_id` int(11) unsigned DEFAULT NULL COMMENT '关联业务ID',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_created` (`user_id`,`created_at`),
  KEY `idx_related` (`related_type`,`related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='星夜币流水记录';

CREATE TABLE `sn_collaboration_contents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '项目ID',
  `creator_id` int(11) unsigned NOT NULL COMMENT '创建者用户ID',
  `type` enum('novel','anime','music','comment','annotation') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容类型',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '内容标题',
  `content` longtext COLLATE utf8mb4_unicode_ci COMMENT '内容数据',
  `file_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文件URL',
  `parent_id` int(11) DEFAULT NULL COMMENT '父内容ID（用于评论和批注）',
  `version` int(11) DEFAULT '1' COMMENT '版本号',
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft' COMMENT '内容状态',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_creator_id` (`creator_id`),
  KEY `idx_type` (`type`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `sn_collaboration_contents_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `sn_collaboration_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_collaboration_contents_ibfk_2` FOREIGN KEY (`creator_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_collaboration_contents_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `sn_collaboration_contents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='协作内容表';

CREATE TABLE `sn_collaboration_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT '项目ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `role` enum('owner','admin','editor','viewer') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '成员角色',
  `permissions` json DEFAULT NULL COMMENT '详细权限配置',
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_project_user` (`project_id`,`user_id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_role` (`role`),
  CONSTRAINT `sn_collaboration_members_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `sn_collaboration_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_collaboration_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='协作成员表';

CREATE TABLE `sn_collaboration_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '项目名称',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '项目描述',
  `creator_id` int(11) unsigned NOT NULL COMMENT '创建者用户ID',
  `type` enum('novel','anime','music','mixed') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '项目类型：小说/动漫/音乐/混合',
  `status` enum('planning','active','completed','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'planning' COMMENT '项目状态',
  `is_public` tinyint(1) DEFAULT '0' COMMENT '是否公开',
  `settings` json DEFAULT NULL COMMENT '项目设置',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_creator_id` (`creator_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_is_public` (`is_public`),
  CONSTRAINT `sn_collaboration_projects_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='协作项目表';

CREATE TABLE `sn_comments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int(11) unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_target` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通用评论表';

CREATE TABLE `sn_community_activities` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区活动';

CREATE TABLE `sn_community_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区分类';

CREATE TABLE `sn_community_contents` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `category_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区内容';

CREATE TABLE `sn_community_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL COMMENT '帖子ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '回复用户ID',
  `parent_id` int(11) DEFAULT NULL COMMENT '父回复ID',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '回复内容',
  `like_count` int(11) DEFAULT '0' COMMENT '点赞数',
  `is_best_answer` tinyint(1) DEFAULT '0' COMMENT '是否最佳答案',
  `status` enum('published','hidden','deleted') COLLATE utf8mb4_unicode_ci DEFAULT 'published' COMMENT '回复状态',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_is_best_answer` (`is_best_answer`),
  KEY `idx_status` (`status`),
  CONSTRAINT `sn_community_replies_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `sn_creation_community` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_community_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_community_replies_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `sn_community_replies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区回复表';

CREATE TABLE `sn_community_reports` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `reporter_id` int(11) unsigned NOT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int(11) unsigned NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_target` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区举报';

CREATE TABLE `sn_community_tags` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区标签';

CREATE TABLE `sn_consistency_conflicts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `project_id` int(11) unsigned NOT NULL,
  `project_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_id` int(11) unsigned DEFAULT NULL,
  `content_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conflict_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `conflicting_content` longtext COLLATE utf8mb4_unicode_ci,
  `reference_setting` longtext COLLATE utf8mb4_unicode_ci,
  `suggestion` longtext COLLATE utf8mb4_unicode_ci,
  `similarity_score` decimal(5,4) DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT '0',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_report` (`report_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='一致性检查冲突记录';

CREATE TABLE `sn_consistency_reports` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `project_id` int(11) unsigned NOT NULL,
  `project_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_id` int(11) unsigned DEFAULT NULL,
  `content_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_data` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `execution_time` int(11) DEFAULT NULL COMMENT '执行耗时（毫秒）',
  `tokens_used` int(11) DEFAULT NULL COMMENT '消耗的Tokens',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_project` (`user_id`,`project_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='一致性检查报告';

CREATE TABLE `sn_content_review_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rules_json` longtext COLLATE utf8mb4_unicode_ci,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容审查配置';

CREATE TABLE `sn_content_review_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `queue_id` int(11) unsigned DEFAULT NULL,
  `reviewer_id` int(11) unsigned DEFAULT NULL,
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_queue` (`queue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容审查日志';

CREATE TABLE `sn_content_review_queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `config_id` int(11) unsigned DEFAULT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int(11) unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payload_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容审查队列';

CREATE TABLE `sn_contest_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contest_id` int(11) NOT NULL COMMENT '大赛ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '投稿用户ID',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '作品标题',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '作品描述',
  `content_type` enum('novel','anime','music','other') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容类型',
  `content_data` longtext COLLATE utf8mb4_unicode_ci COMMENT '内容数据',
  `file_urls` json DEFAULT NULL COMMENT '文件URL列表',
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '缩略图URL',
  `status` enum('submitted','under_review','approved','rejected','winner') COLLATE utf8mb4_unicode_ci DEFAULT 'submitted' COMMENT '投稿状态',
  `vote_count` int(11) DEFAULT '0' COMMENT '投票数',
  `judge_score` decimal(5,2) DEFAULT NULL COMMENT '评委评分',
  `final_rank` int(11) DEFAULT NULL COMMENT '最终排名',
  `prize_amount` decimal(10,2) DEFAULT NULL COMMENT '获奖金额',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` timestamp NULL DEFAULT NULL COMMENT '审核时间',
  PRIMARY KEY (`id`),
  KEY `idx_contest_id` (`contest_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_status` (`status`),
  KEY `idx_vote_count` (`vote_count`),
  KEY `idx_judge_score` (`judge_score`),
  CONSTRAINT `sn_contest_submissions_ibfk_1` FOREIGN KEY (`contest_id`) REFERENCES `sn_creation_contests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_contest_submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='大赛投稿表';

CREATE TABLE `sn_contest_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contest_id` int(11) NOT NULL COMMENT '大赛ID',
  `submission_id` int(11) NOT NULL COMMENT '投稿ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '投票用户ID',
  `score` tinyint(1) DEFAULT NULL COMMENT '评分（1-5分）',
  `voted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_contest_user` (`contest_id`,`user_id`),
  KEY `idx_contest_id` (`contest_id`),
  KEY `idx_submission_id` (`submission_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `sn_contest_votes_ibfk_1` FOREIGN KEY (`contest_id`) REFERENCES `sn_creation_contests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_contest_votes_ibfk_2` FOREIGN KEY (`submission_id`) REFERENCES `sn_contest_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_contest_votes_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='大赛投票表';

CREATE TABLE `sn_copyright_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `content_type` enum('novel','anime','music','image','other') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容类型',
  `content_id` int(11) DEFAULT NULL COMMENT '关联内容ID',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '作品标题',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '作品描述',
  `content_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '内容哈希值',
  `blockchain_tx_hash` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '区块链交易哈希',
  `blockchain_address` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '区块链地址',
  `registration_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '登记编号',
  `metadata` json DEFAULT NULL COMMENT '元数据',
  `status` enum('pending','registered','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '登记状态',
  `registered_at` timestamp NULL DEFAULT NULL COMMENT '登记时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_content_hash` (`content_hash`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_status` (`status`),
  KEY `idx_registration_number` (`registration_number`),
  CONSTRAINT `sn_copyright_registrations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='版权登记表';

CREATE TABLE `sn_core_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='核心系统设置';

CREATE TABLE `sn_coupons` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'amount',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='优惠券';

CREATE TABLE `sn_course_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL COMMENT '课程ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `progress` decimal(5,2) DEFAULT '0.00' COMMENT '学习进度百分比',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT '完成时间',
  `certificate_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '证书URL',
  `enrolled_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_accessed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_course_user` (`course_id`,`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_progress` (`progress`),
  CONSTRAINT `sn_course_enrollments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `sn_education_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_course_enrollments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程学习记录表';

CREATE TABLE `sn_course_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL COMMENT '课程ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '评价用户ID',
  `rating` tinyint(1) NOT NULL COMMENT '评分：1-5星',
  `comment` text COLLATE utf8mb4_unicode_ci COMMENT '评价内容',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_course_user` (`course_id`,`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `sn_course_reviews_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `sn_education_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_course_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程评价表';

CREATE TABLE `sn_creation_community` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '发帖用户ID',
  `type` enum('question','discussion','tutorial','showcase') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '帖子类型',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '帖子标题',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '帖子内容',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '帖子分类',
  `tags` json DEFAULT NULL COMMENT '标签列表',
  `attachments` json DEFAULT NULL COMMENT '附件URL列表',
  `view_count` int(11) DEFAULT '0' COMMENT '查看次数',
  `like_count` int(11) DEFAULT '0' COMMENT '点赞数',
  `reply_count` int(11) DEFAULT '0' COMMENT '回复数',
  `is_pinned` tinyint(1) DEFAULT '0' COMMENT '是否置顶',
  `is_locked` tinyint(1) DEFAULT '0' COMMENT '是否锁定',
  `status` enum('published','hidden','deleted') COLLATE utf8mb4_unicode_ci DEFAULT 'published' COMMENT '帖子状态',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_is_pinned` (`is_pinned`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `sn_creation_community_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='创作社区表';

CREATE TABLE `sn_creation_contests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '大赛标题',
  `description` longtext COLLATE utf8mb4_unicode_ci COMMENT '大赛描述',
  `type` enum('novel','anime','music','comprehensive') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '大赛类型',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '大赛分类',
  `prize_pool` decimal(10,2) DEFAULT '0.00' COMMENT '奖金池（星夜币）',
  `prize_distribution` json DEFAULT NULL COMMENT '奖金分配方案',
  `submission_start` timestamp NOT NULL COMMENT '投稿开始时间',
  `submission_end` timestamp NOT NULL COMMENT '投稿结束时间',
  `voting_start` timestamp NULL DEFAULT NULL COMMENT '投票开始时间',
  `voting_end` timestamp NULL DEFAULT NULL COMMENT '投票结束时间',
  `announcement_at` timestamp NULL DEFAULT NULL COMMENT '结果公布时间',
  `max_submissions` int(11) DEFAULT NULL COMMENT '最大投稿数',
  `rules` longtext COLLATE utf8mb4_unicode_ci COMMENT '大赛规则',
  `judges` json DEFAULT NULL COMMENT '评委信息',
  `status` enum('draft','upcoming','active','voting','judging','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'draft' COMMENT '大赛状态',
  `is_featured` tinyint(1) DEFAULT '0' COMMENT '是否推荐',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_submission_start` (`submission_start`),
  KEY `idx_submission_end` (`submission_end`),
  KEY `idx_is_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='创作大赛表';

CREATE TABLE `sn_creation_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='创作模板';

CREATE TABLE `sn_creation_tool_usage_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tool_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `input_data` longtext COLLATE utf8mb4_unicode_ci,
  `output_data` longtext COLLATE utf8mb4_unicode_ci,
  `execution_time` int(11) DEFAULT NULL,
  `tokens_used` int(11) DEFAULT NULL,
  `coins_spent` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tool` (`tool_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='创作工具使用日志';

CREATE TABLE `sn_creation_tools` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prompt_template` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_schema` longtext COLLATE utf8mb4_unicode_ci,
  `output_schema` longtext COLLATE utf8mb4_unicode_ci,
  `usage_count` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='创作工具定义';

CREATE TABLE `sn_creation_traces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `copyright_id` int(11) NOT NULL COMMENT '版权登记ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '操作用户ID',
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '操作类型',
  `ai_model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '使用的AI模型',
  `prompt` text COLLATE utf8mb4_unicode_ci COMMENT '提示词',
  `parameters` json DEFAULT NULL COMMENT '参数配置',
  `input_data` longtext COLLATE utf8mb4_unicode_ci COMMENT '输入数据',
  `output_data` longtext COLLATE utf8mb4_unicode_ci COMMENT '输出数据',
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_copyright_id` (`copyright_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_timestamp` (`timestamp`),
  CONSTRAINT `sn_creation_traces_ibfk_1` FOREIGN KEY (`copyright_id`) REFERENCES `sn_copyright_registrations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_creation_traces_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='创作溯源记录表';

CREATE TABLE `sn_crowdfunding_pledges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `project_id` int(11) NOT NULL,
  `reward_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','successful','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_crowdfunding_pledges_user_id` (`user_id`),
  KEY `idx_crowdfunding_pledges_project_id` (`project_id`),
  KEY `idx_crowdfunding_pledges_reward_id` (`reward_id`),
  CONSTRAINT `fk_crowdfunding_pledges_project` FOREIGN KEY (`project_id`) REFERENCES `sn_crowdfunding_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_crowdfunding_pledges_reward` FOREIGN KEY (`reward_id`) REFERENCES `sn_crowdfunding_rewards` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_crowdfunding_pledges_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_crowdfunding_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `goal_amount` decimal(10,2) NOT NULL,
  `current_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('pending','active','funded','not_funded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_crowdfunding_projects_user_id` (`user_id`),
  CONSTRAINT `fk_crowdfunding_projects_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_crowdfunding_rewards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `pledge_amount` decimal(10,2) NOT NULL,
  `limit` int(11) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_crowdfunding_rewards_project_id` (`project_id`),
  CONSTRAINT `fk_crowdfunding_rewards_project` FOREIGN KEY (`project_id`) REFERENCES `sn_crowdfunding_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_crowdfunding_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_crowdfunding_updates_project_id` (`project_id`),
  CONSTRAINT `fk_crowdfunding_updates_project` FOREIGN KEY (`project_id`) REFERENCES `sn_crowdfunding_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_education_courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '课程标题',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '课程描述',
  `instructor_id` int(11) unsigned NOT NULL COMMENT '讲师用户ID',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '课程分类',
  `type` enum('tutorial','masterclass','workshop') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '课程类型：教程/大师课/工作坊',
  `level` enum('beginner','intermediate','advanced') COLLATE utf8mb4_unicode_ci DEFAULT 'beginner' COMMENT '难度等级',
  `price_type` enum('free','paid') COLLATE utf8mb4_unicode_ci DEFAULT 'free' COMMENT '价格类型',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '价格（星夜币）',
  `duration` int(11) DEFAULT NULL COMMENT '课程时长（分钟）',
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '课程缩略图',
  `content` longtext COLLATE utf8mb4_unicode_ci COMMENT '课程内容',
  `video_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '视频URL',
  `materials` json DEFAULT NULL COMMENT '课程材料',
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft' COMMENT '课程状态',
  `is_featured` tinyint(1) DEFAULT '0' COMMENT '是否推荐',
  `view_count` int(11) DEFAULT '0' COMMENT '查看次数',
  `rating` decimal(3,2) DEFAULT '0.00' COMMENT '平均评分',
  `rating_count` int(11) DEFAULT '0' COMMENT '评分人数',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_instructor_id` (`instructor_id`),
  KEY `idx_category` (`category`),
  KEY `idx_type` (`type`),
  KEY `idx_level` (`level`),
  KEY `idx_price_type` (`price_type`),
  KEY `idx_status` (`status`),
  KEY `idx_is_featured` (`is_featured`),
  KEY `idx_rating` (`rating`),
  CONSTRAINT `sn_education_courses_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='教育课程表';

CREATE TABLE `sn_embedding_model_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'platform',
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` text COLLATE utf8mb4_unicode_ci,
  `base_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_tokens` int(11) NOT NULL DEFAULT '8192',
  `dimension` int(11) NOT NULL DEFAULT '1536',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='嵌入式模型高级配置';

CREATE TABLE `sn_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feature_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '功能标识key',
  `feature_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '功能名称',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '功能分类',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '功能描述',
  `require_vip` tinyint(1) DEFAULT '0' COMMENT '是否需要会员：0-否 1-是',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用：0-禁用 1-启用',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_feature_key` (`feature_key`),
  KEY `idx_category` (`category`),
  KEY `idx_require_vip` (`require_vip`),
  KEY `idx_is_enabled` (`is_enabled`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_category_enabled` (`category`,`is_enabled`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='功能权限配置表';

CREATE TABLE `sn_file_hashes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件哈希值',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件路径',
  `file_size` bigint(20) NOT NULL COMMENT '文件大小（字节）',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'MIME类型',
  `upload_user_id` int(11) unsigned DEFAULT NULL COMMENT '上传用户ID',
  `reference_count` int(11) NOT NULL DEFAULT '1' COMMENT '引用计数',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_file_hash` (`file_hash`),
  KEY `idx_file_path` (`file_path`),
  KEY `idx_upload_user_id` (`upload_user_id`),
  KEY `idx_reference_count` (`reference_count`),
  CONSTRAINT `sn_file_hashes_ibfk_1` FOREIGN KEY (`upload_user_id`) REFERENCES `sn_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件哈希表';

CREATE TABLE `sn_infringement_detections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `copyright_id` int(11) NOT NULL COMMENT '版权登记ID',
  `detected_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '检测到的侵权URL',
  `similarity_score` decimal(5,2) NOT NULL COMMENT '相似度分数',
  `detection_method` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '检测方法',
  `status` enum('detected','reviewing','confirmed','false_positive','resolved') COLLATE utf8mb4_unicode_ci DEFAULT 'detected' COMMENT '处理状态',
  `admin_notes` text COLLATE utf8mb4_unicode_ci COMMENT '管理员备注',
  `detected_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL COMMENT '解决时间',
  PRIMARY KEY (`id`),
  KEY `idx_copyright_id` (`copyright_id`),
  KEY `idx_status` (`status`),
  KEY `idx_detected_at` (`detected_at`),
  CONSTRAINT `sn_infringement_detections_ibfk_1` FOREIGN KEY (`copyright_id`) REFERENCES `sn_copyright_registrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='侵权检测记录表';

CREATE TABLE `sn_knowledge_bases` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库集合';

CREATE TABLE `sn_knowledge_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_knowledge_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_knowledge_items_category_id` (`category_id`),
  CONSTRAINT `fk_knowledge_items_category` FOREIGN KEY (`category_id`) REFERENCES `sn_knowledge_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_knowledge_purchases` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) unsigned NOT NULL,
  `knowledge_id` int(11) unsigned NOT NULL,
  `price` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_buyer_knowledge` (`buyer_id`,`knowledge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识付费购买记录';

CREATE TABLE `sn_knowledge_ratings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `knowledge_id` int(11) unsigned NOT NULL,
  `rating` int(11) NOT NULL DEFAULT '5',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_knowledge` (`user_id`,`knowledge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识评分';

CREATE TABLE `sn_knowledge_usage_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `knowledge_id` int(11) unsigned DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库使用记录';

CREATE TABLE `sn_marketing_activities` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='营销配置';

CREATE TABLE `sn_marketing_campaigns` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='营销活动';

CREATE TABLE `sn_membership_level_benefits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `membership_level_id` int(11) NOT NULL,
  `benefit_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_level_benefit` (`membership_level_id`,`benefit_id`),
  KEY `fk_mlb_benefit` (`benefit_id`),
  CONSTRAINT `fk_mlb_benefit` FOREIGN KEY (`benefit_id`) REFERENCES `sn_vip_benefits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mlb_level` FOREIGN KEY (`membership_level_id`) REFERENCES `sn_membership_levels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员等级与权益关联表';

CREATE TABLE `sn_membership_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '等级名称',
  `level` int(11) NOT NULL COMMENT '等级值，用于排序和比较',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '等级描述',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_level` (`level`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员等级定义表';

CREATE TABLE `sn_membership_packages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '套餐名称',
  `membership_level_id` int(11) NOT NULL COMMENT '关联的会员等级ID',
  `type` tinyint(1) NOT NULL COMMENT '会员类型：1-月度 2-年度 3-终身',
  `duration_days` int(11) DEFAULT NULL COMMENT '有效天数（终身会员为NULL）',
  `original_price` decimal(10,2) NOT NULL COMMENT '原价',
  `discount_price` decimal(10,2) DEFAULT NULL COMMENT '优惠价',
  `discount_rate` decimal(3,2) DEFAULT NULL COMMENT '折扣率',
  `gift_starry_night_coins` bigint(20) DEFAULT '0' COMMENT '赠送星夜币数',
  `features` json DEFAULT NULL COMMENT '包含的功能权益',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '套餐描述',
  `is_recommended` tinyint(1) DEFAULT '0' COMMENT '是否推荐',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '套餐图标',
  `badge` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '套餐标签',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_enabled` (`is_enabled`),
  KEY `idx_is_recommended` (`is_recommended`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_original_price` (`original_price`),
  KEY `idx_type_enabled_recommended` (`type`,`is_enabled`,`is_recommended`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员套餐配置表';

CREATE TABLE `sn_membership_purchase_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `package_id` int(11) unsigned DEFAULT NULL COMMENT '关联的会员套餐ID',
  `membership_level_id` int(11) NOT NULL COMMENT '关联的会员等级ID',
  `membership_type` tinyint(1) NOT NULL COMMENT '会员类型：1-月度 2-年度 3-终身',
  `membership_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会员名称',
  `original_price` decimal(10,2) NOT NULL COMMENT '原价',
  `actual_price` decimal(10,2) NOT NULL COMMENT '实际支付价格',
  `discount_amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠金额',
  `duration_days` int(11) DEFAULT NULL COMMENT '有效天数（终身会员为NULL）',
  `start_time` datetime NOT NULL COMMENT '会员开始时间',
  `end_time` datetime DEFAULT NULL COMMENT '会员结束时间（终身会员为NULL）',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '支付方式',
  `payment_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '支付状态：pending/paid/failed/refunded',
  `payment_time` datetime DEFAULT NULL COMMENT '支付时间',
  `transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '第三方交易ID',
  `order_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单号',
  `refund_amount` decimal(10,2) DEFAULT '0.00' COMMENT '退款金额',
  `refund_time` datetime DEFAULT NULL COMMENT '退款时间',
  `refund_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '退款原因',
  `auto_renew` tinyint(1) DEFAULT '0' COMMENT '是否自动续费',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户代理',
  `original_vip_expire_at` datetime DEFAULT NULL COMMENT '购买前会员过期时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_package_id` (`package_id`),
  KEY `idx_membership_type` (`membership_type`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_start_time` (`start_time`),
  KEY `idx_end_time` (`end_time`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_type_status` (`user_id`,`membership_type`,`payment_status`),
  CONSTRAINT `fk_membership_package` FOREIGN KEY (`package_id`) REFERENCES `sn_membership_packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_membership_purchase_records_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员购买记录表';

CREATE TABLE `sn_model_prices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `model_name` varchar(100) NOT NULL COMMENT '模型名称',
  `provider` varchar(50) NOT NULL COMMENT '提供商',
  `input_price` decimal(10,6) NOT NULL COMMENT '输入星夜币价格（美元/M 星夜币）',
  `output_price` decimal(10,6) NOT NULL COMMENT '输出星夜币价格（美元/M 星夜币）',
  `chars_per_token` decimal(4,2) DEFAULT '2.50' COMMENT '每星夜币平均字符数',
  `platform_rate` decimal(6,4) DEFAULT '1.5000' COMMENT '平台倍率（成本*倍率=用户价格）',
  `context_window` int(11) DEFAULT NULL COMMENT '上下文窗口',
  `max_output_tokens` int(11) DEFAULT NULL COMMENT '最大输出Token',
  `supports_streaming` tinyint(4) DEFAULT '1',
  `supports_function_calling` tinyint(4) DEFAULT '0',
  `is_enabled` tinyint(4) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_model` (`model_name`,`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sn_music` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通用音乐作品表';

CREATE TABLE `sn_music_comments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `track_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_track` (`track_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='音乐评论';

CREATE TABLE `sn_music_plays` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `track_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_track` (`track_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='音乐播放记录';

CREATE TABLE `sn_music_projects` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='音乐项目';

CREATE TABLE `sn_music_tracks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `artist` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plays` int(11) NOT NULL DEFAULT '0',
  `likes` int(11) NOT NULL DEFAULT '0',
  `file_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='音乐曲目统计表';

CREATE TABLE `sn_notice_bar` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '通知内容',
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '点击通知跳转链接（可选）',
  `priority` int(11) NOT NULL DEFAULT '0' COMMENT '优先级，越大越靠前',
  `display_from` datetime DEFAULT NULL COMMENT '显示开始时间',
  `display_to` datetime DEFAULT NULL COMMENT '显示结束时间',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enabled' COMMENT 'enabled / disabled',
  `lang` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'zh-CN' COMMENT '语言代码',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_notification_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `channel` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'email/sms/system',
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_channel_code` (`channel`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知模板';

CREATE TABLE `sn_novel_chapter_versions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `chapter_id` int(11) unsigned NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chapter` (`chapter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小说章节版本记录';

CREATE TABLE `sn_novel_chapters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `novel_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `word_count` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_novel_chapters_novel_id` (`novel_id`),
  CONSTRAINT `fk_novel_chapters_novel` FOREIGN KEY (`novel_id`) REFERENCES `sn_novels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_novel_character_relationships` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `character_id` int(11) unsigned NOT NULL,
  `related_character_id` int(11) unsigned NOT NULL,
  `relation_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_character` (`character_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色关系表';

CREATE TABLE `sn_novel_characters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `novel_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_novel_characters_novel_id` (`novel_id`),
  CONSTRAINT `fk_novel_characters_novel` FOREIGN KEY (`novel_id`) REFERENCES `sn_novels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_novel_outlines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `novel_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_novel_outlines_novel_id` (`novel_id`),
  CONSTRAINT `fk_novel_outlines_novel` FOREIGN KEY (`novel_id`) REFERENCES `sn_novels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_novels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_novels_user_id` (`user_id`),
  CONSTRAINT `fk_novels_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `product_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'membership/coins/other',
  `product_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CNY',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending/completed/refunded/failed',
  `payment_gateway` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_channel` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `refund_reason` text COLLATE utf8mb4_unicode_ci,
  `refund_operator_id` int(11) unsigned DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_order_no` (`order_no`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status_paid` (`status`,`paid_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='统一订单表';

CREATE TABLE `sn_promotion_links` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `target_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clicks` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推广链接';

CREATE TABLE `sn_queue_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT '0',
  `available_at` int(11) NOT NULL,
  `reserved_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `idx_queue_status` (`queue`,`status`),
  KEY `idx_available_at` (`available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='简单队列表';

CREATE TABLE `sn_ranking_cache` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload_json` longtext COLLATE utf8mb4_unicode_ci,
  `generated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='排行榜缓存';

CREATE TABLE `sn_recharge_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '套餐名称',
  `tokens` bigint(20) NOT NULL COMMENT '包含星夜币数量',
  `price` decimal(10,2) NOT NULL COMMENT '原价（元）',
  `vip_price` decimal(10,2) DEFAULT NULL COMMENT '会员价（元）',
  `discount_rate` decimal(3,2) DEFAULT NULL COMMENT '会员折扣率（0.8表示8折）',
  `bonus_tokens` bigint(20) DEFAULT '0' COMMENT '额外赠送Token',
  `is_hot` tinyint(1) DEFAULT '0' COMMENT '是否热门推荐',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '套餐描述',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '套餐图标',
  `badge` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '套餐标签',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_is_enabled` (`is_enabled`),
  KEY `idx_is_hot` (`is_hot`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_price` (`price`),
  KEY `idx_enabled_hot` (`is_enabled`,`is_hot`)
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值套餐配置表';

CREATE TABLE `sn_recharge_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `package_id` int(11) NOT NULL COMMENT '套餐ID',
  `order_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单号',
  `tokens` bigint(20) NOT NULL COMMENT '购买星夜币数量',
  `bonus_tokens` bigint(20) DEFAULT '0' COMMENT '赠送星夜币数量',
  `total_tokens` bigint(20) NOT NULL COMMENT '实际到账星夜币数量',
  `original_price` decimal(10,2) NOT NULL COMMENT '原价',
  `actual_price` decimal(10,2) NOT NULL COMMENT '实际支付价格',
  `discount_amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠金额',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '支付方式',
  `payment_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT '支付状态：pending/paid/failed/refunded',
  `payment_time` datetime DEFAULT NULL COMMENT '支付时间',
  `transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '第三方交易ID',
  `refund_amount` decimal(10,2) DEFAULT '0.00' COMMENT '退款金额',
  `refund_time` datetime DEFAULT NULL COMMENT '退款时间',
  `refund_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '退款原因',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户代理',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_package_id` (`package_id`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_payment_time` (`payment_time`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_status_time` (`user_id`,`payment_status`,`payment_time`),
  CONSTRAINT `fk_recharge_records_package` FOREIGN KEY (`package_id`) REFERENCES `sn_recharge_packages` (`id`),
  CONSTRAINT `fk_recharge_records_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值记录表';

CREATE TABLE `sn_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `item_type` enum('novel','anime','music','tool','agent','creator') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '推荐项目类型',
  `item_id` int(11) NOT NULL COMMENT '推荐项目ID',
  `score` decimal(5,2) NOT NULL COMMENT '推荐分数',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '推荐理由',
  `algorithm` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '推荐算法',
  `is_clicked` tinyint(1) DEFAULT '0' COMMENT '是否被点击',
  `is_liked` tinyint(1) DEFAULT '0' COMMENT '是否被喜欢',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `clicked_at` timestamp NULL DEFAULT NULL COMMENT '点击时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_item_type` (`item_type`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_score` (`score`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `sn_recommendations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推荐记录表';

CREATE TABLE `sn_reports` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `params_json` longtext COLLATE utf8mb4_unicode_ci,
  `result_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通用报表占位表';

CREATE TABLE `sn_resource_favorites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `resource_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_id` int(11) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_fav` (`user_id`,`resource_type`,`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='资源收藏';

CREATE TABLE `sn_resource_purchases` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) unsigned NOT NULL,
  `resource_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_id` int(11) unsigned NOT NULL,
  `order_id` int(11) unsigned DEFAULT NULL,
  `price_coin` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_purchase` (`buyer_id`,`resource_type`,`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='资源购买记录';

CREATE TABLE `sn_resource_ratings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `resource_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_id` int(11) unsigned NOT NULL,
  `rating` int(11) NOT NULL DEFAULT '5',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_rating` (`user_id`,`resource_type`,`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='资源评分';

CREATE TABLE `sn_resource_shares` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `resource_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_id` int(11) unsigned NOT NULL,
  `channel` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='资源分享记录';

CREATE TABLE `sn_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '设置键',
  `value` longtext COLLATE utf8mb4_unicode_ci COMMENT '设置值',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '设置名称',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '设置描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表';

CREATE TABLE `sn_site_messages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='站内信';

CREATE TABLE `sn_starry_night_engine_permissions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `engine_version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `membership_level_id` int(11) unsigned DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `custom_config` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_version_level` (`engine_version`,`membership_level_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='星夜创作引擎权限配置';

CREATE TABLE `sn_storage_cleanup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cleanup_type` enum('temp_files','expired_drafts','old_logs','abandoned_resources') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '清理类型',
  `files_deleted` int(11) NOT NULL DEFAULT '0' COMMENT '删除文件数量',
  `space_freed` bigint(20) NOT NULL DEFAULT '0' COMMENT '释放空间（字节）',
  `execution_time` decimal(10,3) NOT NULL COMMENT '执行时间（秒）',
  `details` json DEFAULT NULL COMMENT '清理详情',
  `executed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cleanup_type` (`cleanup_type`),
  KEY `idx_executed_at` (`executed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='存储清理日志表';

CREATE TABLE `sn_storage_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `storage_type` enum('local','oss','s3') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local' COMMENT '存储类型',
  `config_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置名称',
  `config_value` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置值（JSON格式）',
  `is_active` tinyint(1) DEFAULT '0' COMMENT '是否激活',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_storage_type` (`storage_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='存储配置表';

CREATE TABLE `sn_system_alerts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统告警表';

CREATE TABLE `sn_system_configs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL COMMENT '配置项键名',
  `value` text COMMENT '配置项值',
  `description` varchar(255) DEFAULT NULL COMMENT '配置项描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sn_token_consumption_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `tokens` bigint(20) NOT NULL COMMENT '消费星夜币数量',
  `balance_before` bigint(20) NOT NULL COMMENT '消费前余额',
  `balance_after` bigint(20) NOT NULL COMMENT '消费后余额',
  `consumption_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '消费类型：ai_generation/file_upload/storage_premium/feature_unlock',
  `related_id` int(11) DEFAULT NULL COMMENT '关联ID（如作品ID、文件ID等）',
  `related_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '关联类型',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '消费描述',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户代理',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_consumption_type` (`consumption_type`),
  KEY `idx_related_id_type` (`related_id`,`related_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_type_time` (`user_id`,`consumption_type`,`created_at`),
  CONSTRAINT `fk_token_consumption_records_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='星夜币消费记录表';

CREATE TABLE `sn_usage_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `token_id` int(10) unsigned DEFAULT NULL COMMENT 'API令牌ID（如果通过API调用）',
  `channel_id` int(10) unsigned NOT NULL COMMENT '调用渠道ID',
  `model_id` int(10) unsigned NOT NULL COMMENT '调用模型ID',
  `model_name` varchar(100) NOT NULL COMMENT '模型名称',
  `request_id` varchar(64) NOT NULL COMMENT '请求唯一ID',
  `prompt_starry_night_coins` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '输入星夜币数',
  `completion_starry_night_coins` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '输出星夜币数',
  `total_starry_night_coins` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总星夜币数',
  `total_chars` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总字符数（平台计费单位）',
  `cost_usd` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '实际成本（美元）',
  `revenue_usd` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '平台收益（美元）',
  `duration_ms` int(10) unsigned DEFAULT NULL COMMENT '请求耗时（毫秒）',
  `status` tinyint(4) DEFAULT '1' COMMENT '调用状态：1-成功 0-失败',
  `error_code` varchar(50) DEFAULT NULL COMMENT '错误码',
  `error_message` text COMMENT '错误信息',
  `request_ip` varchar(45) DEFAULT NULL COMMENT '请求IP',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_id` (`request_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_token_id` (`token_id`),
  KEY `idx_channel_id` (`channel_id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sn_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='兼容旧代码用的 user 表占位（实际用户使用 users 表）';

CREATE TABLE `sn_user_announcement_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `announcement_id` int(11) NOT NULL COMMENT '公告ID',
  `read_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_announcement` (`user_id`,`announcement_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_announcement_id` (`announcement_id`),
  KEY `idx_read_at` (`read_at`),
  CONSTRAINT `sn_user_announcement_reads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_user_announcement_reads_ibfk_2` FOREIGN KEY (`announcement_id`) REFERENCES `sn_announcements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户公告阅读记录表';

CREATE TABLE `sn_user_consistency_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户一致性检查配置';

CREATE TABLE `sn_user_custom_models` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '所属用户ID',
  `channel_id` int(10) unsigned NOT NULL COMMENT '关联的渠道ID',
  `api_key` varchar(500) NOT NULL COMMENT '用户提供的API密钥（加密存储）',
  `base_url` varchar(255) DEFAULT NULL COMMENT '自定义API基础URL',
  `config` json DEFAULT NULL COMMENT '额外配置参数',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用 0-禁用 2-审核中 3-审核失败',
  `audit_message` text COMMENT '审核信息',
  `is_enabled_by_admin` tinyint(4) DEFAULT '1' COMMENT '管理员是否启用此自定义模型',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_channel` (`user_id`,`channel_id`),
  KEY `channel_id` (`channel_id`),
  CONSTRAINT `sn_user_custom_models_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sn_user_custom_models_ibfk_2` FOREIGN KEY (`channel_id`) REFERENCES `sn_channels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sn_user_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '反馈类型：suggestion/bug_report/other',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '反馈标题',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '反馈内容',
  `attachments` json DEFAULT NULL COMMENT '附件URL列表',
  `status` tinyint(1) DEFAULT '1' COMMENT '处理状态：1-待处理 2-处理中 3-已解决 4-已关闭',
  `admin_reply` text COLLATE utf8mb4_unicode_ci COMMENT '管理员回复',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `sn_user_feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户反馈表';

CREATE TABLE `sn_user_invitations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `inviter_id` int(11) unsigned NOT NULL,
  `invitee_id` int(11) unsigned DEFAULT NULL,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邀请记录';

CREATE TABLE `sn_user_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `max_novels` int(11) DEFAULT '5' COMMENT '最大作品数（-1表示无限）',
  `max_chapters_per_novel` int(11) DEFAULT '100' COMMENT '每部作品最大章节数',
  `max_prompts` int(11) DEFAULT '20' COMMENT '最大自定义提示词数',
  `max_agents` int(11) DEFAULT '5' COMMENT '最大智能体数',
  `max_workflows` int(11) DEFAULT '3' COMMENT '最大工作流数',
  `max_folders` int(11) DEFAULT '10' COMMENT '最大文件夹数',
  `daily_word_limit` int(11) DEFAULT '10000' COMMENT '每日字数限制',
  `monthly_word_limit` int(11) DEFAULT '300000' COMMENT '每月字数限制',
  `max_ai_generations_per_day` int(11) DEFAULT '50' COMMENT '每日AI生成次数限制',
  `max_file_upload_size` int(11) DEFAULT '10' COMMENT '最大文件上传大小（MB）',
  `max_storage_space` int(11) DEFAULT '100' COMMENT '最大存储空间（MB）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_id` (`user_id`),
  KEY `idx_max_novels` (`max_novels`),
  KEY `idx_daily_word_limit` (`daily_word_limit`),
  CONSTRAINT `fk_user_limits_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户限制配置表';

CREATE TABLE `sn_user_memberships` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `level_id` int(11) unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户当前会员关系（用于聚合查询）';

CREATE TABLE `sn_user_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `category` enum('content','tools','creators') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '偏好类别',
  `preference_data` json NOT NULL COMMENT '偏好数据',
  `weight` decimal(3,2) DEFAULT '1.00' COMMENT '权重',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_category` (`user_id`,`category`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category` (`category`),
  CONSTRAINT `sn_user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户偏好表';

CREATE TABLE `sn_user_profiles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `real_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_profiles_user_id` (`user_id`),
  CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_user_starry_night_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户星夜引擎个性化配置';

CREATE TABLE `sn_user_storage_quotas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `membership_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会员等级',
  `total_quota` bigint(20) NOT NULL DEFAULT '1073741824' COMMENT '总配额（字节）',
  `used_space` bigint(20) NOT NULL DEFAULT '0' COMMENT '已使用空间（字节）',
  `last_calculated_at` timestamp NULL DEFAULT NULL COMMENT '最后计算时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`),
  KEY `idx_membership_level` (`membership_level`),
  KEY `idx_used_space` (`used_space`),
  CONSTRAINT `sn_user_storage_quotas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户存储配额表';

CREATE TABLE `sn_user_token_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `balance` bigint(20) DEFAULT '0' COMMENT '当前星夜币余额',
  `total_recharged` bigint(20) DEFAULT '0' COMMENT '累计充值星夜币',
  `total_consumed` bigint(20) DEFAULT '0' COMMENT '累计消费星夜币',
  `total_bonus` bigint(20) DEFAULT '0' COMMENT '累计获得赠送星夜币',
  `last_recharge_time` datetime DEFAULT NULL COMMENT '最后充值时间',
  `last_consumption_time` datetime DEFAULT NULL COMMENT '最后消费时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_id` (`user_id`),
  KEY `idx_balance` (`balance`),
  KEY `idx_last_recharge_time` (`last_recharge_time`),
  KEY `idx_last_consumption_time` (`last_consumption_time`),
  CONSTRAINT `fk_user_token_balance_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户星夜币余额表';

CREATE TABLE `sn_user_wallets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_wallets_user_id` (`user_id`),
  CONSTRAINT `fk_user_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nickname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '手机号',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` datetime DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `vip_type` tinyint(1) DEFAULT '0' COMMENT '会员类型：0-普通用户 1-月度会员 2-年度会员 3-终身会员',
  `vip_expire_at` datetime DEFAULT NULL COMMENT '会员过期时间（终身会员为NULL）',
  `vip_start_at` datetime DEFAULT NULL COMMENT '会员开始时间',
  `auto_renew` tinyint(1) DEFAULT '0' COMMENT '是否自动续费',
  `membership_source` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '会员来源：purchase/purchase_renew/gift/admin',
  `membership_level_id` int(11) DEFAULT NULL COMMENT '会员等级ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `idx_users_phone` (`phone`),
  KEY `idx_vip_type` (`vip_type`),
  KEY `idx_vip_expire_at` (`vip_expire_at`),
  KEY `idx_auto_renew` (`auto_renew`),
  KEY `idx_membership_source` (`membership_source`),
  KEY `idx_membership_level_id` (`membership_level_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sn_vector_db_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_json` longtext COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='向量数据库配置';

CREATE TABLE `sn_vector_db_usage_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `config_id` int(11) unsigned DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latency_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='向量数据库使用记录';

CREATE TABLE `sn_vip_benefits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `benefit_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '权益标识',
  `benefit_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '权益名称',
  `benefit_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型：discount/bonus/feature',
  `value` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '权益值',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '权益描述',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_benefit_key` (`benefit_key`),
  KEY `idx_benefit_type` (`benefit_type`),
  KEY `idx_is_enabled` (`is_enabled`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=182 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员权益配置表';

CREATE TABLE `sn_vip_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `order_no` varchar(32) NOT NULL,
  `package_id` int(10) unsigned NOT NULL,
  `duration_type` tinyint(4) NOT NULL COMMENT '时长类型：1-月度 2-年度 3-终身',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `status` tinyint(4) DEFAULT '1' COMMENT '订单状态：1-待支付 2-已支付 3-已取消 4-已退款',
  `payment_method` varchar(30) DEFAULT NULL COMMENT '支付方式',
  `trade_no` varchar(64) DEFAULT NULL COMMENT '第三方交易号',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT '支付时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vip_orders_order_no` (`order_no`),
  KEY `fk_vip_orders_user` (`user_id`),
  KEY `fk_vip_orders_package` (`package_id`),
  CONSTRAINT `fk_vip_orders_package` FOREIGN KEY (`package_id`) REFERENCES `sn_membership_packages` (`id`),
  CONSTRAINT `fk_vip_orders_user` FOREIGN KEY (`user_id`) REFERENCES `sn_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sn_work_favorites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `work_id` int(11) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_work_fav` (`user_id`,`work_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='作品收藏';

CREATE TABLE `sn_work_ratings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `work_id` int(11) unsigned NOT NULL,
  `rating` int(11) NOT NULL DEFAULT '5',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_work_rating` (`user_id`,`work_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='作品评分';

CREATE TABLE `sn_work_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_work` (`work_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='作品浏览记录';

