package com.starrynight.starrynight.system.ai.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.ai.dto.AiModelDTO;
import com.starrynight.starrynight.system.ai.service.AdminAiConfigService;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

/**
 * 对齐开发文档用户侧接口：
 * GET /api/models
 */
@RestController
@RequestMapping("/api/models")
public class AiModelController {

    private final AdminAiConfigService adminAiConfigService;

    public AiModelController(AdminAiConfigService adminAiConfigService) {
        this.adminAiConfigService = adminAiConfigService;
    }

    @GetMapping
    public ResponseVO<List<AiModelDTO>> listAvailableModels(@RequestParam(required = false) Long billingChannelId) {
        return ResponseVO.success(adminAiConfigService.listEnabledModels(billingChannelId));
    }
}

