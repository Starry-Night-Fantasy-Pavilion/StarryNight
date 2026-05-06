package com.starrynight.starrynight.system.monitor.service;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.monitor.dto.CacheKeyEntryDTO;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.cache.Cache;
import org.springframework.cache.CacheManager;
import org.springframework.dao.DataAccessException;
import org.springframework.data.redis.connection.RedisConnection;
import org.springframework.data.redis.connection.RedisConnectionFactory;
import org.springframework.data.redis.core.Cursor;
import org.springframework.data.redis.core.ScanOptions;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;

import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.stream.Collectors;

@Slf4j
@Service
@RequiredArgsConstructor
public class AdminCacheService {

    private static final int MAX_SCAN = 500;
    private static final int PREVIEW_LEN = 800;

    private final CacheManager cacheManager;
    private final RedisConnectionFactory redisConnectionFactory;

    public List<String> listCacheNames() {
        return cacheManager.getCacheNames().stream().sorted().collect(Collectors.toList());
    }

    /**
     * 扫描 Redis 中匹配的 key（Spring Cache 一般为 cacheName::key）
     */
    public List<CacheKeyEntryDTO> scanKeys(String pattern, int limit) {
        String pat = StringUtils.hasText(pattern) ? pattern.trim() : "*";
        int lim = Math.min(Math.max(limit, 1), MAX_SCAN);
        List<CacheKeyEntryDTO> out = new ArrayList<>();
        try {
            RedisConnection conn = redisConnectionFactory.getConnection();
            try (Cursor<byte[]> cursor = conn.scan(ScanOptions.scanOptions().match(pat).count(100).build())) {
                while (cursor.hasNext() && out.size() < lim) {
                    String k = new String(cursor.next(), StandardCharsets.UTF_8);
                    Long ttl = conn.keyCommands().ttl(k.getBytes(StandardCharsets.UTF_8));
                    byte[] raw = conn.stringCommands().get(k.getBytes(StandardCharsets.UTF_8));
                    String preview = preview(raw);
                    out.add(CacheKeyEntryDTO.builder()
                            .key(k)
                            .ttlSeconds(ttl)
                            .valuePreview(preview)
                            .build());
                }
            } finally {
                conn.close();
            }
        } catch (DataAccessException e) {
            log.warn("redis scan failed: {}", e.toString());
            throw new BusinessException("Redis 不可用或扫描失败: " + e.getMessage());
        }
        out.sort(Comparator.comparing(CacheKeyEntryDTO::getKey));
        return out;
    }

    public String previewValue(String fullKey) {
        if (!StringUtils.hasText(fullKey)) {
            throw new BusinessException("key 不能为空");
        }
        try {
            RedisConnection conn = redisConnectionFactory.getConnection();
            try {
                byte[] raw = conn.stringCommands().get(fullKey.getBytes(StandardCharsets.UTF_8));
                return preview(raw);
            } finally {
                conn.close();
            }
        } catch (DataAccessException e) {
            throw new BusinessException("读取失败: " + e.getMessage());
        }
    }

    public void deleteRedisKey(String fullKey) {
        if (!StringUtils.hasText(fullKey)) {
            throw new BusinessException("key 不能为空");
        }
        try {
            RedisConnection conn = redisConnectionFactory.getConnection();
            try {
                conn.keyCommands().del(fullKey.getBytes(StandardCharsets.UTF_8));
            } finally {
                conn.close();
            }
        } catch (DataAccessException e) {
            throw new BusinessException("删除失败: " + e.getMessage());
        }
    }

    public void clearSpringCache(String cacheName) {
        if (!StringUtils.hasText(cacheName)) {
            throw new BusinessException("cacheName 不能为空");
        }
        Cache c = cacheManager.getCache(cacheName.trim());
        if (c == null) {
            throw new BusinessException("未知缓存区: " + cacheName);
        }
        c.clear();
    }

    private static String preview(byte[] raw) {
        if (raw == null) {
            return "(null)";
        }
        String s = new String(raw, StandardCharsets.UTF_8);
        if (s.length() > PREVIEW_LEN) {
            return s.substring(0, PREVIEW_LEN) + "…";
        }
        return s;
    }
}
