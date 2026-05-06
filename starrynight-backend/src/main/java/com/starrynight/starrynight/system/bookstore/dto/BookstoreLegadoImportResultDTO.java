package com.starrynight.starrynight.system.bookstore.dto;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.util.List;

@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class BookstoreLegadoImportResultDTO {

    private int inserted;

    private int updated;

    private int skipped;

    private List<String> errors;
}
