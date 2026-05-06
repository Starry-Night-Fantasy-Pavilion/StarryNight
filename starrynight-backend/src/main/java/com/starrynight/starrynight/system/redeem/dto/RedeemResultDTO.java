package com.starrynight.starrynight.system.redeem.dto;

import lombok.Builder;
import lombok.Data;

import java.math.BigDecimal;

@Data
@Builder
public class RedeemResultDTO {

    private String rewardType;

    private Long rewardPoints;

    private BigDecimal rewardCurrency;

    private String message;
}
