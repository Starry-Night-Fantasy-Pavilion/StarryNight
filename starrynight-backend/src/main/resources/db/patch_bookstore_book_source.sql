-- 阅读 Legado 书源集合（一条一行完整 JSON，如 yiove / 本地 json 数组导入）
CREATE TABLE IF NOT EXISTS bookstore_book_source (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    book_source_name VARCHAR(200) NOT NULL COMMENT 'Legado bookSourceName',
    book_source_url VARCHAR(500) NULL COMMENT 'Legado bookSourceUrl（可能为站点根或占位）',
    book_source_group VARCHAR(200) NULL COMMENT 'Legado bookSourceGroup',
    source_json MEDIUMTEXT NOT NULL COMMENT '单条书源完整 JSON',
    enabled TINYINT NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
    sort_order INT NOT NULL DEFAULT 0,
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (book_source_name),
    INDEX idx_enabled_sort (enabled, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Legado 书源集合';
