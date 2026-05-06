package com.starrynight.starrynight.system.user.dto;

import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class UserPointsUpdateDTO {

    @NotNull(message = "Points is required")
    private Integer points;
}
