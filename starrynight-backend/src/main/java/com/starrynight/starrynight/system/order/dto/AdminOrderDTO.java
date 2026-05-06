package com.starrynight.starrynight.system.order.dto;

import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
public class AdminOrderDTO {

    private Long id;

    private String orderNo;

    private Long userId;

    private String username;

    private String productName;

    private BigDecimal amount;

    private Integer status;

    private LocalDateTime payTime;

    private LocalDateTime createTime;
}
