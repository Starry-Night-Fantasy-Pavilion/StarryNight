-- =========================
-- 星夜创作引擎初始数据
-- =========================
-- 本迁移为 starry_night_engine_permissions 和 membership_levels 植入初始数据，
-- 使普通用户（未开通会员）可使用基础版星夜创作引擎（小说创作、AI音乐、短剧创作、图片生成）

-- 1. 会员等级初始数据（使用 INSERT IGNORE + uniq_level 避免重复）
INSERT IGNORE INTO `__PREFIX__membership_levels` (`name`, `level`, `description`, `sort_order`) VALUES
('免费用户', 0, '注册即可使用基础创作功能', 0),
('标准会员', 1, '标准创作功能，更多权益', 1),
('高级会员', 2, '高级创作功能，优先支持', 2),
('企业版', 3, '企业级功能，专属服务', 3);

-- 2. 星夜创作引擎权限初始数据
-- membership_level_id = NULL 表示「未开通会员的普通用户」，可访问基础版
INSERT INTO `__PREFIX__starry_night_engine_permissions` (`engine_version`, `membership_level_id`, `is_enabled`, `description`, `custom_config`) 
SELECT 'basic', NULL, 1, '基础版：普通用户/未开通会员可用', NULL
FROM (SELECT 1) AS _tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `__PREFIX__starry_night_engine_permissions` 
    WHERE `engine_version` = 'basic' AND `membership_level_id` IS NULL
);

-- 3. 为各会员等级配置对应引擎版本（若等级存在则插入）
INSERT INTO `__PREFIX__starry_night_engine_permissions` (`engine_version`, `membership_level_id`, `is_enabled`, `description`, `custom_config`)
SELECT 'basic', ml.id, 1, '基础版：免费用户', NULL
FROM `__PREFIX__membership_levels` ml
WHERE ml.level = 0
AND NOT EXISTS (
    SELECT 1 FROM `__PREFIX__starry_night_engine_permissions` p 
    WHERE p.engine_version = 'basic' AND p.membership_level_id = ml.id
);

INSERT INTO `__PREFIX__starry_night_engine_permissions` (`engine_version`, `membership_level_id`, `is_enabled`, `description`, `custom_config`)
SELECT 'standard', ml.id, 1, '标准版：标准会员', NULL
FROM `__PREFIX__membership_levels` ml
WHERE ml.level = 1
AND NOT EXISTS (
    SELECT 1 FROM `__PREFIX__starry_night_engine_permissions` p 
    WHERE p.engine_version = 'standard' AND p.membership_level_id = ml.id
);

INSERT INTO `__PREFIX__starry_night_engine_permissions` (`engine_version`, `membership_level_id`, `is_enabled`, `description`, `custom_config`)
SELECT 'premium', ml.id, 1, '高级版：高级会员', NULL
FROM `__PREFIX__membership_levels` ml
WHERE ml.level = 2
AND NOT EXISTS (
    SELECT 1 FROM `__PREFIX__starry_night_engine_permissions` p 
    WHERE p.engine_version = 'premium' AND p.membership_level_id = ml.id
);

INSERT INTO `__PREFIX__starry_night_engine_permissions` (`engine_version`, `membership_level_id`, `is_enabled`, `description`, `custom_config`)
SELECT 'enterprise', ml.id, 1, '企业版：企业会员', NULL
FROM `__PREFIX__membership_levels` ml
WHERE ml.level = 3
AND NOT EXISTS (
    SELECT 1 FROM `__PREFIX__starry_night_engine_permissions` p 
    WHERE p.engine_version = 'enterprise' AND p.membership_level_id = ml.id
);
