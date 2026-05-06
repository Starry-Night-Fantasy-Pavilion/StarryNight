-- 已有库执行一次：书城章节表（与 novel_category_bookstore.sql 末尾定义一致）
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
