package com.starrynight.starrynight.system.auth.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class OauthExchangeDTO {

    @NotBlank
    private String sid;
}
