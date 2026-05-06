-- 易支付（星夜支付对接占位，键供运营端与后续充值接口读取）与找回密码邮件模板

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'payment.epay.enabled', 'false', 'boolean', '启用易支付', 'payment', '对接标准易支付商户接口时需开启', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'payment.epay.enabled');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'payment.epay.gateway', '', 'string', '易支付网关 URL', 'payment', '完整地址，如 https://pay.example.com/submit.php', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'payment.epay.gateway');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'payment.epay.pid', '', 'string', '易支付商户 ID（PID）', 'payment', '商户后台获取', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'payment.epay.pid');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'payment.epay.key', '', 'string', '易支付商户密钥', 'payment', '用于签名，勿泄露', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'payment.epay.key');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'payment.epay.sign-type', 'md5', 'string', '签名类型', 'payment', '一般为 md5', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'payment.epay.sign-type');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.template.reset-password.subject', '密码重置验证码', 'string', '邮件模板-找回密码标题', 'mail', '发找回密码邮件标题（正文为上传的 HTML）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.template.reset-password.subject');
