package com.starrynight.starrynight.system.auth.vo;

import lombok.Data;

@Data
public class AuthVO {

    private String accessToken;
    private String refreshToken;
    private Long expiresIn;
    /**
     * USER | OPS，与令牌中的 portal 一致，供前端路由守卫使用。
     */
    private String authPortal;
    private UserInfo user;

    @Data
    public static class UserInfo {
        private Long id;
        private String username;
        private String email;
        private String phone;
        private String avatar;
        private Integer status;
        private Integer isAdmin;
        /** 运营端：admin_role.code，如 SUPER_ADMIN */
        private String roleCode;
        /** 运营端：角色展示名 */
        private String roleName;
        /** 前台：实名核验是否已通过（支付宝人脸 / Ovooa 等完成后为 true） */
        private Boolean realNameVerified;
    }
}

