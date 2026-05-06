package com.starrynight.starrynight.system.ai.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.ai.dto.AiGenerationParamsDTO;
import com.starrynight.starrynight.system.ai.dto.AiModelDTO;
import com.starrynight.starrynight.system.ai.dto.AiSensitiveWordDTO;
import com.starrynight.starrynight.system.ai.dto.AiTemplateDTO;
import com.starrynight.starrynight.system.ai.service.AdminAiConfigService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/admin/ai")
@PreAuthorize("hasRole('ADMIN')")
public class AdminAiConfigController {

    @Autowired
    private AdminAiConfigService adminAiConfigService;

    @GetMapping("/models")
    public ResponseVO<List<AiModelDTO>> listModels(@RequestParam(required = false) Long billingChannelId) {
        return ResponseVO.success(adminAiConfigService.listModels(billingChannelId));
    }

    @PostMapping("/models")
    public ResponseVO<AiModelDTO> createModel(@Valid @RequestBody AiModelDTO dto) {
        return ResponseVO.success(adminAiConfigService.createModel(dto));
    }

    @PutMapping("/models/{id}")
    public ResponseVO<AiModelDTO> updateModel(@PathVariable Long id, @Valid @RequestBody AiModelDTO dto) {
        return ResponseVO.success(adminAiConfigService.updateModel(id, dto));
    }

    @DeleteMapping("/models/{id}")
    public ResponseVO<Void> deleteModel(@PathVariable Long id) {
        adminAiConfigService.deleteModel(id);
        return ResponseVO.success();
    }

    @GetMapping("/sensitive-words")
    public ResponseVO<List<AiSensitiveWordDTO>> listSensitiveWords(@RequestParam(required = false) Integer level) {
        return ResponseVO.success(adminAiConfigService.listSensitiveWords(level));
    }

    @PostMapping("/sensitive-words")
    public ResponseVO<AiSensitiveWordDTO> createSensitiveWord(@Valid @RequestBody AiSensitiveWordDTO dto) {
        return ResponseVO.success(adminAiConfigService.createSensitiveWord(dto));
    }

    @PutMapping("/sensitive-words/{id}")
    public ResponseVO<AiSensitiveWordDTO> updateSensitiveWord(@PathVariable Long id, @Valid @RequestBody AiSensitiveWordDTO dto) {
        return ResponseVO.success(adminAiConfigService.updateSensitiveWord(id, dto));
    }

    @DeleteMapping("/sensitive-words/{id}")
    public ResponseVO<Void> deleteSensitiveWord(@PathVariable Long id) {
        adminAiConfigService.deleteSensitiveWord(id);
        return ResponseVO.success();
    }

    @GetMapping("/templates")
    public ResponseVO<List<AiTemplateDTO>> listTemplates(@RequestParam(required = false) String type) {
        return ResponseVO.success(adminAiConfigService.listTemplates(type));
    }

    @PostMapping("/templates")
    public ResponseVO<AiTemplateDTO> createTemplate(@Valid @RequestBody AiTemplateDTO dto) {
        return ResponseVO.success(adminAiConfigService.createTemplate(dto));
    }

    @PutMapping("/templates/{id}")
    public ResponseVO<AiTemplateDTO> updateTemplate(@PathVariable Long id, @Valid @RequestBody AiTemplateDTO dto) {
        return ResponseVO.success(adminAiConfigService.updateTemplate(id, dto));
    }

    @DeleteMapping("/templates/{id}")
    public ResponseVO<Void> deleteTemplate(@PathVariable Long id) {
        adminAiConfigService.deleteTemplate(id);
        return ResponseVO.success();
    }

    /** 生成参数（与模板、模型同属运营端 AI 配置，路径统一在 /api/admin/ai 下） */
    @GetMapping("/config/params")
    public ResponseVO<AiGenerationParamsDTO> getGenerationParams() {
        return ResponseVO.success(adminAiConfigService.getGenerationParams());
    }

    @PostMapping("/config/params")
    public ResponseVO<Void> saveGenerationParams(@Valid @RequestBody AiGenerationParamsDTO dto) {
        adminAiConfigService.saveGenerationParams(dto);
        return ResponseVO.success();
    }
}
