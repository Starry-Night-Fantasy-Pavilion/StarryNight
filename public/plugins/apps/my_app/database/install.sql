CREATE TABLE IF NOT EXISTS `__PREFIX__books` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `description` text,
  `status` varchar(20) NOT NULL DEFAULT 'published',
  `views` bigint(20) unsigned NOT NULL DEFAULT 0,
  `likes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `source_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_created_at` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__book_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `book_source_name` VARCHAR(255) NOT NULL COMMENT '书源名称',
  `book_source_url` VARCHAR(500) NOT NULL COMMENT '书源地址',
  `book_source_group` VARCHAR(255) NULL DEFAULT NULL COMMENT '书源分组',
  `book_source_type` INT NOT NULL DEFAULT 0 COMMENT '书源类型，0 文本，1 音频',
  `book_url_pattern` VARCHAR(500) NULL DEFAULT NULL COMMENT '详情页URL正则',
  `custom_order` INT NOT NULL DEFAULT 0 COMMENT '手动排序编号',
  `enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用',
  `enabled_explore` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '启用发现',
  `header` TEXT NULL DEFAULT NULL COMMENT '请求头',
  `login_url` VARCHAR(500) NULL DEFAULT NULL COMMENT '登录地址',
  `last_update_time` INT NOT NULL DEFAULT 0 COMMENT '最后更新时间',
  `weight` INT NOT NULL DEFAULT 0 COMMENT '权重',
  `explore_url` VARCHAR(500) NULL DEFAULT NULL COMMENT '发现URL',
  `rule_explore` TEXT NULL DEFAULT NULL COMMENT '发现规则',
  `search_url` VARCHAR(500) NULL DEFAULT NULL COMMENT '搜索URL',
  `rule_search` TEXT NULL DEFAULT NULL COMMENT '搜索规则',
  `rule_book_info` TEXT NULL DEFAULT NULL COMMENT '书籍信息页规则',
  `rule_toc` TEXT NULL DEFAULT NULL COMMENT '目录页规则',
  `rule_content` TEXT NULL DEFAULT NULL COMMENT '正文页规则',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__chapters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext,
  `chapter_number` int(10) unsigned DEFAULT NULL,
  `source_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_book_id` (`book_id`),
  KEY `idx_book_id_chapter_number` (`book_id`, `chapter_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `content` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_book_id_created_at` (`book_id`, `created_at`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 阅读进度表
CREATE TABLE IF NOT EXISTS `__PREFIX__reading_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `book_id` bigint(20) unsigned NOT NULL,
  `chapter_id` bigint(20) unsigned DEFAULT NULL,
  `progress` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '阅读进度百分比',
  `last_read_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_book` (`user_id`, `book_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_book_id` (`book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 书签表
CREATE TABLE IF NOT EXISTS `__PREFIX__bookmarks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `book_id` bigint(20) unsigned NOT NULL,
  `chapter_id` bigint(20) unsigned NOT NULL,
  `position` int(10) unsigned DEFAULT NULL COMMENT '书签在章节中的位置（字符位置）',
  `note` text COMMENT '书签备注',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_book` (`user_id`, `book_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_book_id` (`book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 用户行为记录表（用于推荐系统）
CREATE TABLE IF NOT EXISTS `__PREFIX__user_behaviors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `book_id` bigint(20) unsigned NOT NULL,
  `action_type` varchar(20) NOT NULL COMMENT '行为类型：view, like, bookmark, download, share',
  `duration` int(10) unsigned DEFAULT NULL COMMENT '阅读时长（秒）',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_book_id` (`book_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 推荐记录表
CREATE TABLE IF NOT EXISTS `__PREFIX__recommendations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `book_id` bigint(20) unsigned NOT NULL,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '推荐分数',
  `reason` varchar(255) DEFAULT NULL COMMENT '推荐理由',
  `algorithm` varchar(50) NOT NULL DEFAULT 'collaborative' COMMENT '推荐算法',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '推荐过期时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_book` (`user_id`, `book_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_score` (`score`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 书籍相似度表（用于推荐系统）
CREATE TABLE IF NOT EXISTS `__PREFIX__book_similarity` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `book_id_1` bigint(20) unsigned NOT NULL,
  `book_id_2` bigint(20) unsigned NOT NULL,
  `similarity` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT '相似度分数',
  `algorithm` varchar(50) NOT NULL DEFAULT 'content' COMMENT '相似度算法',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_book_pair` (`book_id_1`, `book_id_2`),
  KEY `idx_book_id_1` (`book_id_1`),
  KEY `idx_book_id_2` (`book_id_2`),
  KEY `idx_similarity` (`similarity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

