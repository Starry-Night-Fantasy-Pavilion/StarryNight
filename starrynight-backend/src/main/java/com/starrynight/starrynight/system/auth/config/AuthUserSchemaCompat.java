package com.starrynight.starrynight.system.auth.config;

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
 * 旧库 {@code auth_user} 缺实名、登录审计等列时自动补齐（与 {@code schema.sql} / patch 脚本一致）。
 */
@Slf4j
@Component
@DependsOn("dataSourceScriptDatabaseInitializer")
@RequiredArgsConstructor
public class AuthUserSchemaCompat {

    private final JdbcTemplate jdbcTemplate;

    @PostConstruct
    public void ensureAuthUserColumns() {
        try {
            if (!tableExists("auth_user")) {
                return;
            }
            addColumnIfMissing("auth_user", "real_name", "VARCHAR(32) NULL COMMENT '真实姓名（实名注册）'");
            addColumnIfMissing("auth_user", "id_card_no", "VARCHAR(32) NULL COMMENT '证件号（实名注册）'");
            boolean addedVerified = addColumnIfMissing(
                    "auth_user",
                    "real_name_verified",
                    "TINYINT NOT NULL DEFAULT 0 COMMENT '实名核验是否通过：0 否 1 是'");
            addColumnIfMissing(
                    "auth_user",
                    "real_name_verify_outer_no",
                    "VARCHAR(80) NULL COMMENT '最近一次核验外部单号/流水'");
            addColumnIfMissing(
                    "auth_user",
                    "realname_fee_paid_record_no",
                    "VARCHAR(64) NULL COMMENT '易支付实名认证费已付订单号（recharge_record.record_no）'");
            addColumnIfMissing("auth_user", "register_ip", "VARCHAR(45) NULL COMMENT '首次注册IP'");
            addColumnIfMissing("auth_user", "last_login_time", "DATETIME NULL COMMENT '最后登录时间'");
            addColumnIfMissing("auth_user", "last_login_ip", "VARCHAR(45) NULL COMMENT '最后登录IP'");

            if (addedVerified) {
                backfillRealNameVerifiedFromLegacyFields();
            }
        } catch (DataAccessException e) {
            log.warn("auth_user 结构兼容未执行: {}", e.getMessage(), e);
        }
    }

    private void backfillRealNameVerifiedFromLegacyFields() {
        int n = jdbcTemplate.update(
                """
                UPDATE auth_user SET real_name_verified = 1
                WHERE deleted = 0
                  AND real_name_verified = 0
                  AND real_name IS NOT NULL AND TRIM(real_name) <> ''
                  AND id_card_no IS NOT NULL AND TRIM(id_card_no) <> ''
                """);
        if (n > 0) {
            log.info("auth_user 已按历史姓名+证件号回填 real_name_verified，影响 {} 行", n);
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
