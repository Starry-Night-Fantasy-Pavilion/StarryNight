package com.starrynight.starrynight.system.prompt.controller;

import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.prompt.dto.PromptTemplateDTO;
import com.starrynight.starrynight.system.prompt.service.PromptTemplateService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/prompts")
public class PromptTemplateController {

    @Autowired
    private PromptTemplateService promptTemplateService;

    @GetMapping("/list")
    public ResponseVO<PageVO<PromptTemplateDTO>> list(
            @RequestParam(required = false) String keyword,
            @RequestParam(required = false) String category,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "12") int size) {
        return ResponseVO.success(promptTemplateService.list(keyword, category, page, size));
    }

    @GetMapping("/{id}")
    public ResponseVO<PromptTemplateDTO> get(@PathVariable Long id) {
        return ResponseVO.success(promptTemplateService.getById(id));
    }

    @PostMapping
    public ResponseVO<PromptTemplateDTO> create(@Valid @RequestBody PromptTemplateDTO dto) {
        return ResponseVO.success(promptTemplateService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<PromptTemplateDTO> update(@PathVariable Long id, @Valid @RequestBody PromptTemplateDTO dto) {
        return ResponseVO.success(promptTemplateService.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        promptTemplateService.delete(id);
        return ResponseVO.success();
    }

    @PostMapping("/{id}/apply")
    public ResponseVO<String> apply(@PathVariable Long id, @RequestBody java.util.Map<String, String> variables) {
        String result = promptTemplateService.applyPrompt(id, variables);
        return ResponseVO.success(result);
    }

    /**
     * 获取所有分类列表
     */
    @GetMapping("/categories")
    public ResponseVO<List<String>> listCategories() {
        return ResponseVO.success(promptTemplateService.listCategories());
    }
}