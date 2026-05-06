package com.starrynight.starrynight.framework.common.util;

/**
 * 请求线程内当前登录用户 ID（由 {@link com.starrynight.starrynight.system.auth.filter.JwtAuthenticationFilter} 写入）。
 */
public final class ThreadLocalUtil {

    private static final ThreadLocal<Long> USER_ID = new ThreadLocal<>();
    private static final ThreadLocal<String> REQUEST_ID = new ThreadLocal<>();
    private static final ThreadLocal<Long> TENANT_ID = new ThreadLocal<>();

    private ThreadLocalUtil() {
    }

    public static void setUserId(Long userId) {
        USER_ID.set(userId);
    }

    public static Long getUserId() {
        return USER_ID.get();
    }

    /** 同 {@link #getUserId()}，兼容业务侧命名 */
    public static Long getCurrentUserId() {
        return USER_ID.get();
    }

    public static void setRequestId(String requestId) {
        REQUEST_ID.set(requestId);
    }

    public static String getRequestId() {
        return REQUEST_ID.get();
    }

    public static void setTenantId(Long tenantId) {
        TENANT_ID.set(tenantId);
    }

    public static Long getTenantId() {
        return TENANT_ID.get();
    }

    public static void clear() {
        USER_ID.remove();
        REQUEST_ID.remove();
        TENANT_ID.remove();
    }

    public static ThreadLocalUtil getInstance() {
        return new ThreadLocalUtil();
    }

    public Object get(String key) {
        if ("userId".equals(key)) {
            return USER_ID.get();
        } else if ("tenantId".equals(key)) {
            return TENANT_ID.get();
        } else if ("requestId".equals(key)) {
            return REQUEST_ID.get();
        }
        return null;
    }
}
