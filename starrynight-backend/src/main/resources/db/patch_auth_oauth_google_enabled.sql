-- 第三方登录：谷歌开关（与 seed / patch_system_config_mail_sms_oauth 一致，已有库可执行一次）

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.enabled', 'false', 'boolean', '谷歌登录', 'oauth', '启用 Google OAuth（预留）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.enabled');
