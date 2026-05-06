package com.starrynight.starrynight.system.community.config;

import jakarta.annotation.PostConstruct;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.context.annotation.DependsOn;
import org.springframework.dao.DataAccessException;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;

/**
 * 旧库 {@code community_comment} 缺少审核列时自动补齐。
 */
@Slf4j
@Component
@DependsOn("dataSourceScriptDatabaseInitializer")
@RequiredArgsConstructor
public class CommentAuditSchemaCompat {

    private final JdbcTemplate jdbcTemplate;

    @PostConstruct
    public void ensureCommentAuditColumns() {
        try {
            addColumnIfMissing(
                    "community_comment",
                    "audit_status",
                    "TINYINT NOT NULL DEFAULT 1 COMMENT '0待审1通过2驳回' AFTER `content`");
            addColumnIfMissing(
                    "community_comment",
                    "moderation_note",
                    "VARCHAR(500) NULL COMMENT '审核备注' AFTER `audit_status`");
        } catch (DataAccessException e) {
            log.warn("community_comment 审核列兼容未执行: {}", e.getMessage(), e);
        }
    }

    private void addColumnIfMissing(String tableName, String columnName, String columnDefinition) {
        Long cnt = jdbcTemplate.queryForObject(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() "
                        + "AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                Long.class,
                tableName,
                columnName);
        if (cnt != null && cnt == 0L) {
            jdbcTemplate.execute("ALTER TABLE `" + tableName + "` ADD COLUMN `" + columnName + "` " + columnDefinition);
            log.info("{} 已补充列 {}", tableName, columnName);
        }
    }
}
