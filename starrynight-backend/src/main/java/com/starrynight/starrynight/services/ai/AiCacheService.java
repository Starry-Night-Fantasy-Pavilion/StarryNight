package com.starrynight.starrynight.services.ai;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.data.redis.core.StringRedisTemplate;
import org.springframework.stereotype.Service;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.time.Duration;
import java.util.Optional;

@Service
public class AiCacheService {

    private static final String CACHE_PREFIX = "ai_cache:";

    @Autowired
    private StringRedisTemplate redisTemplate;

    @Autowired
    private ObjectMapper objectMapper;

    @Autowired
    private RuntimeConfigService runtimeConfigService;

    public <T> Optional<T> get(String type, Object params, Class<T> responseType) {
        String key = generateKey(type, params);
        try {
            String cached = redisTemplate.opsForValue().get(key);
            if (cached != null) {
                return Optional.of(objectMapper.readValue(cached, responseType));
            }
        } catch (JsonProcessingException e) {
            // Cache miss or invalid JSON
        }
        return Optional.empty();
    }

    public <T> void put(String type, Object params, T result) {
        String key = generateKey(type, params);
        try {
            String json = objectMapper.writeValueAsString(result);
            long ttl = runtimeConfigService.getLong("ai.cache.ttl-seconds", 3600L);
            redisTemplate.opsForValue().set(key, json, Duration.ofSeconds(ttl));
        } catch (JsonProcessingException e) {
            // Skip caching on serialization error
        }
    }

    public void invalidate(String type, Object params) {
        String key = generateKey(type, params);
        redisTemplate.delete(key);
    }

    public void invalidateByType(String type) {
        String pattern = CACHE_PREFIX + type + ":*";
        var keys = redisTemplate.keys(pattern);
        if (keys != null && !keys.isEmpty()) {
            redisTemplate.delete(keys);
        }
    }

    private String generateKey(String type, Object params) {
        try {
            String paramsJson = objectMapper.writeValueAsString(params);
            MessageDigest digest = MessageDigest.getInstance("SHA-256");
            byte[] hash = digest.digest((type + ":" + paramsJson).getBytes(StandardCharsets.UTF_8));
            StringBuilder hexString = new StringBuilder();
            for (byte b : hash) {
                String hex = Integer.toHexString(0xff & b);
                if (hex.length() == 1) {
                    hexString.append('0');
                }
                hexString.append(hex);
            }
            return CACHE_PREFIX + type + ":" + hexString.toString().substring(0, 32);
        } catch (JsonProcessingException | NoSuchAlgorithmException e) {
            return CACHE_PREFIX + type + ":" + params.hashCode();
        }
    }
}
