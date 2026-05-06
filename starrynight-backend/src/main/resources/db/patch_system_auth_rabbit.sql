-- 已有库若未包含「注册开关 + RabbitMQ 运营配置」时执行一次（与 seed.sql 中 INSERT…WHERE NOT EXISTS 等价）

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.register.email.enabled', 'true', 'boolean', '允许邮箱注册', 'auth', '关闭后注册接口拒绝携带邮箱', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.register.email.enabled');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.register.phone.enabled', 'true', 'boolean', '允许手机号注册', 'auth', '关闭后注册接口拒绝携带手机号', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.register.phone.enabled');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'auth.realname.enabled', 'false', 'boolean', '注册实名认证', 'auth', '开启后注册须填写真实姓名与 18 位身份证', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'auth.realname.enabled');

INSERT INTO system_config (config_key, config_value, config_type, config_name, config_group, description, editable)
SELECT 'rabbitmq.integration.enabled', 'true', 'boolean', '启用 RabbitMQ 集成', 'rabbitmq', '关闭后不发消息、不消费；需重启后端', 1
WHERE NOT EXISTS (SELECT 1 FROM system_config WHERE config_key = 'rabbitmq.integration.enabled');

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
