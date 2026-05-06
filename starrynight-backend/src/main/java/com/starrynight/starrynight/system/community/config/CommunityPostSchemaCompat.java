package com.starrynight.starrynight.system.community.config;

import jakarta.annotation.PostConstruct;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.context.annotation.DependsOn;
import org.springframework.dao.DataAccessException;
import org.springframework.jdbc.core.ConnectionCallback;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;

import java.sql.DatabaseMetaData;
import java.sql.ResultSet;

/**
 * 旧库缺少 {@code community_post} 时自动建表（与 patch_community_post.sql / schema.sql 一致）。
 */
@Slf4j
@Component
@DependsOn("dataSourceScriptDatabaseInitializer")
@RequiredArgsConstructor
public class CommunityPostSchemaCompat {

    private final JdbcTemplate jdbcTemplate;

    @PostConstruct
    public void ensureCommunityPostTable() {
        try {
            if (tableExists("community_post")) {
                return;
            }
            jdbcTemplate.execute(
                    "CREATE TABLE IF NOT EXISTS community_post ("
                            + " id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '帖子ID',"
                            + " user_id BIGINT NOT NULL COMMENT '发布者 auth_user.id',"
                            + " title VARCHAR(200) NULL COMMENT '标题',"
                            + " content TEXT NOT NULL COMMENT '正文',"
                            + " content_type VARCHAR(20) NOT NULL DEFAULT 'text' COMMENT '类型',"
                            + " topic_id BIGINT NULL COMMENT '话题ID',"
                            + " audit_status TINYINT NOT NULL DEFAULT 0 COMMENT '审核 0待审1通过2驳回',"
                            + " reject_reason VARCHAR(500) NULL COMMENT '驳回原因',"
                            + " like_count INT NOT NULL DEFAULT 0,"
                            + " comment_count INT NOT NULL DEFAULT 0,"
                            + " view_count INT NOT NULL DEFAULT 0,"
                            + " online_status TINYINT NOT NULL DEFAULT 1 COMMENT '1展示0下架',"
                            + " create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,"
                            + " update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,"
                            + " deleted TINYINT NOT NULL DEFAULT 0,"
                            + " INDEX idx_user_id (user_id),"
                            + " INDEX idx_audit_status (audit_status),"
                            + " INDEX idx_create_time (create_time)"
                            + ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区帖子表'");
            log.info("community_post 表已自动创建");
        } catch (DataAccessException e) {
            log.warn("community_post 表兼容未执行: {}", e.getMessage(), e);
        }
    }

    private boolean tableExists(String table) {
        return Boolean.TRUE.equals(jdbcTemplate.execute((ConnectionCallback<Boolean>) con -> {
            DatabaseMetaData md = con.getMetaData();
            try (ResultSet rs = md.getTables(con.getCatalog(), null, table, new String[] { "TABLE" })) {
                return rs.next();
            }
        }));
    }
}
