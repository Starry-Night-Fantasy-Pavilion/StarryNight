-- 兼容性修复迁移
-- 用于补齐早期创建的表与当前代码期望结构之间的差异
-- 主要修复：
-- 1) community_categories 缺少 sort / slug / is_active 字段
-- 2) membership_levels 缺少 sort_order 字段

-- 注意：MySQL 不支持 "ADD COLUMN IF NOT EXISTS" 语法，这里只写标准 ALTER，
-- 迁移脚本会在列已经存在时报 Warning，可以忽略。

-- 社区分类表：补齐列并对齐命名
ALTER TABLE `__PREFIX__community_categories`
    ADD COLUMN `sort` INT(11) NOT NULL DEFAULT 0 AFTER `description`,
    ADD COLUMN `slug` VARCHAR(255) NULL AFTER `name`,
    ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `sort`;

-- 如果原来只有 sort_order，可以尝试把旧值迁移到 sort（忽略缺列时的错误）
UPDATE `__PREFIX__community_categories`
SET `sort` = `sort_order`
WHERE `sort` = 0;

-- 会员等级表：补充 sort_order，便于后台按排序展示
ALTER TABLE `__PREFIX__membership_levels`
    ADD COLUMN `sort_order` INT(11) NOT NULL DEFAULT 0 AFTER `level`;

