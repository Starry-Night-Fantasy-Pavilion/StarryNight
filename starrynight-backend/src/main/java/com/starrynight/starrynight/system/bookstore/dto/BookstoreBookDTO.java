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

    /** 书源 URL（文档接口 {@code /api/bookstore/book?url=}） */
    private String sourceUrl;

    /** 已发布章节条数（运营端列表展示） */
    private Integer chapterCount;
}
