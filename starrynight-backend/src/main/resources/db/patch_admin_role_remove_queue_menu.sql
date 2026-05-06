-- 运营菜单已移除「消息队列」项，权限键 queue 合并为 system（系统配置）；已有库执行一次即可
UPDATE admin_role
SET menu_permissions = REPLACE(menu_permissions, ',"queue"', '')
WHERE deleted = 0 AND menu_permissions LIKE '%"queue"%';

UPDATE admin_role
SET menu_permissions = REPLACE(menu_permissions, '"queue",', '')
WHERE deleted = 0 AND menu_permissions LIKE '%"queue"%';
