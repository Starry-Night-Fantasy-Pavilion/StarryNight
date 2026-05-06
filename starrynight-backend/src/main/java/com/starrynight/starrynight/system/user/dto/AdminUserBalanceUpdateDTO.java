package com.starrynight.starrynight.system.user.dto;

import lombok.Data;

import java.math.BigDecimal;

@Data
public class AdminUserBalanceUpdateDTO {

    /** 创作点余额（user_balance.free_quota）；与 platformCurrency 至少填一项 */
    private Long freeQuota;

    /** 星夜币（user_balance.platform_currency） */
    private BigDecimal platformCurrency;
}
