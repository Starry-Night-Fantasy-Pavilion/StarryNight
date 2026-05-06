package com.starrynight.starrynight.system.auth.realname;

import org.springframework.stereotype.Component;

import java.time.Instant;
import java.time.temporal.ChronoUnit;
import java.util.Locale;
import java.util.concurrent.ConcurrentHashMap;

/**
 * certify_id / outer_order_no → userId，用于支付宝异步通知或 Ovooa 回调关联用户（进程内缓存，多实例需改为 Redis）。
 */
@Component
public class RealnameCertifyPendingStore {

    private final ConcurrentHashMap<String, Pending> byCertifyOrOuter = new ConcurrentHashMap<>();

    public record Pending(long userId, Instant expiresAt) {}

    public void put(String key, long userId) {
        if (key == null || key.isBlank()) {
            return;
        }
        byCertifyOrOuter.put(key.trim(), new Pending(userId, Instant.now().plus(30, ChronoUnit.MINUTES)));
    }

    public Long consume(String key) {
        if (key == null || key.isBlank()) {
            return null;
        }
        Pending p = byCertifyOrOuter.remove(key.trim());
        if (p == null || Instant.now().isAfter(p.expiresAt())) {
            return null;
        }
        return p.userId();
    }

    public Long peekUserId(String key) {
        if (key == null || key.isBlank()) {
            return null;
        }
        Pending p = byCertifyOrOuter.get(key.trim());
        if (p == null || Instant.now().isAfter(p.expiresAt())) {
            return null;
        }
        return p.userId();
    }

    public static String normalizeKey(String raw) {
        return raw == null ? "" : raw.trim().toLowerCase(Locale.ROOT);
    }
}
