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
 * 旧库缺少 {@code community_comment} / {@code community_post_like} 时自动建表。
 */
@Slf4j
@Component
@DependsOn("dataSourceScriptDatabaseInitializer")
@RequiredArgsConstructor
public class CommunityInteractionSchemaCompat {

    private final JdbcTemplate jdbcTemplate;

    @PostConstruct
    public void ensureInteractionTables() {
        try {
            if (!tableExists("community_comment")) {
                jdbcTemplate.execute(
                        "CREATE TABLE IF NOT EXISTS community_comment ("
                                + " id BIGINT AUTO_INCREMENT PRIMARY KEY,"
                                + " post_id BIGINT NOT NULL,"
                                + " user_id BIGINT NOT NULL,"
                                + " parent_id BIGINT NULL,"
                                + " content VARCHAR(2000) NOT NULL,"
                                + " audit_status TINYINT NOT NULL DEFAULT 1,"
                                + " moderation_note VARCHAR(500) NULL,"
                                + " create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,"
                                + " update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,"
                                + " deleted TINYINT NOT NULL DEFAULT 0,"
                                + " INDEX idx_post_id (post_id),"
                                + " INDEX idx_parent_id (parent_id),"
                                + " INDEX idx_user_id (user_id),"
                                + " INDEX idx_audit_status (audit_status)"
                                + ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区评论表'");
                log.info("community_comment 表已自动创建");
            }
            if (!tableExists("community_post_like")) {
                jdbcTemplate.execute(
                        "CREATE TABLE IF NOT EXISTS community_post_like ("
                                + " id BIGINT AUTO_INCREMENT PRIMARY KEY,"
                                + " post_id BIGINT NOT NULL,"
                                + " user_id BIGINT NOT NULL,"
                                + " create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,"
                                + " UNIQUE KEY uk_post_user (post_id, user_id),"
                                + " INDEX idx_user_id (user_id)"
                                + ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区帖子点赞'");
                log.info("community_post_like 表已自动创建");
            }
        } catch (DataAccessException e) {
            log.warn("社区互动表兼容未执行: {}", e.getMessage(), e);
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
