package com.starrynight.starrynight.system.auth.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class LoginDTO {

    @NotBlank(message = "Username is required")
    private String username;

    @NotBlank(message = "Password is required")
    private String password;

    /**
     * 登录端：USER 用户端（默认），OPS 运营端。与 JWT portal 声明一致。
     */
    private String portal;
}

