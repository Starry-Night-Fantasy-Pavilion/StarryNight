package com.starrynight.starrynight.system.campaign.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

import java.time.LocalDateTime;

@Data
public class OpsCampaignDTO {

    private Long id;

    @NotBlank
    private String title;

    private String summary;

    private String linkUrl;

    private String coverUrl;

    @NotNull
    private Integer status;

    private LocalDateTime startTime;

    private LocalDateTime endTime;

    private Integer sortOrder;

    private LocalDateTime createTime;

    private LocalDateTime updateTime;
}
