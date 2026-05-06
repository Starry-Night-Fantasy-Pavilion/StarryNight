-- 默认超级角色（运营端）：预置 admin 绑定此角色，拥有全部运营菜单（含「账号与权限」ops-accounts）
INSERT INTO admin_role (name, code, description, menu_permissions, status, deleted)
SELECT '超级管理员', 'SUPER_ADMIN', '系统内置：全部运营菜单；预置账号 admin 默认绑定本角色',
       '["dashboard","users","categories","bookstore","recommendations","novels","community","announcements","activities","redeem-codes","growth-tasks","billing","orders","ai-config","vector-db","storage","system-config","logs","cache","system","ops-accounts"]', 1, 0
WHERE NOT EXISTS (SELECT 1 FROM admin_role WHERE code = 'SUPER_ADMIN' AND deleted = 0);

-- 已有库：同步超级管理员菜单权限为全量（避免历史种子缺菜单）
UPDATE admin_role
SET description = '系统内置：全部运营菜单；预置账号 admin 默认绑定本角色',
    menu_permissions = '["dashboard","users","categories","bookstore","recommendations","novels","community","announcements","activities","redeem-codes","growth-tasks","billing","orders","ai-config","vector-db","storage","system-config","logs","cache","system","ops-accounts"]'
WHERE code = 'SUPER_ADMIN' AND deleted = 0;

-- 管理员分组：日常运营角色（不含「账号与权限」菜单）
INSERT INTO admin_role (name, code, description, menu_permissions, status, deleted)
SELECT '管理员', 'OPS_ADMIN', '管理员分组：业务与系统运维菜单；不含账号与权限（运营账号与角色）',
       '["dashboard","users","categories","bookstore","recommendations","novels","community","announcements","activities","redeem-codes","growth-tasks","billing","orders","ai-config","vector-db","storage","system-config","logs","cache","system"]', 1, 0
WHERE NOT EXISTS (SELECT 1 FROM admin_role WHERE code = 'OPS_ADMIN' AND deleted = 0);

-- 已有库：管理员分组菜单与种子对齐（含社区管理）
UPDATE admin_role
SET menu_permissions = '["dashboard","users","categories","bookstore","recommendations","novels","community","announcements","activities","redeem-codes","growth-tasks","billing","orders","ai-config","vector-db","storage","system-config","logs","cache","system"]'
WHERE code = 'OPS_ADMIN' AND deleted = 0;

-- 默认运营账号：admin / admin123
INSERT INTO ops_account (username, password, role_id, status, deleted)
SELECT 'admin', '$2b$10$Y0UzAwn9xZ0Nyte2vvmjquk8Gn7dDYp1zThS5/OybWY8Q1V.MZHcm', r.id, 1, 0
FROM admin_role r
WHERE r.code = 'SUPER_ADMIN'
  AND r.deleted = 0
  AND NOT EXISTS (SELECT 1 FROM ops_account WHERE username = 'admin' AND deleted = 0);

-- 兼容迁移：历史版本将运营账号落在 auth_user（is_admin=1），启动时同步进 ops_account
INSERT INTO ops_account (username, password, role_id, status, deleted)
SELECT au.username, au.password, r.id, au.status, 0
FROM auth_user au
JOIN admin_role r ON r.code = 'SUPER_ADMIN' AND r.deleted = 0
WHERE au.deleted = 0
  AND au.is_admin = 1
  AND NOT EXISTS (SELECT 1 FROM ops_account oa WHERE oa.username = au.username AND oa.deleted = 0);

-- 历史上 seed 曾写入错误 bcrypt（与 admin123 不匹配）；已迁移账号按旧哈希定点纠正一次
UPDATE ops_account
SET password = '$2b$10$Y0UzAwn9xZ0Nyte2vvmjquk8Gn7dDYp1zThS5/OybWY8Q1V.MZHcm'
WHERE username = 'admin'
  AND deleted = 0
  AND password = '$2b$10$0dv9CHA2CLlwllgDtPQneeoJDx7B7Oz6Z13/3LZSV4tAV9e75Zunu';

