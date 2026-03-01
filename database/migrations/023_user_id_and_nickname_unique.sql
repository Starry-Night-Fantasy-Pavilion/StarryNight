-- 用户ID和昵称唯一性约束迁移
-- 1. 为昵称添加唯一性约束（如果不存在）
-- 2. 确保ID唯一性（主键已保证）

-- 注意：执行此迁移前，请确保表中没有重复的昵称
-- 如果有重复昵称，需要先清理数据：
-- UPDATE sn_users SET nickname = CONCAT(nickname, '_', id) WHERE nickname IN (SELECT nickname FROM (SELECT nickname, COUNT(*) as cnt FROM sn_users GROUP BY nickname HAVING cnt > 1) AS dup);

-- 添加昵称唯一性约束
-- 如果约束已存在，会报错但可以忽略（通过应用层处理）

ALTER TABLE `__PREFIX__users` 
ADD UNIQUE KEY `unique_nickname` (`nickname`);

-- 注意：由于MySQL不支持在ALTER TABLE中使用条件判断，
-- 如果约束已存在，此语句会失败，需要在应用层捕获错误并忽略
