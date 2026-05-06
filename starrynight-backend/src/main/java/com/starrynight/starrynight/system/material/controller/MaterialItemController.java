package com.starrynight.starrynight.system.material.controller;

import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.material.dto.MaterialItemDTO;
import com.starrynight.starrynight.system.material.service.MaterialItemService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/materials")
public class MaterialItemController {

    @Autowired
    private MaterialItemService materialItemService;

    @GetMapping("/list")
    public ResponseVO<PageVO<MaterialItemDTO>> list(
            @RequestParam(required = false) String keyword,
            @RequestParam(required = false) String type,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "12") int size) {
        return ResponseVO.success(materialItemService.list(keyword, type, page, size));
    }

    @GetMapping("/recommend")
    public ResponseVO<List<MaterialItemDTO>> recommend(
            @RequestParam(required = false) Long novelId,
            @RequestParam(required = false) String context,
            @RequestParam(required = false) String type,
            @RequestParam(defaultValue = "5") int limit) {
        return ResponseVO.success(materialItemService.recommendMaterials(novelId, context, type, limit));
    }

    @GetMapping("/{id}")
    public ResponseVO<MaterialItemDTO> get(@PathVariable Long id) {
        return ResponseVO.success(materialItemService.getById(id));
    }

    @PostMapping
    public ResponseVO<MaterialItemDTO> create(@Valid @RequestBody MaterialItemDTO dto) {
        return ResponseVO.success(materialItemService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<MaterialItemDTO> update(@PathVariable Long id, @Valid @RequestBody MaterialItemDTO dto) {
        return ResponseVO.success(materialItemService.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        materialItemService.delete(id);
        return ResponseVO.success();
    }

    /**
     * 记录素材使用次数
     */
    @PostMapping("/{id}/usage")
    public ResponseVO<Void> recordUsage(@PathVariable Long id) {
        materialItemService.recordUsage(id);
        return ResponseVO.success();
    }
}