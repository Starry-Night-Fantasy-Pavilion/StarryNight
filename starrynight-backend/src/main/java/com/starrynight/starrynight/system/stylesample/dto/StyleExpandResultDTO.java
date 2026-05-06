package com.starrynight.starrynight.system.stylesample.dto;

import lombok.Data;

@Data
public class StyleExpandResultDTO {

    private String result;

    private String style;

    private Integer inputLength;

    private Integer outputLength;

    public static StyleExpandResultDTO of(String result, String style, int inputLength) {
        StyleExpandResultDTO dto = new StyleExpandResultDTO();
        dto.result = result;
        dto.style = style;
        dto.inputLength = inputLength;
        dto.outputLength = result.length();
        return dto;
    }
}