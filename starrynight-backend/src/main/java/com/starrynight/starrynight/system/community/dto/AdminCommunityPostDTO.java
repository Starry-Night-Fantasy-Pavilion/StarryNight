package com.starrynight.starrynight.system.community.dto;

import lombok.Data;

import java.time.LocalDateTime;

@Data
public class AdminCommunityPostDTO {

    private Long id;

    private Long userId;

    private String authorUsername;

    private String title;

    private String content;

    private String contentType;

    private Long topicId;

    private Integer auditStatus;

    private String rejectReason;

    private Integer likeCount;

    private Integer commentCount;

    private Integer viewCount;

    private Integer onlineStatus;

    private LocalDateTime createTime;

    private LocalDateTime updateTime;
}
