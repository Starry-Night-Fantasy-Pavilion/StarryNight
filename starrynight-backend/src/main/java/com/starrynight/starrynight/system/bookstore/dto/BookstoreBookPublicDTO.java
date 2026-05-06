package com.starrynight.starrynight.system.bookstore.dto;

import lombok.Builder;
import lombok.Data;

import java.math.BigDecimal;
import java.util.List;

@Data
@Builder
public class BookstoreBookPublicDTO {

    private Long id;

    private String title;

    private String author;

    private String cover;

    private String description;

    private Long views;

    private BigDecimal rating;

    private Boolean isVip;

    private Integer wordCount;

    private String category;

    private List<String> tags;
}