-- 运行时业务配置（仅此表 + 运营端；应用不再从 YAML/环境变量读取下列键）
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'jwt.secret', 'starrynight-secret-key-change-in-production-environment', 'string', 'JWT 签名密钥', 'security', '生产环境务必在运营端修改为强随机串', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'jwt.secret');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'jwt.expiration', '86400000', 'number', 'JWT 访问令牌过期(ms)', 'security', 'jwt.expiration', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'jwt.expiration');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'jwt.refresh-expiration', '604800000', 'number', 'JWT 刷新令牌过期(ms)', 'security', 'jwt.refresh-expiration', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'jwt.refresh-expiration');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'openai.api.key', '', 'string', 'OpenAI API Key', 'ai', '留空为模拟客户端；填写真实 sk- 启用 OpenAI', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'openai.api.key');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'openai.api.base-url', 'https://api.openai.com/v1', 'string', 'OpenAI Base URL', 'ai', 'openai.api.base-url', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'openai.api.base-url');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'openai.api.timeout', '120000', 'number', 'OpenAI 超时(ms)', 'ai', 'openai.api.timeout', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'openai.api.timeout');

-- 对象存储：值一律在运营端「对象存储配置」中填写后写入本表，种子仅保证键存在、值为空（未启用 MinIO 直至运营端保存）
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'storage.minio.endpoint', '', 'string', 'MinIO 端点', 'storage', '运营端填写，如 http://127.0.0.1:9000', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'storage.minio.endpoint');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'storage.minio.access-key', '', 'string', 'MinIO Access Key', 'storage', '运营端填写，勿写入代码或种子', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'storage.minio.access-key');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'storage.minio.secret-key', '', 'string', 'MinIO Secret Key', 'storage', '运营端填写，勿写入代码或种子', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'storage.minio.secret-key');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'storage.minio.bucket', '', 'string', 'MinIO 桶名', 'storage', '运营端填写桶名称', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'storage.minio.bucket');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'starrynight.mybatis.pagination-max-limit', '500', 'number', '分页最大条数', 'mybatis', 'starrynight.mybatis.pagination-max-limit', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'starrynight.mybatis.pagination-max-limit');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'starrynight.engine.max-context-tokens', '4096', 'number', '引擎最大上下文 Token', 'engine', 'ContextTruncator 最大 token', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'starrynight.engine.max-context-tokens');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'openai.api.model', 'gpt-4o-mini', 'string', 'OpenAI 默认模型', 'ai', 'openai.api.model', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'openai.api.model');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'ai.generation.temperature', '0.7', 'number', '生成温度', 'ai', 'ai.generation.temperature', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'ai.generation.temperature');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'ai.generation.max-tokens', '4096', 'number', '生成 max_tokens', 'ai', 'ai.generation.max-tokens', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'ai.generation.max-tokens');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'ai.cache.enabled', 'true', 'boolean', 'AI 缓存开关', 'ai', 'ai.cache.enabled', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'ai.cache.enabled');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'ai.cache.ttl-seconds', '3600', 'number', 'AI 缓存 TTL（秒）', 'ai', 'ai.cache.ttl-seconds', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'ai.cache.ttl-seconds');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.data.redis.host', 'localhost', 'string', 'Redis 主机', 'redis', 'spring.data.redis.host', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.data.redis.host');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.data.redis.port', '6379', 'number', 'Redis 端口', 'redis', 'spring.data.redis.port', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.data.redis.port');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.data.redis.password', '', 'string', 'Redis 密码', 'redis', '无密码可留空', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.data.redis.password');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.data.redis.database', '0', 'number', 'Redis 库索引', 'redis', 'spring.data.redis.database', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.data.redis.database');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'server.servlet.multipart.max-file-size', '100MB', 'string', '单文件上传上限', 'server', 'server.servlet.multipart.max-file-size', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'server.servlet.multipart.max-file-size');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'server.servlet.multipart.max-request-size', '100MB', 'string', '整请求上传上限', 'server', 'server.servlet.multipart.max-request-size', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'server.servlet.multipart.max-request-size');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'logging.level.com.starrynight', 'DEBUG', 'string', '应用日志级别', 'logging', 'DEBUG/INFO/WARN/ERROR', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'logging.level.com.starrynight');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'logging.level.org.springframework', 'INFO', 'string', 'Spring 日志级别', 'logging', 'DEBUG/INFO/WARN/ERROR', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'logging.level.org.springframework');

