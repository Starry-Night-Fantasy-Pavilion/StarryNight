package com.starrynight.starrynight.system.billing.dto;

import lombok.Data;

import java.math.BigDecimal;

@Data
public class BillingConfigDTO {
    private Long dailyFreeQuota;
    private BigDecimal defaultProfitMargin;
    private Boolean mixedPaymentDefault;
    private Integer freeQuotaResetHour;
    private BigDecimal platformCurrencyRate;
    private Long creationPointRate;
}
