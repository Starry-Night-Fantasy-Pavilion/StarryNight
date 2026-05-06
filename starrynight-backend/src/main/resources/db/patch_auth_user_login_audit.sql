-- 用户注册/登录审计：首次注册 IP、最后登录时间与 IP
-- 已有库执行本脚本一次即可；全新安装请同步更新 schema.sql 中 auth_user 表定义

ALTER TABLE auth_user
    ADD COLUMN register_ip VARCHAR(45) NULL COMMENT '首次注册IP' AFTER avatar,
    ADD COLUMN last_login_time DATETIME NULL COMMENT '最后登录时间' AFTER register_ip,
    ADD COLUMN last_login_ip VARCHAR(45) NULL COMMENT '最后登录IP' AFTER last_login_time;
