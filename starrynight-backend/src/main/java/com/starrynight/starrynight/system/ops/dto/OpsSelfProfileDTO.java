package com.starrynight.starrynight.system.ops.dto;

import jakarta.validation.constraints.Size;
import lombok.Data;

/**
 * 运营个人中心资料更新。登录用户名（username）仅超级管理员可改；邮箱所有运营账号可改。
 */
@Data
public class OpsSelfProfileDTO {

    @Size(max = 100, message = "邮箱长度不能超过100")
    private String email;

    /**
     * 若传入非空则尝试修改登录用户名，仅 SUPER_ADMIN 角色允许（格式在业务层校验）。
     */
    private String username;
}
