package com.starrynight.starrynight.system.novel.dto;

import lombok.Data;

import java.util.ArrayList;
import java.util.List;

@Data
public class PlotSuggestionResultDTO {

    private List<String> suggestions = new ArrayList<>();
}

