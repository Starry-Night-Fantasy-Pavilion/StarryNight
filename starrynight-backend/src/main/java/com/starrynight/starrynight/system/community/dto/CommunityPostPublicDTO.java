package com.starrynight.starrynight.system.community.dto;

import lombok.Data;

import java.time.LocalDateTime;

@Data
public class CommunityPostPublicDTO {

    private Long id;

    private Long userId;

    private String authorUsername;

    private String title;

    private String content;

    private String contentType;

    private Long topicId;

    private Integer likeCount;

    private Integer commentCount;

    private Integer viewCount;

    /** 当前登录用户是否已点赞（仅用户端 JWT 时有意义） */
    private Boolean likedByMe;

    /** 是否允许评论/点赞（已审核且上架） */
    private Boolean interactionEnabled;

    /** Present after create/update; public list/detail only approved posts */
    private Integer auditStatus;

    /** Filled for author preview when rejected */
    private String rejectReason;

    private LocalDateTime createTime;
}
