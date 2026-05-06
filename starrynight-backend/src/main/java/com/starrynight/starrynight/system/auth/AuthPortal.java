package com.starrynight.starrynight.system.auth;

import java.util.Optional;

/**
 * 登录端：用户端与运营端隔离。JWT 中携带 portal，仅 OPS 端令牌具备 ROLE_ADMIN。
 */
public enum AuthPortal {
    USER,
    OPS;

    public static AuthPortal fromLoginRequest(String raw) {
        if (raw == null || raw.isBlank()) {
            return USER;
        }
        String v = raw.trim().toUpperCase();
        if ("OPS".equals(v) || "ADMIN".equals(v) || "OPERATION".equals(v)) {
            return OPS;
        }
        return USER;
    }

    public static Optional<AuthPortal> fromJwtClaim(String raw) {
        if (raw == null || raw.isBlank()) {
            return Optional.empty();
        }
        String v = raw.trim().toUpperCase();
        if ("OPS".equals(v)) {
            return Optional.of(OPS);
        }
        if ("USER".equals(v)) {
            return Optional.of(USER);
        }
        return Optional.empty();
    }
}
