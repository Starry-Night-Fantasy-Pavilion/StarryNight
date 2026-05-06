package com.starrynight.starrynight.system.stylesample.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.stylesample.dto.StyleExpandRequestDTO;
import com.starrynight.starrynight.system.stylesample.dto.StyleExpandResultDTO;
import com.starrynight.starrynight.system.stylesample.dto.StyleSampleDTO;
import com.starrynight.starrynight.system.stylesample.service.StyleSampleService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api")
public class StyleSampleController {

    @Autowired
    private StyleSampleService styleSampleService;

    // ==================== 风格样本管理 ====================

    @GetMapping("/style-samples/list")
    public ResponseVO<List<StyleSampleDTO>> list() {
        return ResponseVO.success(styleSampleService.list());
    }

    @PostMapping("/style-samples")
    public ResponseVO<StyleSampleDTO> create(@Valid @RequestBody StyleSampleDTO dto) {
        return ResponseVO.success(styleSampleService.create(dto));
    }

    @DeleteMapping("/style-samples/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        styleSampleService.delete(id);
        return ResponseVO.success();
    }

    // ==================== 风格分析 ====================

    /**
     * 分析文本风格指纹
     */
    @PostMapping("/style-analyze")
    public ResponseVO<Map<String, Object>> analyzeStyle(@RequestBody Map<String, String> request) {
        String text = request.getOrDefault("text", "");
        return ResponseVO.success(styleSampleService.analyzeStyleFingerprint(text));
    }

    // ==================== 风格扩写 ====================

    @PostMapping("/style-expand")
    public ResponseVO<StyleExpandResultDTO> expand(@Valid @RequestBody StyleExpandRequestDTO request) {
        return ResponseVO.success(styleSampleService.expand(request));
    }
}