package com.starrynight.starrynight.system.novel.dto;

import lombok.Data;

import java.util.ArrayList;
import java.util.List;

@Data
public class NovelCategoryTreeNodeDTO {

    private Long id;

    private String name;

    private List<NovelCategoryTreeNodeDTO> children = new ArrayList<>();
}
