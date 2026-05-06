package com.starrynight.starrynight.system.community.dto;

import jakarta.validation.constraints.Size;
import lombok.Data;

@Data
public class CommunityRejectRequest {

    @Size(max = 500)
    private String reason;
}
