-- 好友系统：好友关系、好友申请、好友私信（最小可用）
-- 说明：本项目迁移文件有两种写法：sn_ 直写 或 __PREFIX__ 占位。
-- 这里使用 __PREFIX__，以兼容不同 DB_PREFIX。

CREATE TABLE IF NOT EXISTS `__PREFIX__friends` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `friend_id` int(11) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_friend_pair` (`user_id`,`friend_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_friend_id` (`friend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='好友关系（有向，双方各一条）';

CREATE TABLE IF NOT EXISTS `__PREFIX__friend_requests` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `requester_id` int(11) unsigned NOT NULL COMMENT '发起者',
  `receiver_id` int(11) unsigned NOT NULL COMMENT '接收者',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending/accepted/rejected/cancelled',
  `message` varchar(255) DEFAULT NULL COMMENT '附言',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_request` (`requester_id`,`receiver_id`),
  KEY `idx_receiver_status` (`receiver_id`,`status`),
  KEY `idx_requester_status` (`requester_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='好友申请';

CREATE TABLE IF NOT EXISTS `__PREFIX__friend_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) unsigned NOT NULL,
  `receiver_id` int(11) unsigned NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_receiver_read` (`receiver_id`,`is_read`),
  KEY `idx_pair_time` (`sender_id`,`receiver_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='好友私信';

