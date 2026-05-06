package com.starrynight.starrynight.system.ops.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class OpsAccountPasswordDTO {
    @NotBlank(message = "Password is required")
    private String password;
}
