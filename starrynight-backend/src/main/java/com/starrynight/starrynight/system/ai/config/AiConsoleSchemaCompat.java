package com.starrynight.starrynight.system.ai.config;

import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.context.annotation.DependsOn;
import org.springframework.dao.DataAccessException;
import org.springframework.jdbc.core.ConnectionCallback;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;

import jakarta.annotation.PostConstruct;
import java.sql.DatabaseMetaData;
import java.sql.ResultSet;

/**
 * 旧库缺少 {@code ai_model.billing_channel_id}、{@code ai_template} 表时，在启动后自动补齐，避免运营端 AI 配置页报错。
 */
@Slf4j
@Component
@DependsOn("dataSourceScriptDatabaseInitializer")
@RequiredArgsConstructor
public class AiConsoleSchemaCompat {

    private final JdbcTemplate jdbcTemplate;

    @PostConstruct
    public void ensureAiConsoleSchema() {
        try {
            ensureAiModelBillingChannelColumn();
            ensureAiModelBillingChannelIndex();
            normalizeAiModelTypes();
            ensureAiTemplateTable();
        } catch (DataAccessException e) {
            log.warn("AI 控制台表结构兼容未执行: {}", e.getMessage(), e);
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

    private boolean indexExists(String tableName, String indexName) {
        Long cnt = jdbcTemplate.queryForObject(
                "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() "
                        + "AND TABLE_NAME = ? AND INDEX_NAME = ?",
                Long.class,
                tableName,
                indexName);
        return cnt != null && cnt > 0L;
    }

    private void ensureAiModelBillingChannelColumn() {
        if (!tableExists("ai_model")) {
            return;
        }
        addColumnIfMissing(
                "ai_model",
                "billing_channel_id",
                "BIGINT NULL COMMENT '计费渠道 billing_channel.id'");
    }

    private void ensureAiModelBillingChannelIndex() {
        if (!tableExists("ai_model")) {
            return;
        }
        if (!indexExists("ai_model", "idx_ai_model_billing_channel_id")) {
            jdbcTemplate.execute("CREATE INDEX idx_ai_model_billing_channel_id ON ai_model (billing_channel_id)");
            log.info("ai_model 已创建索引 idx_ai_model_billing_channel_id");
        }
    }

    private void normalizeAiModelTypes() {
        if (!tableExists("ai_model")) {
            return;
        }
        jdbcTemplate.update(
                "UPDATE ai_model SET model_type = 'default' WHERE model_type IN ('outline', 'content', 'chat') "
                        + "OR model_type IS NULL OR model_type = ''");
    }

    private void ensureAiTemplateTable() {
        jdbcTemplate.execute(
                """
                CREATE TABLE IF NOT EXISTS ai_template (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT '模板ID',
                    name VARCHAR(200) NOT NULL COMMENT '模板名称',
                    type VARCHAR(50) NOT NULL COMMENT '类型',
                    description VARCHAR(500) COMMENT '描述',
                    content MEDIUMTEXT NOT NULL COMMENT '模板正文',
                    enabled TINYINT NOT NULL DEFAULT 1 COMMENT '是否启用',
                    usage_count INT NOT NULL DEFAULT 0 COMMENT '使用次数',
                    create_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                    update_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
                    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记',
                    INDEX idx_ai_template_type (type),
                    INDEX idx_ai_template_enabled (enabled)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI提示模板表'
                """);
    }
}
