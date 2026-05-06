package com.starrynight.starrynight.system.billing.dto;

import lombok.Data;

import java.math.BigDecimal;

@Data
public class ChargeRequest {
    private Long userId;
    private String contentType;
    private Long contentId;
    private Long channelId;
    private Integer inputTokens;
    private Integer outputTokens;
}
