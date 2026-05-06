package com.starrynight.starrynight.system.bookstore.dto;

import lombok.Builder;
import lombok.Data;

import java.util.ArrayList;
import java.util.List;

@Data
@Builder
public class BookstoreSyncResultDTO {

    private int tocLinksFound;

    private int chaptersImported;

    private int chaptersUpdated;

    private int chaptersSkipped;

    @Builder.Default
    private List<String> logs = new ArrayList<>();
}
