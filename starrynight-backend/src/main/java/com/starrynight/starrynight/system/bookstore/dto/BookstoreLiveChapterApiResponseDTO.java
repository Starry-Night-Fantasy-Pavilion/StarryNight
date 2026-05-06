package com.starrynight.starrynight.system.bookstore.dto;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class BookstoreLiveChapterApiResponseDTO {

    private String title;

    private String contentHtml;

    private BookstoreLiveChapterNavDTO navigation;
}
