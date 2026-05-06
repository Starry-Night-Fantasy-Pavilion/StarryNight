package com.starrynight.starrynight.system.novel.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.novel.dto.NovelCategoryTreeNodeDTO;
import com.starrynight.starrynight.system.novel.service.NovelCategoryService;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

@RestController
@RequestMapping("/api/novel-categories")
@RequiredArgsConstructor
public class NovelCategoryPublicController {

    private final NovelCategoryService novelCategoryService;

    @GetMapping("/tree")
    public ResponseVO<List<NovelCategoryTreeNodeDTO>> tree() {
        return ResponseVO.success(novelCategoryService.tree());
    }
}
