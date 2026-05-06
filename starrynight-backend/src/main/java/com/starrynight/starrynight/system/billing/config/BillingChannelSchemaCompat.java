package com.starrynight.starrynight.system.billing.config;

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
 * 旧库 billing_channel 缺 api_base_url 等列时，CREATE IF NOT EXISTS 不会升级表结构；启动后按需 ALTER 补齐。
 * <p>说明：MySQL 对 COUNT(*) 常映射为 Long，用 Integer 接收会抛错并被吞掉，导致从未执行 ALTER。</p>
 */
@Slf4j
@Component
@DependsOn("dataSourceScriptDatabaseInitializer")
@RequiredArgsConstructor
public class BillingChannelSchemaCompat {

    private final JdbcTemplate jdbcTemplate;

    @PostConstruct
    public void ensureBillingChannelColumns() {
        try {
            if (!tableExists("billing_channel")) {
                return;
            }
            addColumnIfMissing("api_base_url", "VARCHAR(500) NULL COMMENT 'API 基地址'");
            addColumnIfMissing("api_key", "VARCHAR(500) NULL COMMENT 'API 密钥'");
            addColumnIfMissing("model_name", "VARCHAR(200) NULL COMMENT '模型名称'");
        } catch (DataAccessException e) {
            log.warn("billing_channel 结构兼容检查未执行: {}", e.getMessage(), e);
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

    private void addColumnIfMissing(String columnName, String columnDefinition) {
        Long cnt = jdbcTemplate.queryForObject(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() "
                        + "AND TABLE_NAME = 'billing_channel' AND COLUMN_NAME = ?",
                Long.class,
                columnName);
        if (cnt != null && cnt == 0L) {
            jdbcTemplate.execute(
                    "ALTER TABLE billing_channel ADD COLUMN `" + columnName + "` " + columnDefinition);
            log.info("billing_channel 已补充列 {}", columnName);
        }
    }
}
