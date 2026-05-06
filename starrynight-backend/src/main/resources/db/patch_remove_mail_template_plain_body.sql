-- 邮件模板改为仅使用上传的 HTML，移除库内「纯文本正文」配置键（mail.template.*.body）
DELETE FROM system_config
WHERE config_key LIKE 'mail.template.%.body';
