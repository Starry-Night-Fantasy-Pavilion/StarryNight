-- 知我云聚合登录：https://u.zevost.com/doc.php
-- 回调请在知我云平台登记：{站点公网根}/api/auth/oauth/zevost/callback

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.enabled', 'false', 'boolean', '知我云聚合登录', 'oauth', '启用知我云聚合 OAuth（与各分项可同时配置）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.enabled');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.app-id', '', 'string', '知我云 AppID', 'oauth', '聚合后台申请的 appid', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.app-id');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.app-key', '', 'string', '知我云 AppKey', 'oauth', '聚合后台申请的 appkey，勿泄露', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.app-key');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.qq.enabled', 'false', 'boolean', '聚合·QQ', 'oauth', '知我云 type=qq', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.qq.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.wx.enabled', 'false', 'boolean', '聚合·微信', 'oauth', '知我云 type=wx', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.wx.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.alipay.enabled', 'false', 'boolean', '聚合·支付宝', 'oauth', '知我云 type=alipay', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.alipay.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.sina.enabled', 'false', 'boolean', '聚合·微博', 'oauth', '知我云 type=sina', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.sina.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.baidu.enabled', 'false', 'boolean', '聚合·百度', 'oauth', '知我云 type=baidu', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.baidu.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.douyin.enabled', 'false', 'boolean', '聚合·抖音', 'oauth', '知我云 type=douyin', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.douyin.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.huawei.enabled', 'false', 'boolean', '聚合·华为', 'oauth', '知我云 type=huawei', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.huawei.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.xiaomi.enabled', 'false', 'boolean', '聚合·小米', 'oauth', '知我云 type=xiaomi', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.xiaomi.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.google.enabled', 'false', 'boolean', '聚合·Google', 'oauth', '知我云 type=google', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.google.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.microsoft.enabled', 'false', 'boolean', '聚合·微软', 'oauth', '知我云 type=microsoft', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.microsoft.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.twitter.enabled', 'false', 'boolean', '聚合·Twitter', 'oauth', '知我云 type=twitter', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.twitter.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.dingtalk.enabled', 'false', 'boolean', '聚合·钉钉', 'oauth', '知我云 type=dingtalk', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.dingtalk.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.gitee.enabled', 'false', 'boolean', '聚合·Gitee', 'oauth', '知我云 type=gitee', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.gitee.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.github.enabled', 'false', 'boolean', '聚合·GitHub', 'oauth', '知我云 type=github', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.github.enabled');