-- 注册：是否允许填写邮箱 / 手机号（运营端「系统设置」可改）
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.register.email.enabled', 'true', 'boolean', '允许邮箱注册', 'auth', '关闭后注册接口拒绝携带邮箱', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.register.email.enabled');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.register.phone.enabled', 'true', 'boolean', '允许手机号注册', 'auth', '关闭后注册接口拒绝携带手机号', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.register.phone.enabled');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.enabled', 'false', 'boolean', '实名认证总开关', 'auth', '开启后个人中心登记证件；未核验限制导出等', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.enabled');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.verify_provider', 'alipay', 'string', '实名核验方式', 'auth', 'alipay=支付宝；ovooa 或 miaoyuxin=喵雨欣开发平台（历史 basic 已废弃，将在运行时视为 alipay）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.verify_provider');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.alipay.app-id', '', 'string', '支付宝 AppID', 'auth', '开放平台应用 APPID', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.alipay.app-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.alipay.private-key', '', 'string', '支付宝应用私钥 PEM', 'auth', 'RSA2', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.alipay.private-key');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.alipay.alipay-public-key', '', 'string', '支付宝公钥 PEM', 'auth', '验签异步通知', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.alipay.alipay-public-key');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.alipay.gateway', 'https://openapi.alipay.com/gateway.do', 'string', '支付宝网关', 'auth', '正式环境网关', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.alipay.gateway');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.alipay.face-biz-code', 'FACE_CERTIFY', 'string', '支付宝人脸 biz_code', 'auth', '与签约产品一致', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.alipay.face-biz-code');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.ovooa.invoke-url', '', 'string', '喵雨欣调用 URL', 'auth', '喵雨欣开发平台控制台完整调用地址', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.ovooa.invoke-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.ovooa.api-token', '', 'string', '喵雨欣 API Token', 'auth', 'Bearer Token', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.ovooa.api-token');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.ovooa.invoke-json-template',
       '{"real_name":"{realName}","id_card":"{idCard}","notify_url":"{notifyUrl}","user_id":"{userId}"}',
       'string', '喵雨欣调用 JSON 模板', 'auth', '占位 {realName}{idCard}{notifyUrl}{userId}', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.ovooa.invoke-json-template');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.ovooa.callback-secret', '', 'string', '喵雨欣回调密钥', 'auth', '与回调头名配合', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.ovooa.callback-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.ovooa.callback-secret-header', 'X-Realname-Secret', 'string', '喵雨欣回调密钥头名', 'auth', '空则不校验', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.ovooa.callback-secret-header');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.fee.enabled', 'false', 'boolean', '实名认证费开关', 'auth', '开启后用户须先通过易支付缴纳下方金额，再可发起核验；需配置 payment.epay.*', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.fee.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.fee.amount-yuan', '0', 'number', '实名认证费（元）', 'auth', '人民币元；走易支付，不入账星夜币', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.fee.amount-yuan');

-- RabbitMQ 集成总开关（仅启动期生效，改后需重启；为 false 时不装配队列与消费者）
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'rabbitmq.integration.enabled', 'true', 'boolean', '启用 RabbitMQ 集成', 'rabbitmq', '关闭后不发消息、不消费；需重启后端', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'rabbitmq.integration.enabled');

