package com.starrynight.starrynight.system.user.dto;

import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class UserStatusUpdateDTO {

    @NotNull(message = "Status is required")
    private Integer status;
}
