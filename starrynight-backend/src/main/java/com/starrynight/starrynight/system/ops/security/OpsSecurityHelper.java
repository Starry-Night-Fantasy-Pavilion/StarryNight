package com.starrynight.starrynight.system.ops.security;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.system.auth.entity.OpsAccount;
import com.starrynight.starrynight.system.auth.repository.OpsAccountRepository;
import com.starrynight.starrynight.system.rbac.entity.AdminRole;
import com.starrynight.starrynight.system.rbac.repository.AdminRoleRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

/**
 * 运营端当前登录人权限（基于 admin_role.code）。
 */
@Component
public class OpsSecurityHelper {

    public static final String SUPER_ADMIN_CODE = "SUPER_ADMIN";

    @Autowired
    private OpsAccountRepository opsAccountRepository;
    @Autowired
    private AdminRoleRepository adminRoleRepository;

    public boolean currentOpsIsSuperAdmin() {
        Long id = ThreadLocalUtil.getUserId();
        if (id == null) {
            return false;
        }
        OpsAccount me = opsAccountRepository.selectById(id);
        if (me == null || me.getDeleted() != 0) {
            return false;
        }
        return isSuperAdminRoleId(me.getRoleId());
    }

    public boolean isSuperAdminRoleId(Long roleId) {
        if (roleId == null || roleId <= 0) {
            return false;
        }
        AdminRole role = adminRoleRepository.selectById(roleId);
        return role != null && Integer.valueOf(0).equals(role.getDeleted())
                && SUPER_ADMIN_CODE.equals(role.getCode());
    }

    public void requireSuperAdmin() {
        if (!currentOpsIsSuperAdmin()) {
            throw new BusinessException("仅超级管理员可进行此操作");
        }
    }
}
