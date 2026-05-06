package com.starrynight.starrynight.system.ops.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class OpsAccountCreateDTO {
    @NotBlank(message = "Username is required")
    private String username;

    /** 可选；用于登录与联系，须唯一 */
    private String email;

    @NotBlank(message = "Password is required")
    private String password;

    @NotNull(message = "Role is required")
    private Long roleId;

    @NotNull(message = "Status is required")
    private Integer status;
}
