package com.starrynight.starrynight.system.auth.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Pattern;
import lombok.Data;

@Data
public class RegisterDTO {

    @NotBlank(message = "Username is required")
    private String username;

    @NotBlank(message = "Password is required")
    private String password;

    /** 空串表示不填；非空须为合法邮箱 */
    @Pattern(regexp = "^$|^[A-Za-z0-9+_.-]+@[A-Za-z0-9.-]+$", message = "Invalid email format")
    private String email;

    /** 空串表示不填；非空须为 11 位大陆手机号 */
    @Pattern(regexp = "^$|^1[3-9]\\d{9}$", message = "Invalid phone format")
    private String phone;

    /** 开启实名认证时必填；否则可空 */
    private String realName;

    /** 开启实名认证时必填；否则可空 */
    private String idCardNo;
}

