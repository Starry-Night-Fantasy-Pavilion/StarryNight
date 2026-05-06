-- 已有库：书目增加书源 URL / 书源 JSON（与 BookstoreBookSchemaCompat 逻辑一致；列已存在时勿重复执行）
-- 亦可重启后端，由 BookstoreBookSchemaCompat 自动补齐。
ALTER TABLE bookstore_book
    ADD COLUMN source_url VARCHAR(2000) NULL COMMENT '书源详情或目录页 URL（对接外部解析用）' AFTER tags;
ALTER TABLE bookstore_book
    ADD COLUMN source_json MEDIUMTEXT NULL COMMENT '书源规则 JSON（Legado/自定义引擎等）' AFTER source_url;
