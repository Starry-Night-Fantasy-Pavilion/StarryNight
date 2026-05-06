package com.starrynight.starrynight.system.bookstore.config;

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
 * 旧库 {@code bookstore_book} 缺书源列时自动补齐（与 {@code novel_category_bookstore.sql} / patch 一致）。
 */
@Slf4j
@Component
@DependsOn("dataSourceScriptDatabaseInitializer")
@RequiredArgsConstructor
public class BookstoreBookSchemaCompat {

    private final JdbcTemplate jdbcTemplate;

    @PostConstruct
    public void ensureBookstoreBookSourceColumns() {
        try {
            if (!tableExists("bookstore_book")) {
                return;
            }
            addColumnIfMissing(
                    "bookstore_book",
                    "source_url",
                    "VARCHAR(2000) NULL COMMENT '书源详情或目录页 URL（对接外部解析用）' AFTER `tags`");
            addColumnIfMissing(
                    "bookstore_book",
                    "source_json",
                    "MEDIUMTEXT NULL COMMENT '书源规则 JSON（Legado/自定义引擎等）' AFTER `source_url`");
        } catch (DataAccessException e) {
            log.warn("bookstore_book 书源列兼容未执行: {}", e.getMessage(), e);
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

    private boolean addColumnIfMissing(String tableName, String columnName, String columnDefinition) {
        Long cnt = jdbcTemplate.queryForObject(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() "
                        + "AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                Long.class,
                tableName,
                columnName);
        if (cnt != null && cnt == 0L) {
            jdbcTemplate.execute("ALTER TABLE `" + tableName + "` ADD COLUMN `" + columnName + "` " + columnDefinition);
            log.info("{} 已补充列 {}", tableName, columnName);
            return true;
        }
        return false;
    }
}
