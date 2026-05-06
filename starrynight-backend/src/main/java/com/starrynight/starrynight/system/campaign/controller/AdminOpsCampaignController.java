package com.starrynight.starrynight.system.campaign.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.campaign.dto.OpsCampaignDTO;
import com.starrynight.starrynight.system.campaign.service.OpsCampaignService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/admin/campaigns")
@PreAuthorize("hasRole('ADMIN')")
@RequiredArgsConstructor
public class AdminOpsCampaignController {

    private final OpsCampaignService opsCampaignService;

    @GetMapping("/list")
    public ResponseVO<List<OpsCampaignDTO>> list() {
        return ResponseVO.success(opsCampaignService.listAll());
    }

    @GetMapping("/{id}")
    public ResponseVO<OpsCampaignDTO> get(@PathVariable Long id) {
        return ResponseVO.success(opsCampaignService.getById(id));
    }

    @PostMapping
    public ResponseVO<OpsCampaignDTO> create(@Valid @RequestBody OpsCampaignDTO dto) {
        return ResponseVO.success(opsCampaignService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<OpsCampaignDTO> update(@PathVariable Long id, @Valid @RequestBody OpsCampaignDTO dto) {
        return ResponseVO.success(opsCampaignService.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        opsCampaignService.delete(id);
        return ResponseVO.success();
    }
}
