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
 * 旧库缺少 {@code community_report} 时自动建表。
 */
@Slf4j
@Component
@DependsOn("dataSourceScriptDatabaseInitializer")
@RequiredArgsConstructor
public class CommunityReportSchemaCompat {

    private final JdbcTemplate jdbcTemplate;

    @PostConstruct
    public void ensureCommunityReportTable() {
        try {
            if (tableExists("community_report")) {
                return;
            }
            jdbcTemplate.execute(
                    "CREATE TABLE IF NOT EXISTS community_report ("
                            + " id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '举报ID',"
                            + " kind VARCHAR(10) NOT NULL COMMENT 'POST/COMMENT',"
                            + " post_id BIGINT NOT NULL COMMENT '帖子ID',"
                            + " comment_id BIGINT NULL COMMENT '评论ID',"
                            + " target_user_id BIGINT NOT NULL COMMENT '被举报作者',"
                            + " reporter_user_id BIGINT NOT NULL COMMENT '举报人',"
                            + " reason VARCHAR(50) NOT NULL COMMENT '原因',"
                            + " detail VARCHAR(500) NULL COMMENT '说明',"
                            + " status TINYINT NOT NULL DEFAULT 0 COMMENT '0待处理1已处理2已忽略',"
                            + " handle_action VARCHAR(30) NULL COMMENT '动作',"
                            + " handle_note VARCHAR(500) NULL COMMENT '处理备注',"
                            + " handled_by BIGINT NULL COMMENT '处理人',"
                            + " handled_time DATETIME NULL COMMENT '处理时间',"
                            + " create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,"
                            + " update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,"
                            + " deleted TINYINT NOT NULL DEFAULT 0,"
                            + " INDEX idx_status (status),"
                            + " INDEX idx_post_id (post_id),"
                            + " INDEX idx_reporter_user_id (reporter_user_id),"
                            + " INDEX idx_target_user_id (target_user_id)"
                            + ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='社区举报表'");
            log.info("community_report 表已自动创建");
        } catch (DataAccessException e) {
            log.warn("community_report 表兼容未执行: {}", e.getMessage(), e);
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

