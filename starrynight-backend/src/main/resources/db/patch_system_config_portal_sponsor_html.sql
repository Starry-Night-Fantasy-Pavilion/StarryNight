-- 赞助区块：上传的 HTML 文件访问 URL（对象存储）
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.footer.sponsor.html-url', '', 'string', '赞助 HTML URL', 'portal', '上传鸣谢页 HTML 后的完整 URL；用户端 iframe 嵌入', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.footer.sponsor.html-url');
