package com.starrynight.starrynight.system.user.dto;

import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class AdminUserRealnameVerifiedUpdateDTO {

    /** 0 未通过人脸/三方核验；1 已通过 */
    @NotNull(message = "请指定核验状态")
    private Integer realNameVerified;
}
