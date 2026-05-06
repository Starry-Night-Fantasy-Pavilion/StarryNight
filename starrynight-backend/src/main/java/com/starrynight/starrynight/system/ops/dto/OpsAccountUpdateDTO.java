package com.starrynight.starrynight.system.ops.dto;

import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class OpsAccountUpdateDTO {
    /** 可选；传 null 表示不修改邮箱 */
    private String email;

    @NotNull(message = "Role is required")
    private Long roleId;

    @NotNull(message = "Status is required")
    private Integer status;
}
