package com.starrynight.starrynight.system.community.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import jakarta.validation.constraints.Size;
import lombok.Data;

@Data
public class CommunityCommentCreateDTO {

    @NotNull
    private Long postId;

    private Long parentId;

    @NotBlank
    @Size(max = 2000)
    private String content;
}
