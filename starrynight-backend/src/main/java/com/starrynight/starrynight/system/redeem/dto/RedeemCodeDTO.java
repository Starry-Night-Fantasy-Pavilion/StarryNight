package com.starrynight.starrynight.system.redeem.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
public class RedeemCodeDTO {

    private Long id;

    @NotBlank
    private String code;

    private String batchLabel;

    @NotBlank
    private String rewardType;

    @NotNull
    private Long rewardPoints;

    @NotNull
    private BigDecimal rewardCurrency;

    private Integer maxTotalRedemptions;

    private Integer redemptionCount;

    @NotNull
    private Integer maxPerUser;

    private LocalDateTime validStart;

    private LocalDateTime validEnd;

    @NotNull
    private Integer enabled;

    private Long campaignId;

    private LocalDateTime createTime;

    private LocalDateTime updateTime;
}
