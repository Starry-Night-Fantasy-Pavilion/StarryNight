CREATE TABLE IF NOT EXISTS `__PREFIX__settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL COMMENT '设置键',
  `value` longtext COMMENT '设置值',
  `name` varchar(100) DEFAULT NULL COMMENT '设置名称',
  `description` text DEFAULT NULL COMMENT '设置描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表';

INSERT IGNORE INTO `__PREFIX__settings` (`key`, `value`, `name`, `description`) VALUES
('ai_channel_openai', 'enabled', 'OpenAI Channel Status', 'Status of the OpenAI API channel'),
('ai_channel_gemini', 'disabled', 'Gemini Channel Status', 'Status of the Google Gemini API channel'),
('site_name', '星夜阁', 'Site Name', 'The name of the website'),
('site_description', '一个AI驱动的创作平台', 'Site Description', 'A short description of the website');