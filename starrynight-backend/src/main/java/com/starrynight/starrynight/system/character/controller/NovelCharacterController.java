package com.starrynight.starrynight.system.character.controller;

import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.character.dto.NovelCharacterDTO;
import com.starrynight.starrynight.system.character.service.NovelCharacterService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/characters")
public class NovelCharacterController {

    @Autowired
    private NovelCharacterService novelCharacterService;

    @GetMapping("/list")
    public ResponseVO<PageVO<NovelCharacterDTO>> list(
            @RequestParam(required = false) String keyword,
            @RequestParam(required = false) Long novelId,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "12") int size) {
        return ResponseVO.success(novelCharacterService.list(keyword, novelId, page, size));
    }

    @GetMapping("/{id}")
    public ResponseVO<NovelCharacterDTO> get(@PathVariable Long id) {
        return ResponseVO.success(novelCharacterService.getById(id));
    }

    @PostMapping
    public ResponseVO<NovelCharacterDTO> create(@Valid @RequestBody NovelCharacterDTO dto) {
        return ResponseVO.success(novelCharacterService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<NovelCharacterDTO> update(@PathVariable Long id, @Valid @RequestBody NovelCharacterDTO dto) {
        return ResponseVO.success(novelCharacterService.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        novelCharacterService.delete(id);
        return ResponseVO.success();
    }

    // ==================== 关系图谱 ====================

    /**
     * 获取作品的角色关系图谱
     */
    @GetMapping("/graph")
    public ResponseVO<Map<String, Object>> getRelationshipGraph(
            @RequestParam(required = false) Long novelId) {
        return ResponseVO.success(novelCharacterService.getRelationshipGraph(novelId));
    }

    /**
     * 导出角色
     */
    @GetMapping("/export")
    public ResponseVO<List<NovelCharacterDTO>> exportCharacters(@RequestParam Long novelId) {
        return ResponseVO.success(novelCharacterService.exportCharacters(novelId));
    }

    /**
     * 导入角色
     */
    @PostMapping("/import")
    public ResponseVO<Void> importCharacters(@RequestParam Long novelId,
                                            @RequestBody List<NovelCharacterDTO> characters) {
        novelCharacterService.importCharacters(novelId, characters);
        return ResponseVO.success();
    }
}