-- 新增运营菜单键 system-config（空白「系统配置」页）；已有库执行一次
UPDATE admin_role
SET menu_permissions = REPLACE(menu_permissions, '"storage","logs"', '"storage","system-config","logs"')
WHERE deleted = 0
  AND menu_permissions LIKE '%"storage"%'
  AND menu_permissions LIKE '%"logs"%'
  AND menu_permissions NOT LIKE '%"system-config"%';
