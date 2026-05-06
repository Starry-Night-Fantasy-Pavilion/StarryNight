package com.starrynight.starrynight.system.user.dto;

import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
public class AdminUserDTO {

    private Long id;

    private String username;

    private String email;

    private String phone;

    private Integer status;

    private Integer isAdmin;

    private Integer memberLevel;

    /** 会员到期时间（与 user_profile.member_expire_time 一致） */
    private LocalDateTime memberExpireTime;

    /** user_profile 遗留字段，运营列表不再主推 */
    private Integer points;

    /** user_balance.free_quota：可用于创作的点数余额 */
    private Long freeQuota;

    /** user_balance.platform_currency：星夜币（充值货币） */
    private BigDecimal platformCurrency;

    private LocalDateTime createTime;

    /** auth_user.register_ip */
    private String registerIp;

    private LocalDateTime lastLoginTime;

    /** auth_user.last_login_ip */
    private String lastLoginIp;
}
