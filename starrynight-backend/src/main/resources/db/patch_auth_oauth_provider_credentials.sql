-- 微信 / QQ / GitHub / Google OAuth 凭证键（与 seed 一致，已有库执行一次）

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.wechat.client-id', '', 'string', '微信开放平台 AppID', 'oauth', '网站应用扫码登录；与 client-secret 对应', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.wechat.client-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.wechat.client-secret', '', 'string', '微信开放平台 AppSecret', 'oauth', '勿泄露', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.wechat.client-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.qq.client-id', '', 'string', 'QQ 互联 AppID', 'oauth', 'OAuth2 client_id', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.qq.client-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.qq.client-secret', '', 'string', 'QQ 互联 AppKey', 'oauth', 'OAuth2 client_secret', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.qq.client-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.github.client-id', '', 'string', 'GitHub OAuth Client ID', 'oauth', 'GitHub Developer Settings 创建 OAuth App', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.github.client-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.github.client-secret', '', 'string', 'GitHub OAuth Client Secret', 'oauth', '勿泄露', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.github.client-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.client-id', '', 'string', 'Google OAuth Client ID', 'oauth', 'Google Cloud Console OAuth 2.0 客户端', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.client-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.client-secret', '', 'string', 'Google OAuth Client Secret', 'oauth', '勿泄露', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.client-secret');
