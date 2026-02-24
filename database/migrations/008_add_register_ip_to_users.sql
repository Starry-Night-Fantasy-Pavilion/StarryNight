-- 添加用户注册IP字段
-- 用于记录用户注册时的IP地址，便于安全审计和反欺诈

-- 添加字段
ALTER TABLE `__PREFIX__users`
ADD COLUMN `register_ip` varchar(45) DEFAULT NULL COMMENT '注册IP地址' AFTER `phone`;

-- 添加索引以提高查询性能
CREATE INDEX `idx_register_ip` ON `__PREFIX__users` (`register_ip`);
