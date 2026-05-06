package com.starrynight.starrynight.system.novel.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.novel.dto.NovelCategoryMutateDTO;
import com.starrynight.starrynight.system.novel.dto.NovelCategoryRowDTO;
import com.starrynight.starrynight.system.novel.service.NovelCategoryService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/admin/novel-categories")
@PreAuthorize("hasRole('ADMIN')")
@RequiredArgsConstructor
public class AdminNovelCategoryController {

    private final NovelCategoryService novelCategoryService;

    @GetMapping("/list")
    public ResponseVO<List<NovelCategoryRowDTO>> list() {
        return ResponseVO.success(novelCategoryService.listRows());
    }

    @PostMapping
    public ResponseVO<NovelCategoryRowDTO> create(@Valid @RequestBody NovelCategoryMutateDTO dto) {
        return ResponseVO.success(novelCategoryService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<NovelCategoryRowDTO> update(@PathVariable Long id, @Valid @RequestBody NovelCategoryMutateDTO dto) {
        return ResponseVO.success(novelCategoryService.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        novelCategoryService.delete(id);
        return ResponseVO.success();
    }
}
