-- 实名注册字段；已有库执行本脚本一次即可；全新安装请同步 schema.sql 中 auth_user 表定义
ALTER TABLE auth_user
    ADD COLUMN real_name VARCHAR(32) NULL COMMENT '真实姓名（实名注册）' AFTER phone,
    ADD COLUMN id_card_no VARCHAR(32) NULL COMMENT '证件号（实名注册）' AFTER real_name;
