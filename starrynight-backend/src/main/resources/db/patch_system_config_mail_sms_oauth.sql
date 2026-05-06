-- 系统配置页：邮件、短信、第三方登录开关；与 seed.sql 中对应 INSERT 一致，已有库执行一次

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.enabled', 'false', 'boolean', '启用邮件发送', 'mail', '总开关，业务发信前需为 true', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.mail.host', 'localhost', 'string', 'SMTP 主机', 'mail', 'spring.mail.host', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.mail.host');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.mail.port', '587', 'number', 'SMTP 端口', 'mail', 'spring.mail.port', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.mail.port');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.mail.username', '', 'string', 'SMTP 用户名', 'mail', 'spring.mail.username', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.mail.username');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.mail.password', '', 'string', 'SMTP 密码', 'mail', 'spring.mail.password', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.mail.password');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.from', '', 'string', '发件人地址', 'mail', '可空，空则使用 spring.mail.username', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.from');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.from.personal', '', 'string', '发件者显示名', 'mail', '可选；收件箱「发件人」旁展示的中文名，UTF-8', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.from.personal');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.smtp.starttls', 'true', 'boolean', 'SMTP STARTTLS', 'mail', '端口非 465 时一般为 true', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.smtp.starttls');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.smtp.ssl', 'false', 'boolean', 'SMTP SSL（465）', 'mail', 'SSL 直连时 true', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.smtp.ssl');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.enabled', 'false', 'boolean', '启用短信', 'sms', '总开关', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.provider', 'aliyun', 'string', '短信服务商', 'sms', 'aliyun 或 tencent', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.provider');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.access-key-id', '', 'string', '短信 AccessKey ID', 'sms', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.access-key-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.access-key-secret', '', 'string', '短信 AccessKey Secret', 'sms', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.access-key-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.sign-name', '', 'string', '短信签名', 'sms', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.sign-name');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.template.verification', '', 'string', '验证码短信模板编码', 'sms', '阿里云填 SMS_xxx，控制台模板变量名须为 code；腾讯云填数字模板 ID', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.template.verification');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.tencent.sdk-app-id', '', 'string', '腾讯云短信 SdkAppId', 'sms', '短信控制台应用 ID', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.tencent.sdk-app-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.tencent.region', 'ap-guangzhou', 'string', '腾讯云短信地域', 'sms', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.tencent.region');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.wechat.enabled', 'false', 'boolean', '微信登录', 'oauth', '启用微信 OAuth', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.wechat.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.qq.enabled', 'false', 'boolean', 'QQ 登录', 'oauth', '启用 QQ OAuth', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.qq.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.github.enabled', 'false', 'boolean', 'GitHub 登录', 'oauth', '启用 GitHub OAuth', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.github.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.enabled', 'false', 'boolean', '谷歌登录', 'oauth', '启用 Google OAuth（预留）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.enabled');
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
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.linuxdo.enabled', 'false', 'boolean', 'LINUX DO 登录', 'oauth', '启用 LINUX DO Connect 登录', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.linuxdo.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.linuxdo.client-id', '', 'string', 'LINUX DO Client ID', 'oauth', '在 https://connect.linux.do 应用接入中创建', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.linuxdo.client-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.linuxdo.client-secret', '', 'string', 'LINUX DO Client Secret', 'oauth', '勿泄露、勿提交代码库', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.linuxdo.client-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.public-base-url', '', 'string', '站点公网根 URL', 'oauth', '如 https://你的域名 无尾斜杠；OAuth 回调须与 Connect 中 redirect 一致', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.public-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.frontend.api-public-origin', '', 'string', 'portal.frontend.api-public-origin', 'oauth', 'Browser origin for this app /api only (optional). Empty = relative /api. VITE_API_PUBLIC_ORIGIN overrides when set.', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.frontend.api-public-origin');
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
