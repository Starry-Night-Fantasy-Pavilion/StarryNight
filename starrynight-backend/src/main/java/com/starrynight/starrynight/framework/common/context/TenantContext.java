package com.starrynight.starrynight.framework.common.context;

import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;

public class TenantContext {

    public static void setTenantId(Long tenantId) {
        ThreadLocalUtil.setTenantId(tenantId);
    }

    public static Long getTenantId() {
        return ThreadLocalUtil.getTenantId();
    }

    public static void clear() {
        ThreadLocalUtil.setTenantId(null);
    }

    public static boolean isValid() {
        return getTenantId() != null && getTenantId() > 0;
    }
}
