-- 各第三方 OAuth 开放平台接口根（可选），留空走官方；便于代理/多端口/内网调试
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.linuxdo.platform-base-url', '', 'string', 'auth.oauth.linuxdo.platform-base-url', 'oauth', 'LINUX DO Connect OAuth 2.0 base. Default https://connect.linux.do. Paths: /oauth2/authorize, /oauth2/token, /api/user', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.linuxdo.platform-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.github.oauth-web-base-url', '', 'string', 'auth.oauth.github.oauth-web-base-url', 'oauth', 'github.com OAuth web: /login/oauth/authorize, /login/oauth/access_token. Default https://github.com', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.github.oauth-web-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.github.rest-api-base-url', '', 'string', 'auth.oauth.github.rest-api-base-url', 'oauth', 'GitHub REST API: /user, /user/emails. Default https://api.github.com', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.github.rest-api-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.accounts-base-url', '', 'string', 'auth.oauth.google.accounts-base-url', 'oauth', 'Google OAuth 2.0 authorization host. Default https://accounts.google.com. Path: /o/oauth2/v2/auth', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.accounts-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.token-base-url', '', 'string', 'auth.oauth.google.token-base-url', 'oauth', 'Google token endpoint host. Default https://oauth2.googleapis.com. Path: /token', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.token-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.userinfo-base-url', '', 'string', 'auth.oauth.google.userinfo-base-url', 'oauth', 'Google OIDC userinfo host. Default https://openidconnect.googleapis.com. Path: /v1/userinfo', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.userinfo-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.wechat.open-platform-base-url', '', 'string', 'auth.oauth.wechat.open-platform-base-url', 'oauth', 'WeChat Open Platform (website QR): open.weixin.qq.com. Path: /connect/qrconnect', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.wechat.open-platform-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.wechat.sns-api-base-url', '', 'string', 'auth.oauth.wechat.sns-api-base-url', 'oauth', 'WeChat SNS API host api.weixin.qq.com. Paths: /sns/oauth2/access_token, /sns/userinfo', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.wechat.sns-api-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.qq.open-api-base-url', '', 'string', 'auth.oauth.qq.open-api-base-url', 'oauth', 'QQ Connect graph host. Default https://graph.qq.com. OAuth2.0 /oauth2.0/* and /user/get_user_info', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.qq.open-api-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.platform-base-url', '', 'string', 'auth.oauth.zevost.platform-base-url', 'oauth', 'Zevost base URL (doc connect.php). Default https://u.zevost.com. Appended path: /connect.php?act=login|callback', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.platform-base-url');
