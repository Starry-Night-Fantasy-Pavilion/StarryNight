package com.starrynight.starrynight.system.community.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class CommunityReportCreateDTO {

    /** POST / COMMENT */
    @NotBlank(message = "Kind is required")
    private String kind;

    /** 举报帖子时必填 */
    private Long postId;

    /** 举报评论时必填 */
    private Long commentId;

    @NotNull(message = "Reason is required")
    @NotBlank(message = "Reason is required")
    private String reason;

    /** 可选 */
    private String detail;
}

