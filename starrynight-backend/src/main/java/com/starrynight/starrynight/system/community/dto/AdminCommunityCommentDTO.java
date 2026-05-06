package com.starrynight.starrynight.system.community.dto;

import lombok.Data;

import java.time.LocalDateTime;

@Data
public class AdminCommunityCommentDTO {

    private Long id;

    private Long postId;

    private String postTitle;

    private Long userId;

    private String authorUsername;

    private Long parentId;

    private String content;

    /** 0 待审 1 通过 2 驳回 */
    private Integer auditStatus;

    private String moderationNote;

    private LocalDateTime createTime;
}
