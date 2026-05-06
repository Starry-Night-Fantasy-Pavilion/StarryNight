-- 邮件：发件者显示名（与 seed / patch_system_config_mail_sms_oauth 同步）

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.from.personal', '', 'string', '发件者显示名', 'mail', '可选；收件箱「发件人」旁展示的中文名，UTF-8', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.from.personal');
