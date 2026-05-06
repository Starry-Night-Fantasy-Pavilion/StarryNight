package com.starrynight.starrynight.system.billing.dto;

import lombok.Data;

import java.math.BigDecimal;

@Data
public class RechargeRequest {
    private Long userId;
    private BigDecimal amount;
    private String payMethod;
}
