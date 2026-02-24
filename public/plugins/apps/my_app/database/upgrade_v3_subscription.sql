-- 在线书城插件升级SQL - 章节订阅和评论评分功能
-- 版本: 3.0.0
-- 创建时间: 2024年

-- ============================================
-- 1. 书籍订阅表
-- ============================================
CREATE TABLE IF NOT EXISTS `__PREFIX__book_subscriptions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `book_id` int(11) unsigned NOT NULL COMMENT '书籍ID',
  `notify_on_update` tinyint(1) NOT NULL DEFAULT 1 COMMENT '更新时是否通知',
  `last_notified_at` datetime DEFAULT NULL COMMENT '最后通知时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_book` (`user_id`, `book_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_book_id` (`book_id`),
  KEY `idx_notify` (`notify_on_update`, `last_notified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='书籍订阅表';

-- ============================================
-- 2. 订阅通知记录表
-- ============================================
CREATE TABLE IF NOT EXISTS `__PREFIX__subscription_notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `book_id` int(11) unsigned NOT NULL COMMENT '书籍ID',
  `chapter_id` int(11) unsigned NOT NULL COMMENT '章节ID',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已读',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_book_id` (`book_id`),
  KEY `idx_chapter_id` (`chapter_id`),
  KEY `idx_is_read` (`is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订阅通知记录表';

-- ============================================
-- 3. 书籍评论表（增强）
-- ============================================
CREATE TABLE IF NOT EXISTS `__PREFIX__book_comments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `book_id` int(11) unsigned NOT NULL COMMENT '书籍ID',
  `parent_id` int(11) unsigned DEFAULT NULL COMMENT '父评论ID（回复）',
  `content` text NOT NULL COMMENT '评论内容',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT '状态',
  `like_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '点赞数',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `moderated_at` datetime DEFAULT NULL COMMENT '审核时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_book_id` (`book_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='书籍评论表';

-- ============================================
-- 4. 书籍评分表
-- ============================================
CREATE TABLE IF NOT EXISTS `__PREFIX__book_ratings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `book_id` int(11) unsigned NOT NULL COMMENT '书籍ID',
  `rating` tinyint(1) unsigned NOT NULL COMMENT '评分（1-5）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_book` (`user_id`, `book_id`),
  KEY `idx_book_id` (`book_id`),
  KEY `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='书籍评分表';

-- ============================================
-- 5. 更新书籍表，添加评分和评论数字段
-- ============================================
ALTER TABLE `__PREFIX__books` 
ADD COLUMN IF NOT EXISTS `rating` decimal(3,2) DEFAULT 0.00 COMMENT '平均评分',
ADD COLUMN IF NOT EXISTS `rating_count` int(11) unsigned DEFAULT 0 COMMENT '评分人数',
ADD COLUMN IF NOT EXISTS `comment_count` int(11) unsigned DEFAULT 0 COMMENT '评论数';

-- 添加索引
ALTER TABLE `__PREFIX__books` 
ADD INDEX IF NOT EXISTS `idx_rating` (`rating`, `rating_count`),
ADD INDEX IF NOT EXISTS `idx_comment_count` (`comment_count`);

-- ============================================
-- 6. 评论点赞表
-- ============================================
CREATE TABLE IF NOT EXISTS `__PREFIX__comment_likes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `comment_id` int(11) unsigned NOT NULL COMMENT '评论ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_comment` (`user_id`, `comment_id`),
  KEY `idx_comment_id` (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论点赞表';
