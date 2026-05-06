package com.starrynight.starrynight.system.community.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.Data;

@Data
public class CommunityPostCreateDTO {

    @Size(max = 200)
    private String title;

    @NotBlank
    @Size(max = 20000)
    private String content;

    @Size(max = 20)
    private String contentType;

    private Long topicId;
}
