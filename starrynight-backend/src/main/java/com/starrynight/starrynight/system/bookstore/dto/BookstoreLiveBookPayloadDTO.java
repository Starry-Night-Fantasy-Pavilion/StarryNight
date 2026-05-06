package com.starrynight.starrynight.system.bookstore.dto;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.math.BigDecimal;
import java.util.Map;

/** 实时解析返回的书籍元信息（与运营库字段对齐 + extraInfo） */
@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class BookstoreLiveBookPayloadDTO {

    private Long id;

    private String title;

    private String author;

    private String cover;

    private String description;

    private String category;

    private BigDecimal rating;

    private Integer wordCount;

    private Map<String, Object> extraInfo;
}
