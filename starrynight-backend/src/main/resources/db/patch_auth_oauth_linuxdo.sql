-- LINUX DO Connect OAuth：第三方账号绑定表 + 运营端配置项（与 seed 一致，已有库执行一次即可）

CREATE TABLE IF NOT EXISTS auth_oauth_link (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '主键',
    provider VARCHAR(32) NOT NULL COMMENT '渠道，如 LINUXDO',
    external_id VARCHAR(64) NOT NULL COMMENT '外部用户唯一标识',
    user_id BIGINT NOT NULL COMMENT 'auth_user.id',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '绑定时间',
    UNIQUE KEY uk_oauth_provider_external (provider, external_id),
    INDEX idx_oauth_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='第三方 OAuth 与站内用户绑定';

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
SELECT 'auth.oauth.public-base-url', '', 'string', '站点公网根 URL', 'oauth', '如 https://你的域名 无尾斜杠；用于 OAuth 回调与前端回调地址，与 Connect 中填写的 redirect 一致', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.public-base-url');
