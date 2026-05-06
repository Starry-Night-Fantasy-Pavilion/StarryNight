-- ============================================
-- 向量节点 / 向量集合 / 运营推荐（与 VectorNode、VectorCollection、Recommendation 实体一致）
-- ============================================

CREATE TABLE IF NOT EXISTS t_vector_node (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '主键',
    name VARCHAR(200) NULL COMMENT '节点名称',
    host VARCHAR(255) NULL COMMENT '主机',
    port INT NULL COMMENT '端口',
    api_key VARCHAR(500) NULL COMMENT 'API 密钥',
    max_vectors INT NULL COMMENT '最大向量数',
    max_storage INT NULL COMMENT '最大存储(MB等)',
    status VARCHAR(50) NULL COMMENT '状态',
    enabled TINYINT NULL DEFAULT 1 COMMENT '是否启用: 0-否, 1-是',
    create_time DATETIME NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='向量库节点';

CREATE TABLE IF NOT EXISTS t_vector_collection (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '主键',
    name VARCHAR(200) NOT NULL COMMENT '集合名称',
    type VARCHAR(50) NULL COMMENT '类型',
    vector_count INT NULL DEFAULT 0 COMMENT '向量数量',
    dimension INT NULL COMMENT '维度',
    embedding_model VARCHAR(100) NULL COMMENT '嵌入模型',
    distance VARCHAR(50) NULL COMMENT '距离度量',
    max_vectors INT NULL COMMENT '最大向量数',
    status VARCHAR(50) NULL COMMENT '状态',
    create_time DATETIME NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='向量集合';

CREATE TABLE IF NOT EXISTS t_recommendation (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '主键',
    title VARCHAR(200) NOT NULL COMMENT '标题',
    type VARCHAR(50) NULL COMMENT '推荐类型',
    novel_id BIGINT NULL COMMENT '作品ID',
    novel_title VARCHAR(200) NULL COMMENT '作品标题',
    cover VARCHAR(500) NULL COMMENT '封面 URL',
    position VARCHAR(50) NULL COMMENT '展示位置',
    sort INT NULL DEFAULT 0 COMMENT '排序',
    start_time DATETIME NULL COMMENT '开始时间',
    end_time DATETIME NULL COMMENT '结束时间',
    status TINYINT NULL DEFAULT 1 COMMENT '状态',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记: 0-未删除, 1-已删除',
    INDEX idx_novel_id (novel_id),
    INDEX idx_status (status),
    INDEX idx_deleted (deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='运营推荐位';
