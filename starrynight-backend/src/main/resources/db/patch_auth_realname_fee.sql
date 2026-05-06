-- 实名认证费：开关 + 金额（元）；收款走易支付（见 patch_auth_realname_fee_epay.sql）

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.fee.enabled', 'false', 'boolean', '实名认证费开关', 'auth', '开启后用户须先通过易支付缴纳下方金额，再可发起核验；需配置 payment.epay.*', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.fee.enabled');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.fee.amount-yuan', '0', 'number', '实名认证费（元）', 'auth', '人民币元；走易支付，不入账星夜币', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.fee.amount-yuan');
