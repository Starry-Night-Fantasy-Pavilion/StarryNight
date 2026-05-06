-- 用户表
CREATE TABLE IF NOT EXISTS auth_user (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '用户ID',
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    password VARCHAR(255) NOT NULL COMMENT '密码',
    email VARCHAR(100) COMMENT '邮箱',
    phone VARCHAR(20) COMMENT '手机号',
    real_name VARCHAR(32) COMMENT '真实姓名（实名注册）',
    id_card_no VARCHAR(32) COMMENT '证件号（实名注册）',
    real_name_verified TINYINT NOT NULL DEFAULT 0 COMMENT '实名核验是否通过：0 否 1 是',
    real_name_verify_outer_no VARCHAR(80) NULL COMMENT '最近一次核验外部单号/流水',
    realname_fee_paid_record_no VARCHAR(64) NULL COMMENT '易支付实名认证费已付订单号（recharge_record.record_no，pay_method=REALNAME_FEE）',
    avatar VARCHAR(500) COMMENT '头像URL',
    register_ip VARCHAR(45) COMMENT '首次注册IP',
    last_login_time DATETIME COMMENT '最后登录时间',
    last_login_ip VARCHAR(45) COMMENT '最后登录IP',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '状态: 1-正常, 0-禁用',
    is_admin TINYINT NOT NULL DEFAULT 0 COMMENT '历史字段（兼容）：是否管理员',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记: 0-未删除, 1-已删除',
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_is_admin (is_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- 第三方 OAuth 与站内用户绑定（如 LINUX DO Connect）
CREATE TABLE IF NOT EXISTS auth_oauth_link (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '主键',
    provider VARCHAR(32) NOT NULL COMMENT '渠道，如 LINUXDO',
    external_id VARCHAR(64) NOT NULL COMMENT '外部用户唯一标识',
    user_id BIGINT NOT NULL COMMENT 'auth_user.id',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '绑定时间',
    UNIQUE KEY uk_oauth_provider_external (provider, external_id),
    INDEX idx_oauth_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='第三方 OAuth 与站内用户绑定';

-- 运营端账号表（与用户端账号完全隔离）
CREATE TABLE IF NOT EXISTS ops_account (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '运营账号ID',
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '运营账号名',
    email VARCHAR(100) NULL COMMENT '邮箱（可选，可与用户名二选一登录，唯一）',
    password VARCHAR(255) NOT NULL COMMENT '密码（BCrypt）',
    role_id BIGINT NOT NULL COMMENT '角色ID（admin_role.id）',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '状态: 1-正常, 0-禁用',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记: 0-未删除, 1-已删除',
    INDEX idx_ops_username (username),
    UNIQUE INDEX uk_ops_account_email (email),
    INDEX idx_ops_role_id (role_id),
    INDEX idx_ops_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='运营端账号表';

-- 用户详情表
CREATE TABLE IF NOT EXISTS user_profile (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '主键ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    nickname VARCHAR(50) COMMENT '昵称',
    bio TEXT COMMENT '个人简介',
    gender TINYINT COMMENT '性别: 0-未知, 1-男, 2-女',
    birthday DATE COMMENT '生日',
    location VARCHAR(100) COMMENT '所在地',
    website VARCHAR(200) COMMENT '个人网站',
    member_level TINYINT NOT NULL DEFAULT 1 COMMENT '会员等级: 1-普通, 2-VIP, 3-高级VIP',
    member_expire_time DATETIME COMMENT '会员过期时间',
    points INT NOT NULL DEFAULT 0 COMMENT '积分',
    total_word_count BIGINT DEFAULT 0 COMMENT '累计创作字数',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    UNIQUE KEY uk_user_id (user_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户详情表';

-- 作品表
CREATE TABLE IF NOT EXISTS novel (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '作品ID',
    user_id BIGINT NOT NULL COMMENT '作者ID',
    title VARCHAR(200) NOT NULL COMMENT '作品标题',
    subtitle VARCHAR(200) COMMENT '副标题',
    cover VARCHAR(500) COMMENT '封面URL',
    category_id BIGINT COMMENT '分类ID',
    genre VARCHAR(50) COMMENT '题材',
    style VARCHAR(50) COMMENT '风格',
    synopsis TEXT COMMENT '简介',
    word_count INT NOT NULL DEFAULT 0 COMMENT '字数',
    chapter_count INT NOT NULL DEFAULT 0 COMMENT '章节数',
    status TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0-创作中, 1-已完结',
    audit_status TINYINT NOT NULL DEFAULT 0 COMMENT '审核状态: 0-待审核, 1-通过, 2-拒绝',
    is_published TINYINT NOT NULL DEFAULT 0 COMMENT '是否发布: 0-草稿, 1-已发布',
    is_deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记: 0-未删除, 1-已删除',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    publish_time DATETIME COMMENT '发布时间',
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_status (status),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='作品表';

-- 卷表
CREATE TABLE IF NOT EXISTS novel_volume (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '卷ID',
    novel_id BIGINT NOT NULL COMMENT '作品ID',
    title VARCHAR(200) NOT NULL COMMENT '卷标题',
    description TEXT COMMENT '卷简介',
    volume_order INT NOT NULL DEFAULT 0 COMMENT '卷序号',
    chapter_count INT NOT NULL DEFAULT 0 COMMENT '章节数',
    word_count INT NOT NULL DEFAULT 0 COMMENT '字数',
    status TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0-草稿, 1-已完成',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_novel_id (novel_id),
    INDEX idx_volume_order (volume_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='卷表';

-- 章节表
CREATE TABLE IF NOT EXISTS novel_chapter (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '章节ID',
    novel_id BIGINT NOT NULL COMMENT '作品ID',
    volume_id BIGINT COMMENT '卷ID',
    title VARCHAR(200) NOT NULL COMMENT '章节标题',
    content LONGTEXT COMMENT '正文内容',
    outline TEXT COMMENT '章节大纲',
    chapter_order INT NOT NULL DEFAULT 0 COMMENT '章节序号',
    word_count INT NOT NULL DEFAULT 0 COMMENT '字数',
    status TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0-草稿, 1-已发布',
    version INT NOT NULL DEFAULT 1 COMMENT '版本号',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    publish_time DATETIME COMMENT '发布时间',
    INDEX idx_novel_id (novel_id),
    INDEX idx_volume_id (volume_id),
    INDEX idx_chapter_order (chapter_order),
    FULLTEXT idx_content (content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='章节表';

-- 大纲表
CREATE TABLE IF NOT EXISTS novel_outline (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '大纲ID',
    novel_id BIGINT NOT NULL COMMENT '作品ID',
    volume_id BIGINT COMMENT '卷ID',
    chapter_id BIGINT COMMENT '章节ID',
    type VARCHAR(20) NOT NULL COMMENT '类型: outline-大纲, volume_outline-卷纲, chapter_outline-细纲',
    title VARCHAR(200) NOT NULL COMMENT '标题',
    content TEXT COMMENT '内容',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '排序',
    parent_id BIGINT COMMENT '父级ID',
    version INT NOT NULL DEFAULT 1 COMMENT '版本号',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_novel_id (novel_id),
    INDEX idx_volume_id (volume_id),
    INDEX idx_chapter_id (chapter_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='大纲表';

-- 章节版本历史表
CREATE TABLE IF NOT EXISTS chapter_version (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '版本ID',
    chapter_id BIGINT NOT NULL COMMENT '章节ID',
    content LONGTEXT COMMENT '内容快照',
    word_count INT NOT NULL DEFAULT 0 COMMENT '字数',
    version INT NOT NULL COMMENT '版本号',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_chapter_id (chapter_id),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='章节版本历史表';

-- 知识库表
CREATE TABLE IF NOT EXISTS knowledge_library (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '知识库ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    name VARCHAR(100) NOT NULL COMMENT '知识库名称',
    type VARCHAR(20) NOT NULL DEFAULT 'custom' COMMENT '类型: canon-官方正史, reference-参考资料, material-素材, custom-自定义',
    description TEXT COMMENT '描述',
    tags VARCHAR(500) COMMENT '标签(逗号分隔)',
    file_url VARCHAR(500) COMMENT '文件URL',
    file_type VARCHAR(20) COMMENT '文件类型',
    file_size BIGINT DEFAULT 0 COMMENT '文件大小(字节)',
    document_count INT NOT NULL DEFAULT 0 COMMENT '文档数',
    chunk_count INT NOT NULL DEFAULT 0 COMMENT '切片数',
    status VARCHAR(20) NOT NULL DEFAULT 'PROCESSING' COMMENT '状态: PROCESSING-处理中, READY-就绪, ERROR-失败',
    error_message TEXT COMMENT '错误信息',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库表';

-- 知识库文档切片表
CREATE TABLE IF NOT EXISTS knowledge_chunk (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '切片ID',
    library_id BIGINT NOT NULL COMMENT '知识库ID',
    content TEXT NOT NULL COMMENT '切片内容',
    content_hash VARCHAR(64) COMMENT '内容哈希',
    chunk_order INT NOT NULL DEFAULT 0 COMMENT '切片序号',
    token_count INT DEFAULT 0 COMMENT 'Token数',
    vector_id VARCHAR(64) COMMENT '向量ID',
    metadata JSON COMMENT '元数据',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_library_id (library_id),
    INDEX idx_content_hash (content_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库文档切片表';

-- 角色表
CREATE TABLE IF NOT EXISTS novel_character (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '角色ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    novel_id BIGINT COMMENT '所属作品ID',
    name VARCHAR(50) NOT NULL COMMENT '角色名称',
    identity VARCHAR(100) COMMENT '身份定位',
    gender VARCHAR(10) COMMENT '性别',
    age VARCHAR(20) COMMENT '年龄',
    appearance TEXT COMMENT '外貌描述',
    background TEXT COMMENT '背景故事',
    motivation TEXT COMMENT '行为动机',
    personality JSON COMMENT '性格特征(含traits/mbti等)',
    abilities JSON COMMENT '能力设定(含level/skills等)',
    relationships JSON COMMENT '关系图谱',
    growth_arc JSON COMMENT '成长弧光',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '排序',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_user_id (user_id),
    INDEX idx_novel_id (novel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- 素材表
CREATE TABLE IF NOT EXISTS material_item (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '素材ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    novel_id BIGINT COMMENT '所属作品ID',
    title VARCHAR(100) NOT NULL COMMENT '素材标题',
    type VARCHAR(30) NOT NULL COMMENT '类型: golden_finger/worldview/character_draft/conflict_idea/style_fingerprint/custom',
    description TEXT COMMENT '描述',
    content JSON COMMENT '素材内容',
    tags VARCHAR(500) COMMENT '标签(逗号分隔)',
    source VARCHAR(30) DEFAULT 'user_created' COMMENT '来源: tool_generated/user_created/imported',
    source_tool VARCHAR(50) COMMENT '来源工具名',
    usage_count INT NOT NULL DEFAULT 0 COMMENT '使用次数',
    last_used_at DATETIME COMMENT '最后使用时间',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_user_id (user_id),
    INDEX idx_novel_id (novel_id),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='素材表';

-- 提示词模板表
CREATE TABLE IF NOT EXISTS prompt_template (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '模板ID',
    user_id BIGINT COMMENT '用户ID(NULL为内置模板)',
    name VARCHAR(100) NOT NULL COMMENT '模板名称',
    category VARCHAR(30) NOT NULL COMMENT '分类: 爆款开头/大纲结构/人物设定/冲突制造/描写润色/自定义',
    description TEXT COMMENT '描述',
    prompt_template TEXT NOT NULL COMMENT '提示词模板',
    variables JSON COMMENT '变量定义',
    output_format VARCHAR(20) DEFAULT 'text' COMMENT '输出格式: text/json/markdown',
    is_builtin TINYINT NOT NULL DEFAULT 0 COMMENT '是否内置: 0-用户创建, 1-系统内置',
    version INT NOT NULL DEFAULT 1 COMMENT '版本号',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_user_id (user_id),
    INDEX idx_category (category),
    INDEX idx_is_builtin (is_builtin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提示词模板表';

-- 风格样本表
CREATE TABLE IF NOT EXISTS style_sample (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '样本ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    name VARCHAR(100) NOT NULL COMMENT '样本名称',
    content TEXT NOT NULL COMMENT '样本内容',
    style_label VARCHAR(100) COMMENT '风格标签',
    style_fingerprint JSON COMMENT '风格指纹(分析结果)',
    word_count INT DEFAULT 0 COMMENT '字数',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='风格样本表';

-- 社区帖子表
CREATE TABLE IF NOT EXISTS community_post (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '帖子ID',
    user_id BIGINT NOT NULL COMMENT '发布者 auth_user.id',
    title VARCHAR(200) NULL COMMENT '标题（可选）',
    content TEXT NOT NULL COMMENT '正文',
    content_type VARCHAR(20) NOT NULL DEFAULT 'text' COMMENT '类型: text 等',
    topic_id BIGINT NULL COMMENT '话题ID（预留）',
    audit_status TINYINT NOT NULL DEFAULT 0 COMMENT '审核: 0待审 1通过 2驳回',
    reject_reason VARCHAR(500) NULL COMMENT '驳回原因',
    like_count INT NOT NULL DEFAULT 0 COMMENT '点赞数',
    comment_count INT NOT NULL DEFAULT 0 COMMENT '评论数',
    view_count INT NOT NULL DEFAULT 0 COMMENT '浏览数',
    online_status TINYINT NOT NULL DEFAULT 1 COMMENT '上架: 1展示 0运营下架',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_user_id (user_id),
    INDEX idx_audit_status (audit_status),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区帖子表';

-- 社区评论
CREATE TABLE IF NOT EXISTS community_comment (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '评论ID',
    post_id BIGINT NOT NULL COMMENT '帖子 community_post.id',
    user_id BIGINT NOT NULL COMMENT '评论者 auth_user.id',
    parent_id BIGINT NULL COMMENT '父评论ID',
    content VARCHAR(2000) NOT NULL COMMENT '内容',
    audit_status TINYINT NOT NULL DEFAULT 1 COMMENT '0待审 1通过 2驳回',
    moderation_note VARCHAR(500) NULL COMMENT '审核备注',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
    INDEX idx_post_id (post_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_user_id (user_id),
    INDEX idx_audit_status (audit_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区评论表';

-- 社区帖子点赞
CREATE TABLE IF NOT EXISTS community_post_like (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '主键',
    post_id BIGINT NOT NULL COMMENT '帖子ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    UNIQUE KEY uk_post_user (post_id, user_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区帖子点赞';
