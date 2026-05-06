package com.starrynight.starrynight.system.community.dto;

import lombok.Data;

import java.time.LocalDateTime;

@Data
public class AdminCommunityReportDTO {

    private Long id;

    private String kind;

    private Long postId;

    private Long commentId;

    private Long targetUserId;

    private String targetUsername;

    private Long reporterUserId;

    private String reporterUsername;

    private String reason;

    private String detail;

    private Integer status;

    private String handleAction;

    private String handleNote;

    private Long handledBy;

    private LocalDateTime handledTime;

    private String postTitle;

    private String contentPreview;

    private LocalDateTime createTime;
}

