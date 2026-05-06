package com.starrynight.starrynight.system.novel.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.novel.dto.ContinueWritingRequestDTO;
import com.starrynight.starrynight.system.novel.dto.ContentExpandResultDTO;
import com.starrynight.starrynight.system.novel.service.ContentExpandService;
import jakarta.validation.Valid;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/ai")
public class AiContinueWritingController {

    private final ContentExpandService contentExpandService;

    public AiContinueWritingController(ContentExpandService contentExpandService) {
        this.contentExpandService = contentExpandService;
    }

    @PostMapping("/continue-writing")
    public ResponseVO<ContentExpandResultDTO> continueWriting(@Valid @RequestBody ContinueWritingRequestDTO req) {
        return ResponseVO.success(contentExpandService.continueWriting(req));
    }
}

