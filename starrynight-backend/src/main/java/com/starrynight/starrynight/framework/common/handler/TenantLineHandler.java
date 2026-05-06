package com.starrynight.starrynight.framework.common.handler;

import net.sf.jsqlparser.expression.Expression;
import net.sf.jsqlparser.expression.LongValue;

public class TenantLineHandler implements com.baomidou.mybatisplus.extension.plugins.handler.TenantLineHandler {

    @Override
    public Expression getTenantId() {
        Long tenantId = getCurrentTenantId();
        if (tenantId != null) {
            return new LongValue(tenantId);
        }
        return new LongValue(0L);
    }

    @Override
    public String getTenantIdColumn() {
        return "tenant_id";
    }

    @Override
    public boolean ignoreTable(String tableName) {
        // 当前业务表均未设计 tenant_id；多租户插件若对部分表生效会在 WHERE 中追加未知列（如 dashboard / system_config 全线 500）。
        // 将来若引入 tenant_id，请改为按表名白名单或黑名单，而非全表追加。
        return true;
    }

    private Long getCurrentTenantId() {
        try {
            com.starrynight.starrynight.framework.common.util.ThreadLocalUtil util =
                com.starrynight.starrynight.framework.common.util.ThreadLocalUtil.getInstance();
            Object tenantId = util.get("tenantId");
            if (tenantId instanceof Long) {
                return (Long) tenantId;
            }
        } catch (Exception e) {
        }
        return null;
    }
}
