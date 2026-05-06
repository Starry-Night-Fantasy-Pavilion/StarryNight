-- 实名核验状态；已有库执行一次。全新安装以 schema.sql 为准。
ALTER TABLE auth_user
    ADD COLUMN real_name_verified TINYINT NOT NULL DEFAULT 0 COMMENT '实名核验是否通过：0 否 1 是' AFTER id_card_no,
    ADD COLUMN real_name_verify_outer_no VARCHAR(80) NULL COMMENT '最近一次核验外部单号/流水' AFTER real_name_verified;

-- 历史数据：曾填写姓名+证件号的标记为已通过（迁移期策略；新逻辑需完成支付宝/喵雨欣核验）
UPDATE auth_user
SET real_name_verified = 1
WHERE deleted = 0
  AND real_name IS NOT NULL AND TRIM(real_name) <> ''
  AND id_card_no IS NOT NULL AND TRIM(id_card_no) <> '';
