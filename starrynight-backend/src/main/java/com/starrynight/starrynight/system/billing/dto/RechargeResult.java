package com.starrynight.starrynight.system.billing.dto;

import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
public class RechargeResult {
    private String recordNo;
    private BigDecimal amount;
    private BigDecimal platformCurrency;
    private BigDecimal bonusCurrency;
    private String payStatus;
    private String payUrl;
    private LocalDateTime expireTime;
}
