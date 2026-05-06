-- 作品/书城分类（一级 parent_id 为空，二级挂在一级下）
CREATE TABLE IF NOT EXISTS novel_category (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '分类ID',
    parent_id BIGINT COMMENT '父级ID，NULL 表示一级分类',
    name VARCHAR(64) NOT NULL COMMENT '显示名称',
    code VARCHAR(64) NOT NULL COMMENT '唯一编码（系统生成）',
    sort INT NOT NULL DEFAULT 0 COMMENT '排序',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_novel_category_code (code),
    INDEX idx_parent (parent_id),
    INDEX idx_status_sort (status, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='作品与书城分类';

-- 在线书城书目（与创作端 novel 表独立）
CREATE TABLE IF NOT EXISTS bookstore_book (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(100),
    cover_url VARCHAR(500),
    intro TEXT,
    category_id BIGINT COMMENT '关联 novel_category',
    is_vip TINYINT NOT NULL DEFAULT 0,
    rating DECIMAL(3, 1) NOT NULL DEFAULT 8.0,
    word_count INT NOT NULL DEFAULT 0 COMMENT '字数',
    read_count BIGINT NOT NULL DEFAULT 0 COMMENT '展示用阅读量',
    sort_order INT NOT NULL DEFAULT 0,
    status TINYINT NOT NULL DEFAULT 1 COMMENT '1上架 0下架',
    tags VARCHAR(500) COMMENT '逗号分隔标签',
    source_url VARCHAR(2000) COMMENT '书源详情或目录页 URL（对接外部解析用）',
    source_json MEDIUMTEXT COMMENT '书源规则 JSON（Legado/自定义引擎等）',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cat (category_id),
    INDEX idx_status_hot (status, read_count),
    INDEX idx_status_new (status, create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='书城书目';

-- 默认频道（男频 / 女频），仅顶层；编码固定便于与业务约定
INSERT IGNORE INTO novel_category (parent_id, name, code, sort, status) VALUES
(NULL, '男频', 'channel_nan_pin', 0, 1),
(NULL, '女频', 'channel_nv_pin', 10, 1);

-- 书城章节（正文由运营维护；阅读器走 API，可配合前端本地缓存）
CREATE TABLE IF NOT EXISTS bookstore_chapter (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '章节ID',
    book_id BIGINT NOT NULL COMMENT '书目ID',
    chapter_no INT NOT NULL COMMENT '序号，从1起',
    title VARCHAR(200) NOT NULL COMMENT '章节标题',
    content MEDIUMTEXT COMMENT '正文（纯文本或 HTML）',
    word_count INT NOT NULL DEFAULT 0 COMMENT '字数',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_bookstore_chapter_no (book_id, chapter_no),
    INDEX idx_bookstore_chapter_book (book_id),
    CONSTRAINT fk_bookstore_chapter_book FOREIGN KEY (book_id) REFERENCES bookstore_book (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='书城章节正文';
