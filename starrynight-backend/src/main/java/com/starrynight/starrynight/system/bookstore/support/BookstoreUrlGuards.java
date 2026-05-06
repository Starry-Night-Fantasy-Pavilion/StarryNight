package com.starrynight.starrynight.system.bookstore.support;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import org.springframework.util.StringUtils;

import java.net.URI;
import java.util.Locale;

/** 书源与代理请求的 URL 安全校验（防 SSRF） */
public final class BookstoreUrlGuards {

    private BookstoreUrlGuards() {}

    public static void assertSafeHttpUrl(String raw) {
        if (!StringUtils.hasText(raw)) {
            throw new BusinessException("无效 URL");
        }
        URI u;
        try {
            u = URI.create(raw.trim());
        } catch (Exception e) {
            throw new BusinessException("无效 URL");
        }
        String scheme = u.getScheme();
        if (!"http".equalsIgnoreCase(scheme) && !"https".equalsIgnoreCase(scheme)) {
            throw new BusinessException("仅允许 http/https 书源");
        }
        String host = u.getHost();
        if (!StringUtils.hasText(host)) {
            throw new BusinessException("无效书源主机");
        }
        String h = host.toLowerCase(Locale.ROOT);
        if ("localhost".equals(h) || h.endsWith(".localhost")) {
            throw new BusinessException("禁止本地书源地址");
        }
        if (h.startsWith("127.")) {
            throw new BusinessException("禁止环回书源地址");
        }
        if (h.startsWith("192.168.") || h.startsWith("10.")) {
            throw new BusinessException("禁止内网书源地址");
        }
        if (h.startsWith("172.")) {
            String[] p = h.split("\\.", -1);
            if (p.length >= 2) {
                try {
                    int sec = Integer.parseInt(p[1]);
                    if (sec >= 16 && sec <= 31) {
                        throw new BusinessException("禁止内网书源地址");
                    }
                } catch (NumberFormatException ignored) {
                    /* continue */
                }
            }
        }
        if ("0.0.0.0".equals(h)) {
            throw new BusinessException("禁止无效书源地址");
        }
    }
}
