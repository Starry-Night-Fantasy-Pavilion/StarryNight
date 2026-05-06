-- 已有库：为运营角色菜单 JSON 追加 community（不含该片段时执行一次即可）
UPDATE admin_role
SET menu_permissions = REPLACE(menu_permissions, '"novels"', '"novels","community"')
WHERE deleted = 0
  AND menu_permissions IS NOT NULL
  AND menu_permissions LIKE '%"novels"%'
  AND menu_permissions NOT LIKE '%"community"%';
