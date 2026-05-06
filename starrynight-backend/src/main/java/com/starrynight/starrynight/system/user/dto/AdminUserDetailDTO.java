package com.starrynight.starrynight.system.user.dto;

import lombok.Data;
import lombok.EqualsAndHashCode;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.List;

@Data
@EqualsAndHashCode(callSuper = true)
public class AdminUserDetailDTO extends AdminUserDTO {

    private String nickname;

    private String avatar;

    private Long totalWordCount;

    /** user_balance.free_quota_date */
    private LocalDate freeQuotaDate;

    /** user_balance 混合支付开关 */
    private Boolean enableMixedPayment;

    /** 是否已在库中登记姓名与证件号 */
    private Boolean hasIdentityOnFile;

    /** 脱敏真实姓名，未登记为 null */
    private String realNameMasked;

    /** 脱敏证件号，未登记为 null */
    private String idCardMasked;

    /** 0 未通过人脸/三方核验；1 已通过 */
    private Integer realNameVerified;

    /** 最近一次核验外部单号 */
    private String realNameVerifyOuterNo;

    /** 当前关联的实名认证费本地订单号（易支付） */
    private String realnameFeePaidRecordNo;

    /** 关联认证费订单状态（如 PENDING / SUCCESS），无关联为 null */
    private String realnameFeePayStatus;

    private BigDecimal realnameFeePayAmount;

    private LocalDateTime realnameFeePayTime;

    /** 第三方 OAuth 已绑定渠道代码列表，如 LINUXDO */
    private List<String> oauthProviders;

    /** 未删除作品数量 */
    private Integer novelCount;

    /** user_balance：累计消耗免费创作点 */
    private Long totalFreeUsed;

    /** user_balance：累计消耗付费侧创作点（混合计费） */
    private Long totalPaidUsed;

    /** user_balance：累计充值人民币取整（元） */
    private Long totalRecharged;
}
