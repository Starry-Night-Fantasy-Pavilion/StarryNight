package com.starrynight.starrynight.system.community.dto;

import lombok.Data;

import java.time.LocalDateTime;

@Data
public class CommunityWorkOrderDTO {

    /** POST 或 COMMENT */
    private String kind;

    private Long targetId;

    private Long postId;

    private Long commentId;

    private Long userId;

    private String username;

    private String titleSnippet;

    private String contentPreview;

    private String reasonNote;

    private LocalDateTime createTime;
}
