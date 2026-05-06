-- 扩展邮件模板：通用验证码、活动通知、营销推广（与 MailTemplateKind 一致；仅 subject，正文为上传的 HTML）

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.template.verification-code.subject', '验证码', 'string', '邮件模板-通用验证码标题', 'mail', '通用验证码邮件标题（正文为上传的 HTML）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.template.verification-code.subject');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.template.activity.subject', '活动通知', 'string', '邮件模板-活动通知标题', 'mail', '活动/公告类邮件标题（正文为上传的 HTML）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.template.activity.subject');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.template.marketing.subject', '邮件通知', 'string', '邮件模板-营销推广标题', 'mail', '营销推广类邮件标题（正文为上传的 HTML）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.template.marketing.subject');
