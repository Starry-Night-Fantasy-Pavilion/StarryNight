package com.starrynight.starrynight.system.user.dto;

import jakarta.validation.constraints.NotNull;
import lombok.Data;

import java.time.LocalDateTime;

@Data
public class AdminUserMembershipUpdateDTO {

    /** 1-普通 2-VIP 3-高级VIP */
    @NotNull(message = "会员等级不能为空")
    private Integer memberLevel;

    /** 为空表示不限制到期（长期有效） */
    private LocalDateTime memberExpireTime;
}
