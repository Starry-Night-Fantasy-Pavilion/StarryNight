package com.starrynight.starrynight.system.vip.dto;

import jakarta.validation.constraints.Max;
import jakarta.validation.constraints.Min;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import jakarta.validation.constraints.Size;
import lombok.Data;

@Data
public class AdminBenefitConfigUpdateDTO {

    @NotBlank
    @Size(max = 100)
    private String benefitName;

    /** JSON，如 {"value":50000} 或 {"value":true} */
    private String benefitValue;

    @Size(max = 500)
    private String description;

    @NotNull
    @Min(0)
    @Max(1)
    private Integer enabled;
}
