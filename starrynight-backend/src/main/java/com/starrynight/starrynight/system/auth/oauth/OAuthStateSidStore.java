package com.starrynight.starrynight.system.auth.oauth;

import org.springframework.stereotype.Component;

import java.time.Instant;
import java.time.temporal.ChronoUnit;
import java.util.Locale;
import java.util.UUID;
import java.util.concurrent.ConcurrentHashMap;

/**
 * OAuth2 CSRF state 与一次性换票 sid 的进程内存储（与 LINUX DO 原实现一致，多提供商共用）。
 */
@Component
public class OAuthStateSidStore {

    private final ConcurrentHashMap<String, Instant> oauthStates = new ConcurrentHashMap<>();
    private final ConcurrentHashMap<String, PendingSid> oauthSid = new ConcurrentHashMap<>();
    /** 知我云聚合：回调 URL 需在平台登记为固定地址，用 Cookie 票据绑定登录方式（type）防 CSRF */
    private final ConcurrentHashMap<String, PendingZevost> zevostTickets = new ConcurrentHashMap<>();

    public record PendingSid(long userId, Instant expiresAt) {
    }

    public record PendingZevost(String loginType, Instant expiresAt) {
    }

    public static final String ZEVOST_COOKIE_NAME = "SN_ZVST_OAUTH";

    public String newState() {
        String state = UUID.randomUUID().toString().replace("-", "");
        oauthStates.put(state, Instant.now().plus(10, ChronoUnit.MINUTES));
        return state;
    }

    public boolean consumeState(String state) {
        if (state == null || state.isBlank()) {
            return false;
        }
        Instant exp = oauthStates.remove(state.trim());
        return exp != null && Instant.now().isBefore(exp);
    }

    /** @return 一次性票据，写入 Cookie 后在聚合回调中核销 */
    public String newZevostTicket(String loginType) {
        String ticket = UUID.randomUUID().toString().replace("-", "");
        String lt = loginType.toLowerCase(Locale.ROOT);
        zevostTickets.put(ticket, new PendingZevost(lt, Instant.now().plus(10, ChronoUnit.MINUTES)));
        return ticket;
    }

    /** @return 登录方式 type（小写），无效则 null */
    public String consumeZevostTicket(String ticket) {
        if (ticket == null || ticket.isBlank()) {
            return null;
        }
        PendingZevost p = zevostTickets.remove(ticket.trim());
        if (p == null || Instant.now().isAfter(p.expiresAt())) {
            return null;
        }
        return p.loginType();
    }

    public String newSidForUser(long userId) {
        String sid = UUID.randomUUID().toString().replace("-", "");
        oauthSid.put(sid, new PendingSid(userId, Instant.now().plus(3, ChronoUnit.MINUTES)));
        return sid;
    }

    public PendingSid consumeSid(String sid) {
        if (sid == null || sid.isBlank()) {
            return null;
        }
        return oauthSid.remove(sid.trim());
    }
}
