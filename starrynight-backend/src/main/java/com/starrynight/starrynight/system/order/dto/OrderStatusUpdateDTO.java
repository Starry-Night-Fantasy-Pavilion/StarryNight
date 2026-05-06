package com.starrynight.starrynight.system.order.dto;

import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class OrderStatusUpdateDTO {

    @NotNull(message = "Status is required")
    private Integer status;
}
