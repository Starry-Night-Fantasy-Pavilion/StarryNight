package com.starrynight.starrynight.system.system.service;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.boot.logging.LogLevel;
import org.springframework.boot.logging.LoggingSystem;
import org.springframework.dao.DataAccessException;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;
import org.springframework.util.StringUtils;

import jakarta.annotation.PostConstruct;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Objects;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.CopyOnWriteArrayList;

/**
 * 运行时配置：仅从运营端维护的 {@code system_config} 表读取，不认 YAML/环境变量/其它来源。
 * 使用 {@link JdbcTemplate} 直连查询，避免依赖 MyBatis Mapper，防止与 MyBatis 初始化形成循环依赖。
 * 仅 MySQL JDBC 连接串仍由 {@code application.yml} + 环境变量提供（引导连库）。
 */
@Component("runtimeConfigService")
public class RuntimeConfigService {

    private static final Logger log = LoggerFactory.getLogger(RuntimeConfigService.class);

    private final JdbcTemplate jdbcTemplate;

    private final ConcurrentHashMap<String, String> dbSnapshot = new ConcurrentHashMap<>();

    private final CopyOnWriteArrayList<Runnable> afterReloadHooks = new CopyOnWriteArrayList<>();

    public RuntimeConfigService(JdbcTemplate jdbcTemplate) {
        this.jdbcTemplate = Objects.requireNonNull(jdbcTemplate, "jdbcTemplate");
    }

    @PostConstruct
    public void init() {
        reloadFromDatabase();
    }

    /**
     * 从数据库刷新内存快照；在运营端修改 system_config 后应调用。
     */
    public synchronized void reloadFromDatabase() {
        dbSnapshot.clear();
        try {
            List<Map<String, Object>> rows = jdbcTemplate.queryForList(
                    "SELECT config_key, config_value FROM system_config");
            for (Map<String, Object> row : rows) {
                Object k = row.get("config_key");
                Object v = row.get("config_value");
                if (k != null && v != null) {
                    dbSnapshot.put(k.toString(), v.toString());
                }
            }
        } catch (DataAccessException e) {
            throw new IllegalStateException("无法从 system_config 加载运营端配置", e);
        }
        refreshLogLevelsFromOps();
        runAfterReloadHooks();
    }

    /**
     * 在每次 {@link #reloadFromDatabase()} 成功刷新快照并调整日志级别之后执行（例如 Redis 热切换）。
     */
    public void registerAfterReloadHook(Runnable hook) {
        afterReloadHooks.add(Objects.requireNonNull(hook, "hook"));
    }

    private void runAfterReloadHooks() {
        for (Runnable r : afterReloadHooks) {
            try {
                r.run();
            } catch (Exception e) {
                log.warn("after_reload_hook failed: {}", e.toString());
            }
        }
    }

    private void refreshLogLevelsFromOps() {
        LoggingSystem ls = LoggingSystem.get(RuntimeConfigService.class.getClassLoader());
        if (ls == null) {
            return;
        }
        Map<String, String> mapping = Map.of(
                "logging.level.com.starrynight", "com.starrynight",
                "logging.level.org.springframework", "org.springframework"
        );
        for (Map.Entry<String, String> e : mapping.entrySet()) {
            String raw = dbSnapshot.get(e.getKey());
            if (!StringUtils.hasText(raw)) {
                continue;
            }
            try {
                ls.setLogLevel(e.getValue(), LogLevel.valueOf(raw.trim().toUpperCase(Locale.ROOT)));
            } catch (IllegalArgumentException ignored) {
                // 非法级别则跳过，避免启动失败
            }
        }
    }

    /**
     * 仅返回库中已存在的键；未落库返回 {@code null}（与「值为空字符串」不同：空串会入库并在此返回）。
     */
    public String getProperty(String key) {
        return dbSnapshot.get(key);
    }

    public String getString(String key, String defaultValue) {
        String v = getProperty(key);
        return StringUtils.hasText(v) ? v.trim() : defaultValue;
    }

    public long getLong(String key, long defaultValue) {
        String v = getProperty(key);
        if (!StringUtils.hasText(v)) {
            return defaultValue;
        }
        try {
            return Long.parseLong(v.trim());
        } catch (NumberFormatException e) {
            return defaultValue;
        }
    }

    public int getInt(String key, int defaultValue) {
        String v = getProperty(key);
        if (!StringUtils.hasText(v)) {
            return defaultValue;
        }
        try {
            return Integer.parseInt(v.trim());
        } catch (NumberFormatException e) {
            return defaultValue;
        }
    }

    public double getDouble(String key, double defaultValue) {
        String v = getProperty(key);
        if (!StringUtils.hasText(v)) {
            return defaultValue;
        }
        try {
            return Double.parseDouble(v.trim());
        } catch (NumberFormatException e) {
            return defaultValue;
        }
    }

    public boolean getBoolean(String key, boolean defaultValue) {
        String v = getProperty(key);
        if (!StringUtils.hasText(v)) {
            return defaultValue;
        }
        return Boolean.parseBoolean(v.trim());
    }
}
