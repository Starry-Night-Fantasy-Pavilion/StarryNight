package com.starrynight.starrynight.system.vip.dto;

import jakarta.validation.constraints.*;
import lombok.Data;

import java.math.BigDecimal;

@Data
public class AdminVipPackageSaveDTO {

    @Size(max = 50)
    private String packageCode;

    @NotBlank
    @Size(max = 100)
    private String packageName;

    @Size(max = 500)
    private String description;

    @NotNull
    @Min(1)
    @Max(3)
    private Integer memberLevel;

    @NotNull
    @Min(1)
    private Integer durationDays;

    @NotNull
    @DecimalMin("0.0")
    private BigDecimal price;

    @DecimalMin("0.0")
    private BigDecimal originalPrice;

    @NotNull
    @Min(0)
    private Long dailyFreeQuota;

    /** JSON 字符串，与 vip_package.features 列一致 */
    private String features;

    @NotNull
    private Integer sortOrder;

    @NotNull
    @Min(0)
    @Max(1)
    private Integer status;
}
