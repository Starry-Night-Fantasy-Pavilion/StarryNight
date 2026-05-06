package com.starrynight.starrynight.system.redeem.dto;

import jakarta.validation.constraints.Max;
import jakarta.validation.constraints.Min;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
public class RedeemGenerateRequest {

    private String batchLabel;

    @NotNull
    @Min(1)
    @Max(500)
    private Integer count;

    @NotNull
    @Min(8)
    @Max(32)
    private Integer codeLength;

    private String prefix;

    @NotBlank
    private String rewardType;

    @NotNull
    private Long rewardPoints;

    @NotNull
    private BigDecimal rewardCurrency;

    private Integer maxTotalRedemptions;

    @NotNull
    private Integer maxPerUser;

    private LocalDateTime validStart;

    private LocalDateTime validEnd;

    @NotNull
    private Integer enabled;

    private Long campaignId;
}
