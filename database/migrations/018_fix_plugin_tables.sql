-- =========================
-- 修复插件相关表结构
-- =========================

-- 修复 admin_plugins 表结构，添加缺失的字段
ALTER TABLE `__PREFIX__admin_plugins` 
ADD COLUMN IF NOT EXISTS `name` varchar(100) NOT NULL DEFAULT '',
ADD COLUMN IF NOT EXISTS `version` varchar(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `type` varchar(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `category` varchar(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `description` text,
ADD COLUMN IF NOT EXISTS `author` varchar(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `website` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `namespace` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `main_class` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `dependencies_json` text,
ADD COLUMN IF NOT EXISTS `requirements_json` text,
ADD COLUMN IF NOT EXISTS `install_sql_path` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `uninstall_sql_path` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `frontend_entry` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `admin_entry` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `installed_at` datetime DEFAULT NULL;

-- 修改 status 字段类型以支持更多状态
ALTER TABLE `__PREFIX__admin_plugins` 
MODIFY COLUMN `status` varchar(20) NOT NULL DEFAULT 'uninstalled';

-- 更新现有 SMTP 插件的完整信息
UPDATE `__PREFIX__admin_plugins` 
SET 
    `name` = 'SMTP 邮箱服务',
    `version` = '1.0.0',
    `type` = 'email',
    `category` = 'smtp',
    `description` = '基于 SMTP 的系统邮件发送服务，使用 PHPMailer 实现，支持 SSL/TLS。',
    `author` = '星夜幻梦',
    `namespace` = 'plugins\\email\\smtp_service',
    `main_class` = 'Plugin',
    `installed_at` = NOW()
WHERE `plugin_id` = 'email/smtp_service';

-- 确保 notification_templates 表存在且结构正确
CREATE TABLE IF NOT EXISTS `__PREFIX__notification_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `channel` varchar(50) NOT NULL COMMENT 'email/sms/system',
  `code` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_channel_code` (`channel`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知模板';

-- 创建插件配置表（如果不存在）
CREATE TABLE IF NOT EXISTS `__PREFIX__admin_plugin_configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` varchar(100) NOT NULL,
  `config_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_plugin_id` (`plugin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件额外配置（保留扩展用）';

-- 插入或更新邮件模板数据（确保数据完整性）
INSERT IGNORE INTO `__PREFIX__notification_templates` (`channel`, `code`, `title`, `content`, `created_at`) VALUES
('email', 'register_verify_email', '注册验证码', 'register_verify_email.html', NOW()),
('email', 'reset_password_email', '密码重置验证码', 'reset_password_email.html', NOW()),
('email', 'welcome_email', '欢迎邮件', 'welcome_email.html', NOW()),
('email', 'order_confirmation', '订单确认', 'order_confirmation.html', NOW()),
('email', 'payment_success', '支付成功', 'payment_success.html', NOW()),
('email', 'subscription_expired', '订阅到期提醒', 'subscription_expired.html', NOW()),
('email', 'account_suspended', '账户暂停通知', 'account_suspended.html', NOW()),
('email', 'system_maintenance', '系统维护通知', 'system_maintenance.html', NOW());

INSERT IGNORE INTO `__PREFIX__notification_templates` (`channel`, `code`, `title`, `content`, `created_at`) VALUES
('sms', 'register_verify_sms', '注册验证码', '您的验证码是：{{code}}，{{minutes}}分钟内有效。', NOW()),
('sms', 'reset_password_sms', '密码重置验证码', '您的密码重置验证码是：{{code}}，{{minutes}}分钟内有效。', NOW()),
('sms', 'login_verify', '登录验证码', '您的登录验证码是：{{code}}，{{minutes}}分钟内有效。', NOW()),
('sms', 'order_notice', '订单通知', '您有新的订单：{{order_id}}，金额：{{amount}}元。', NOW()),
('sms', 'payment_notice', '支付通知', '您的订单{{order_id}}已支付成功，金额：{{amount}}元。', NOW());

INSERT IGNORE INTO `__PREFIX__notification_templates` (`channel`, `code`, `title`, `content`, `created_at`) VALUES
('system', 'new_message', '新消息通知', '您有新的消息：{{message_title}}', NOW()),
('system', 'system_update', '系统更新通知', '系统已更新到{{version}}版本，新增功能：{{features}}', NOW()),
('system', 'security_alert', '安全提醒', '检测到您的账户有异常登录，地点：{{location}}，时间：{{time}}', NOW()),
('system', 'subscription_renewal', '订阅续费提醒', '您的{{plan_name}}订阅即将在{{days}}天后到期，请及时续费。', NOW());
