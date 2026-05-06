-- 一次性迁移：为「已有」数据库补充 ops_account.email（勿加入 Spring Boot 自动执行的 table.sql，否则会重复启动失败）
-- 在 MySQL 客户端对目标库执行本文件；若列或索引已存在，对应语句会报错，跳过即可。

ALTER TABLE ops_account
    ADD COLUMN email VARCHAR(100) NULL COMMENT '邮箱（可选登录）' AFTER username;

ALTER TABLE ops_account
    ADD UNIQUE INDEX uk_ops_account_email (email);