-- RabbitMQ（与 OpsRabbitMQConnectionConfiguration 读取的键一致；修改后需重启后端生效）
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.rabbitmq.host', 'localhost', 'string', 'RabbitMQ 主机', 'rabbitmq', 'spring.rabbitmq.host', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.rabbitmq.host');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.rabbitmq.port', '5672', 'number', 'RabbitMQ 端口', 'rabbitmq', 'spring.rabbitmq.port', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.rabbitmq.port');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.rabbitmq.username', 'guest', 'string', 'RabbitMQ 用户名', 'rabbitmq', 'spring.rabbitmq.username', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.rabbitmq.username');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.rabbitmq.password', 'guest', 'string', 'RabbitMQ 密码', 'rabbitmq', 'spring.rabbitmq.password', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.rabbitmq.password');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.rabbitmq.virtual-host', '/', 'string', 'RabbitMQ 虚拟主机', 'rabbitmq', '一般为 /', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.rabbitmq.virtual-host');

-- 系统配置页：邮件 / 短信 / 第三方登录（键值由运营端维护；发信与短信需服务端接线）
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.enabled', 'false', 'boolean', '启用邮件发送', 'mail', '总开关，业务发信前需为 true', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.mail.host', 'localhost', 'string', 'SMTP 主机', 'mail', 'spring.mail.host', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.mail.host');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.mail.port', '587', 'number', 'SMTP 端口', 'mail', 'spring.mail.port', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.mail.port');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.mail.username', '', 'string', 'SMTP 用户名', 'mail', 'spring.mail.username', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.mail.username');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'spring.mail.password', '', 'string', 'SMTP 密码', 'mail', 'spring.mail.password', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'spring.mail.password');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.from', '', 'string', '发件人地址', 'mail', '可空，空则使用 spring.mail.username', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.from');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.from.personal', '', 'string', '发件者显示名', 'mail', '可选；收件箱「发件人」旁展示的中文名，UTF-8', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.from.personal');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.smtp.starttls', 'true', 'boolean', 'SMTP STARTTLS', 'mail', '端口非 465 时一般为 true', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.smtp.starttls');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.smtp.ssl', 'false', 'boolean', 'SMTP SSL（465）', 'mail', 'SSL 直连时 true', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.smtp.ssl');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.template.reset-password.subject', '密码重置验证码', 'string', '邮件模板-找回密码标题', 'mail', '发找回密码邮件时使用（正文仅为上传的 HTML）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.template.reset-password.subject');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.template.verification-code.subject', '验证码', 'string', '邮件模板-通用验证码标题', 'mail', '通用验证码邮件标题（正文仅为上传的 HTML）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.template.verification-code.subject');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.template.activity.subject', '活动通知', 'string', '邮件模板-活动通知标题', 'mail', '活动/公告类邮件标题（正文仅为上传的 HTML）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.template.activity.subject');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'mail.template.marketing.subject', '邮件通知', 'string', '邮件模板-营销推广标题', 'mail', '营销推广类邮件标题（正文仅为上传的 HTML）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'mail.template.marketing.subject');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'payment.epay.enabled', 'false', 'boolean', '启用易支付', 'payment', '标准易支付商户参数；充值下单接口读取', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'payment.epay.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'payment.epay.gateway', '', 'string', '易支付网关 URL', 'payment', '如 https://pay.example.com/submit.php', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'payment.epay.gateway');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'payment.epay.pid', '', 'string', '易支付商户 ID（PID）', 'payment', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'payment.epay.pid');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'payment.epay.key', '', 'string', '易支付商户密钥', 'payment', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'payment.epay.key');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'payment.epay.sign-type', 'md5', 'string', '易支付签名类型', 'payment', '一般为 md5', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'payment.epay.sign-type');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.enabled', 'false', 'boolean', '启用短信', 'sms', '总开关', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.provider', 'aliyun', 'string', '短信服务商', 'sms', 'aliyun 或 tencent', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.provider');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.access-key-id', '', 'string', '短信 AccessKey ID', 'sms', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.access-key-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.access-key-secret', '', 'string', '短信 AccessKey Secret', 'sms', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.access-key-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.sign-name', '', 'string', '短信签名', 'sms', NULL, 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.sign-name');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.template.verification', '', 'string', '验证码短信模板编码', 'sms', '阿里云填 SMS_xxx，控制台模板变量名须为 code；腾讯云填数字模板 ID', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.template.verification');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.tencent.sdk-app-id', '', 'string', '腾讯云短信 SdkAppId', 'sms', '短信控制台应用 ID，仅 tencent 必填', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.tencent.sdk-app-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'sms.tencent.region', 'ap-guangzhou', 'string', '腾讯云短信地域', 'sms', '如 ap-guangzhou、ap-nanjing', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'sms.tencent.region');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.wechat.enabled', 'false', 'boolean', '微信登录', 'oauth', '启用微信 OAuth', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.wechat.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.qq.enabled', 'false', 'boolean', 'QQ 登录', 'oauth', '启用 QQ OAuth', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.qq.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.github.enabled', 'false', 'boolean', 'GitHub 登录', 'oauth', '启用 GitHub OAuth', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.github.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.enabled', 'false', 'boolean', '谷歌登录', 'oauth', '启用 Google OAuth（预留）', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.wechat.client-id', '', 'string', '微信开放平台 AppID', 'oauth', '网站应用扫码登录；与 client-secret 对应', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.wechat.client-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.wechat.client-secret', '', 'string', '微信开放平台 AppSecret', 'oauth', '勿泄露', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.wechat.client-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.qq.client-id', '', 'string', 'QQ 互联 AppID', 'oauth', 'OAuth2 client_id', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.qq.client-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.qq.client-secret', '', 'string', 'QQ 互联 AppKey', 'oauth', 'OAuth2 client_secret', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.qq.client-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.github.client-id', '', 'string', 'GitHub OAuth Client ID', 'oauth', 'GitHub Developer Settings 创建 OAuth App', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.github.client-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.github.client-secret', '', 'string', 'GitHub OAuth Client Secret', 'oauth', '勿泄露', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.github.client-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.client-id', '', 'string', 'Google OAuth Client ID', 'oauth', 'Google Cloud Console OAuth 2.0 客户端', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.client-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.client-secret', '', 'string', 'Google OAuth Client Secret', 'oauth', '勿泄露', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.client-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.linuxdo.enabled', 'false', 'boolean', 'LINUX DO 登录', 'oauth', '启用 LINUX DO Connect 登录', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.linuxdo.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.linuxdo.client-id', '', 'string', 'LINUX DO Client ID', 'oauth', '在 https://connect.linux.do 应用接入中创建', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.linuxdo.client-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.linuxdo.client-secret', '', 'string', 'LINUX DO Client Secret', 'oauth', '勿泄露、勿提交代码库', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.linuxdo.client-secret');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.public-base-url', '', 'string', '站点公网根 URL', 'oauth', '如 https://你的域名 无尾斜杠；OAuth 回调须与 Connect 中 redirect 一致', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.public-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'portal.frontend.api-public-origin', '', 'string', 'portal.frontend.api-public-origin', 'oauth', 'Browser origin for this app /api only (optional). Empty = relative /api. VITE_API_PUBLIC_ORIGIN overrides when set.', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'portal.frontend.api-public-origin');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.linuxdo.platform-base-url', '', 'string', 'auth.oauth.linuxdo.platform-base-url', 'oauth', 'LINUX DO Connect OAuth 2.0 base. Default https://connect.linux.do. Paths: /oauth2/authorize, /oauth2/token, /api/user', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.linuxdo.platform-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.github.oauth-web-base-url', '', 'string', 'auth.oauth.github.oauth-web-base-url', 'oauth', 'github.com OAuth web: /login/oauth/authorize, /login/oauth/access_token. Default https://github.com', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.github.oauth-web-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.github.rest-api-base-url', '', 'string', 'auth.oauth.github.rest-api-base-url', 'oauth', 'GitHub REST API: /user, /user/emails. Default https://api.github.com', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.github.rest-api-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.accounts-base-url', '', 'string', 'auth.oauth.google.accounts-base-url', 'oauth', 'Google OAuth 2.0 authorization host. Default https://accounts.google.com. Path: /o/oauth2/v2/auth', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.accounts-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.token-base-url', '', 'string', 'auth.oauth.google.token-base-url', 'oauth', 'Google token endpoint host. Default https://oauth2.googleapis.com. Path: /token', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.token-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.google.userinfo-base-url', '', 'string', 'auth.oauth.google.userinfo-base-url', 'oauth', 'Google OIDC userinfo host. Default https://openidconnect.googleapis.com. Path: /v1/userinfo', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.google.userinfo-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.wechat.open-platform-base-url', '', 'string', 'auth.oauth.wechat.open-platform-base-url', 'oauth', 'WeChat Open Platform (website QR): open.weixin.qq.com. Path: /connect/qrconnect', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.wechat.open-platform-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.wechat.sns-api-base-url', '', 'string', 'auth.oauth.wechat.sns-api-base-url', 'oauth', 'WeChat SNS API host api.weixin.qq.com. Paths: /sns/oauth2/access_token, /sns/userinfo', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.wechat.sns-api-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.qq.open-api-base-url', '', 'string', 'auth.oauth.qq.open-api-base-url', 'oauth', 'QQ Connect graph host. Default https://graph.qq.com. OAuth2.0 /oauth2.0/* and /user/get_user_info', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.qq.open-api-base-url');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.platform-base-url', '', 'string', 'auth.oauth.zevost.platform-base-url', 'oauth', 'Zevost base URL (doc connect.php). Default https://u.zevost.com. Appended path: /connect.php?act=login|callback', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.platform-base-url');

