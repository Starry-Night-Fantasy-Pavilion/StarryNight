-- 实名认证费改走易支付（现金）：用户表记录最近一次已支付的认证费订单号
-- 已有库执行一次；全新安装以 schema.sql 为准

ALTER TABLE auth_user
    ADD COLUMN realname_fee_paid_record_no VARCHAR(64) NULL COMMENT '易支付实名认证费已付订单号（recharge_record.record_no）' AFTER real_name_verify_outer_no;

UPDATE system_config SET description = '开启后用户须先通过易支付缴纳下方金额（元），支付成功后再可发起人脸/三方核验；需同时配置 payment.epay.*'
WHERE config_key = 'auth.realname.fee.enabled';

UPDATE system_config SET description = '人民币元；走易支付网关收款，不入账星夜币'
WHERE config_key = 'auth.realname.fee.amount-yuan';
