package com.starrynight.starrynight.system.bookstore.dto;

import lombok.Builder;
import lombok.Data;

import java.math.BigDecimal;
import java.util.List;

/** 书城前台搜索/列表单行（与 {@code bookstore_book} 上架数据一致） */
@Data
@Builder
public class BookstoreSearchBookDTO {

    private Long id;

    private String title;

    private String author;

    private String cover;

    private String description;

    /** 分类展示名（含父级时「父 / 子」） */
    private String category;

    /** 字数，与详情页一致为「万」为单位展示 */
    private Integer wordCount;

    private Long views;

    private BigDecimal rating;

    private Integer chapterCount;

    private Boolean isVip;

    private List<String> tags;

    /** 无数据库字段时可为空，前端可展示「—」 */
    private String status;
}
