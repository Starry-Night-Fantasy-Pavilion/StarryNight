package com.starrynight.starrynight.system.bookstore.dto;

import lombok.Builder;
import lombok.Data;

import java.util.List;
import java.util.Map;

@Data
@Builder
public class BookstoreHomeDTO {

    private boolean enabled;

    private String siteTitle;

    private List<Map<String, Object>> banners;

    private List<Map<String, Object>> hotBooks;

    private List<Map<String, Object>> newBooks;

    private List<Map<String, Object>> rankingBooks;

    private List<Map<String, Object>> categories;

    private List<Map<String, Object>> sidebarReaders;

    private List<Map<String, Object>> latestUpdates;
}
