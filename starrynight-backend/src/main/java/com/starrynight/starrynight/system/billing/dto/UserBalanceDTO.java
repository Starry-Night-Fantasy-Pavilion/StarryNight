package com.starrynight.starrynight.system.billing.dto;

import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDate;

@Data
public class UserBalanceDTO {
    private Long userId;
    private Long freeQuota;
    private LocalDate freeQuotaDate;
    private BigDecimal platformCurrency;
    private Long platformCurrencyInPoints;
    private Boolean enableMixedPayment;
    private Long todayFreeUsed;
    private Long todayPaidUsed;
}
