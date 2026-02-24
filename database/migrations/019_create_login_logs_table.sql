-- 创建用户登录日志表
-- 用于记录用户登录历史，包括IP地址、User Agent等信息

CREATE TABLE IF NOT EXISTS `__PREFIX__login_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `ip` varchar(45) DEFAULT NULL COMMENT '登录IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理字符串',
  `login_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '登录时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_login_at` (`login_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户登录日志表';
