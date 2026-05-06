package com.starrynight.starrynight.system.billing.dto;

import lombok.Data;

import java.math.BigDecimal;

@Data
public class ChannelDTO {
    private Long id;
    private String channelCode;
    private String channelName;
    private String channelType;
    private String apiBaseUrl;
    private String apiKey;
    private String modelName;
    private BigDecimal costPer1kInput;
    private BigDecimal costPer1kOutput;
    private BigDecimal costPerCall;
    private BigDecimal costPerSecond;
    private BigDecimal baseCost;
    private Boolean isFree;
    private String status;
    private Integer enabled;
    private Integer sortOrder;
}
