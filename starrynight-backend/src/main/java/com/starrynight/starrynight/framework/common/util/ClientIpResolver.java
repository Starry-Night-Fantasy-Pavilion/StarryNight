package com.starrynight.starrynight.framework.common.util;

import jakarta.servlet.http.HttpServletRequest;

/**
 * 从反向代理后的请求中解析客户端 IP（优先 X-Forwarded-For 首段、X-Real-IP）。
 */
public final class ClientIpResolver {

    private static final int MAX_LEN = 45;

    private ClientIpResolver() {
    }

    public static String resolve(HttpServletRequest request) {
        if (request == null) {
            return null;
        }
        String xff = request.getHeader("X-Forwarded-For");
        if (xff != null && !xff.isBlank()) {
            String first = xff.split(",")[0].trim();
            if (!first.isEmpty()) {
                return truncate(first);
            }
        }
        String realIp = request.getHeader("X-Real-IP");
        if (realIp != null && !realIp.isBlank()) {
            return truncate(realIp.trim());
        }
        String remote = request.getRemoteAddr();
        return truncate(remote);
    }

    private static String truncate(String ip) {
        if (ip == null) {
            return null;
        }
        return ip.length() > MAX_LEN ? ip.substring(0, MAX_LEN) : ip;
    }
}
