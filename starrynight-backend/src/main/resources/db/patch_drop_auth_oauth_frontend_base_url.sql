-- 已移除 auth.oauth.frontend-base-url；用户端跳转与 OAuth 回跳统一使用 auth.oauth.public-base-url
DELETE FROM system_config WHERE config_key = 'auth.oauth.frontend-base-url';
