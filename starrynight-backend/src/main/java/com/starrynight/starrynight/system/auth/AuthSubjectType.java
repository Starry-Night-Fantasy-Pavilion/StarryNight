package com.starrynight.starrynight.system.auth;

/**
 * JWT 主体类型：用于区分用户端账号与运营端账号，避免跨表解析。
 */
public enum AuthSubjectType {
    USER,
    OPS;

    public static AuthSubjectType fromClaim(String raw, AuthPortal portalFallback) {
        if (raw != null && !raw.isBlank()) {
            String v = raw.trim().toUpperCase();
            if ("OPS".equals(v)) {
                return OPS;
            }
            if ("USER".equals(v)) {
                return USER;
            }
        }
        return portalFallback == AuthPortal.OPS ? OPS : USER;
    }
}
