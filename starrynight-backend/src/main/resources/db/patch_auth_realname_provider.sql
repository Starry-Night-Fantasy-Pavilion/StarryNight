-- 实名核验方式：alipay（支付宝）、ovooa / miaoyuxin（喵雨欣开发平台 HTTP 网关，文档示例：https://www.ovooa.cc/apidata?id=4 / id=5）；basic 已废弃
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.verify_provider', 'alipay', 'string', '实名核验方式', 'auth', 'alipay=支付宝；ovooa 或 miaoyuxin=喵雨欣开发平台（HTTP 模板）；basic 已废弃', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.verify_provider');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.alipay.app-id', '', 'string', '支付宝 AppID', 'auth', '开放平台应用 APPID', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.alipay.app-id');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.alipay.private-key', '', 'string', '支付宝应用私钥 PEM', 'auth', 'RSA2 PKCS8，一行或多行 PEM', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.alipay.private-key');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.alipay.alipay-public-key', '', 'string', '支付宝公钥 PEM', 'auth', '用于验签异步通知', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.alipay.alipay-public-key');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.alipay.gateway', 'https://openapi.alipay.com/gateway.do', 'string', '支付宝网关', 'auth', '沙箱可改为 openapi.alipaydev.com', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.alipay.gateway');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.alipay.face-biz-code', 'FACE_CERTIFY', 'string', '支付宝人脸 biz_code', 'auth', '与开放平台签约产品一致时一般不需改', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.alipay.face-biz-code');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.ovooa.invoke-url', '', 'string', '喵雨欣调用接口 URL', 'auth', '喵雨欣开发平台控制台提供的完整调用地址', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.ovooa.invoke-url');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.ovooa.api-token', '', 'string', '喵雨欣 API Token', 'auth', '写入 Authorization: Bearer …（若非 Bearer 可整块填入自定义头）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.ovooa.api-token');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.ovooa.invoke-json-template',
       '{"real_name":"{realName}","id_card":"{idCard}","notify_url":"{notifyUrl}","user_id":"{userId}"}',
       'string', '喵雨欣调用 JSON 模板', 'auth', '占位：{realName}{idCard}{notifyUrl}{userId}', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.ovooa.invoke-json-template');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.ovooa.callback-secret', '', 'string', '喵雨欣回调密钥', 'auth', '回调请求头或 Query 携带，与下方头名配合', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.ovooa.callback-secret');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.ovooa.callback-secret-header', 'X-Realname-Secret', 'string', '喵雨欣回调密钥头名', 'auth', '空表示不校验密钥（不推荐生产）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.ovooa.callback-secret-header');