-- 知我云聚合登录（https://u.zevost.com/doc.php）回调：{公网根}/api/auth/oauth/zevost/callback
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.enabled', 'false', 'boolean', '知我云聚合登录', 'oauth', '启用知我云聚合 OAuth', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.app-id', '', 'string', '知我云 AppID', 'oauth', '聚合后台 appid', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.app-id');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.app-key', '', 'string', '知我云 AppKey', 'oauth', '聚合后台 appkey，勿泄露', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.app-key');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.qq.enabled', 'false', 'boolean', '聚合·QQ', 'oauth', '知我云 type=qq', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.qq.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.wx.enabled', 'false', 'boolean', '聚合·微信', 'oauth', '知我云 type=wx', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.wx.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.alipay.enabled', 'false', 'boolean', '聚合·支付宝', 'oauth', '知我云 type=alipay', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.alipay.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.sina.enabled', 'false', 'boolean', '聚合·微博', 'oauth', '知我云 type=sina', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.sina.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.baidu.enabled', 'false', 'boolean', '聚合·百度', 'oauth', '知我云 type=baidu', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.baidu.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.douyin.enabled', 'false', 'boolean', '聚合·抖音', 'oauth', '知我云 type=douyin', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.douyin.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.huawei.enabled', 'false', 'boolean', '聚合·华为', 'oauth', '知我云 type=huawei', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.huawei.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.xiaomi.enabled', 'false', 'boolean', '聚合·小米', 'oauth', '知我云 type=xiaomi', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.xiaomi.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.google.enabled', 'false', 'boolean', '聚合·Google', 'oauth', '知我云 type=google', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.google.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.microsoft.enabled', 'false', 'boolean', '聚合·微软', 'oauth', '知我云 type=microsoft', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.microsoft.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.twitter.enabled', 'false', 'boolean', '聚合·Twitter', 'oauth', '知我云 type=twitter', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.twitter.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.dingtalk.enabled', 'false', 'boolean', '聚合·钉钉', 'oauth', '知我云 type=dingtalk', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.dingtalk.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.gitee.enabled', 'false', 'boolean', '聚合·Gitee', 'oauth', '知我云 type=gitee', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.gitee.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.oauth.zevost.type.github.enabled', 'false', 'boolean', '聚合·GitHub', 'oauth', '知我云 type=github', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.oauth.zevost.type.github.enabled');

-- 在线书城（运营端「书城管理」维护）
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'bookstore.enabled', 'true', 'boolean', '书城启用', 'bookstore', '关闭后前台书城首页仅提示维护', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'bookstore.enabled');
INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'bookstore.site_title', '星夜书库', 'string', '书库标题', 'bookstore', '页头展示标题', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'bookstore.site_title');
