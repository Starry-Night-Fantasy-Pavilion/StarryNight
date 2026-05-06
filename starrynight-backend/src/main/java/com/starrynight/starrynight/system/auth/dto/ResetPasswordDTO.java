package com.starrynight.starrynight.system.auth.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class ResetPasswordDTO {

    @NotBlank(message = "Username is required")
    private String username;

    @NotBlank(message = "Code is required")
    private String code;

    @NotBlank(message = "New password is required")
    private String newPassword;
}

