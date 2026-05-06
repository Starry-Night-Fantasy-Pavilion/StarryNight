package com.starrynight.starrynight.system.redeem.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class RedeemRequest {

    @NotBlank
    private String code;
}
