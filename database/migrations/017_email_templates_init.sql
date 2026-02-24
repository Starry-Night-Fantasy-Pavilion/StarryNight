-- =========================
-- 邮件模板初始化数据
-- =========================

-- 插入默认邮件模板
INSERT INTO `__PREFIX__notification_templates` (`channel`, `code`, `title`, `content`, `created_at`) VALUES
('email', 'register_verify_email', '注册验证码', 'register_verify_email.html', NOW()),
('email', 'reset_password_email', '密码重置验证码', 'reset_password_email.html', NOW()),
('email', 'welcome_email', '欢迎邮件', 'welcome_email.html', NOW()),
('email', 'order_confirmation', '订单确认', 'order_confirmation.html', NOW()),
('email', 'payment_success', '支付成功', 'payment_success.html', NOW()),
('email', 'subscription_expired', '订阅到期提醒', 'subscription_expired.html', NOW()),
('email', 'account_suspended', '账户暂停通知', 'account_suspended.html', NOW()),
('email', 'system_maintenance', '系统维护通知', 'system_maintenance.html', NOW());

-- 插入默认短信模板
INSERT INTO `__PREFIX__notification_templates` (`channel`, `code`, `title`, `content`, `created_at`) VALUES
('sms', 'register_verify_sms', '注册验证码', '您的验证码是：{{code}}，{{minutes}}分钟内有效。', NOW()),
('sms', 'reset_password_sms', '密码重置验证码', '您的密码重置验证码是：{{code}}，{{minutes}}分钟内有效。', NOW()),
('sms', 'login_verify', '登录验证码', '您的登录验证码是：{{code}}，{{minutes}}分钟内有效。', NOW()),
('sms', 'order_notice', '订单通知', '您有新的订单：{{order_id}}，金额：{{amount}}元。', NOW()),
('sms', 'payment_notice', '支付通知', '您的订单{{order_id}}已支付成功，金额：{{amount}}元。', NOW());

-- 插入系统通知模板
INSERT INTO `__PREFIX__notification_templates` (`channel`, `code`, `title`, `content`, `created_at`) VALUES
('system', 'new_message', '新消息通知', '您有新的消息：{{message_title}}', NOW()),
('system', 'system_update', '系统更新通知', '系统已更新到{{version}}版本，新增功能：{{features}}', NOW()),
('system', 'security_alert', '安全提醒', '检测到您的账户有异常登录，地点：{{location}}，时间：{{time}}', NOW()),
('system', 'subscription_renewal', '订阅续费提醒', '您的{{plan_name}}订阅即将在{{days}}天后到期，请及时续费。', NOW());

-- 创建邮件模板文件目录和默认模板
-- 注意：这部分需要在应用部署时执行，创建对应的HTML模板文件

-- 注册验证邮件模板
-- 文件路径: public/static/errors/html/Email/register_verify_email.html
-- 内容示例:
-- <!DOCTYPE html>
-- <html>
-- <head>
--     <meta charset="utf-8">
--     <title>注册验证码</title>
-- </head>
-- <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
--     <div style="background-color: #f8f9fa; padding: 30px; border-radius: 10px;">
--         <h2 style="color: #333; text-align: center;">欢迎注册{{site_name}}</h2>
--         <p style="font-size: 16px; color: #666;">您的验证码是：</p>
--         <div style="background-color: #007bff; color: white; font-size: 24px; font-weight: bold; text-align: center; padding: 15px; border-radius: 5px; margin: 20px 0;">
--             {{code}}
--         </div>
--         <p style="font-size: 14px; color: #666;">验证码在{{minutes}}分钟内有效，请及时使用。</p>
--         <p style="font-size: 12px; color: #999; text-align: center; margin-top: 30px;">
--             此邮件为系统自动发送，请勿回复。如非本人操作，请忽略此邮件。
--         </p>
--     </div>
-- </body>
-- </html>

-- 密码重置邮件模板
-- 文件路径: public/static/errors/html/Email/reset_password_email.html
-- 内容结构类似，主题为密码重置
