package com.starrynight.starrynight.system.user.dto;

import lombok.Data;

import java.math.BigDecimal;

@Data
public class UserProfileDTO {

    private Long userId;

    private String username;

    private String nickname;

    private String email;

    private String phone;

    private String avatar;

    private Integer memberLevel;

    private Integer points;

    /** 0/1，与 auth_user.real_name_verified 一致 */
    private Integer realNameVerified;

    /** 已填实名但未完成人脸/三方核验时为 true */
    private Boolean realNameVerifyPending;

    /** 站点是否开启实名（与 auth.realname.enabled 一致） */
    private Boolean realNameGateEnabled;

    /** 是否已在账号上登记姓名与证件号（不含具体号码） */
    private Boolean hasRealNameOnFile;

    /** 实名未开启时为 basic；开启后为 alipay / ovooa（喵雨欣，miaoyuxin 为别名） */
    private String realNameVerifyProvider;

    /** 是否启用实名认证费（auth.realname.fee.enabled） */
    private Boolean realnameFeeEnabled;

    /** 认证费金额（元），未启用或未配置为 null */
    private BigDecimal realnameFeeAmountYuan;

    /**
     * 当启用认证费且金额大于 0 时：是否已通过易支付完成与当前配置金额一致的一笔缴费；
     * 未启用认证费或金额为 0 时为 null。
     */
    private Boolean realnameFeeCashPaid;
}

