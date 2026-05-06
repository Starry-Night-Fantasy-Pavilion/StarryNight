-- 站点品牌、页脚模块（备案/联系/友链/赞助）、平台币展示名；用户端通过 GET /api/portal/public-config 读取

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.site.name', '星夜阁', 'string', '网站名称', 'portal', '浏览器标题后缀、页脚等展示用站点名', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.site.name');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.site.logo-url', '', 'string', '网站 Logo URL', 'portal', '完整可访问的图片地址；也可在运营端上传写入', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.site.logo-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.wallet.coin-display-name', '星夜币', 'string', '平台币名称', 'portal', '余额、充值等界面展示用', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.wallet.coin-display-name');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.footer.icp.enabled', 'false', 'boolean', '展示备案信息', 'portal', '开启后用户端页脚显示备案号与链接', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.footer.icp.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.footer.icp.record', '', 'string', '备案号文案', 'portal', '如 粤ICP备xxxxxxxx号', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.footer.icp.record');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.footer.icp.url', '', 'string', '备案查询链接', 'portal', '可选，如 https://beian.miit.gov.cn/', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.footer.icp.url');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.footer.contact.enabled', 'false', 'boolean', '展示联系信息', 'portal', '开启后用户端页脚显示联系条目', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.footer.contact.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.footer.contact.lines-json', '[]', 'json', '联系信息 JSON', 'portal', '[{"label":"邮箱","text":"hi@example.com","href":"mailto:hi@example.com"}]', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.footer.contact.lines-json');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.footer.friend-links.enabled', 'false', 'boolean', '展示友情链接', 'portal', '开启后用户端页脚显示友链列表', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.footer.friend-links.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.footer.friend-links.json', '[]', 'json', '友情链接 JSON', 'portal', '[{"name":"示例","url":"https://example.com"}]', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.footer.friend-links.json');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.footer.sponsor.enabled', 'false', 'boolean', '展示赞助/鸣谢', 'portal', '开启后用户端页脚显示赞助文案', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.footer.sponsor.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.footer.sponsor.text', '', 'string', '赞助文案', 'portal', '纯文本，多行；支持简单说明与鸣谢名单', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.footer.sponsor.text');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.footer.sponsor.html-url', '', 'string', '赞助 HTML URL', 'portal', '上传鸣谢页 HTML 后的完整 URL；用户端 iframe 嵌入', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.footer.sponsor.html-url');
