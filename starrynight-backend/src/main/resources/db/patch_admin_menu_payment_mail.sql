-- 支付与邮件模板已并入「系统配置」页内 Tab，侧栏不再单独展示。
-- 若曾执行过旧版 patch（向 menu_permissions 写入 payment-config / mail-template），执行本脚本清理。
UPDATE admin_role
SET menu_permissions = REPLACE(REPLACE(menu_permissions, ',"payment-config"', ''), ',"mail-template"', '')
WHERE deleted = 0
  AND (
    menu_permissions LIKE '%"payment-config"%'
    OR menu_permissions LIKE '%"mail-template"%'
  );
