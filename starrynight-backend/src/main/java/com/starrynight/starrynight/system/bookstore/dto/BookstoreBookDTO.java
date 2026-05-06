package com.starrynight.starrynight.system.bookstore.dto;

import jakarta.validation.constraints.NotNull;
import lombok.Data;

import java.math.BigDecimal;

@Data
public class BookstoreBookDTO {

    private Long id;

    /** 可选；空则服务端根据书源 URL / JSON 自动生成列表标题 */
    private String title;

    private String author;

    private String coverUrl;

    private String intro;

    private Long categoryId;

    @NotNull
    private Integer isVip;

    @NotNull
    private BigDecimal rating;

    @NotNull
    private Integer wordCount;

    @NotNull
    private Long readCount;

    @NotNull
    private Integer sortOrder;

    @NotNull
    private Integer status;

    private String tags;

    /** 书源详情或目录页 URL */
    private String sourceUrl;

    /** 书源规则 JSON（须为合法 JSON 文本） */
    private String sourceJson;

    /** 已发布章节条数（运营端列表展示） */
    private Integer chapterCount;
}
