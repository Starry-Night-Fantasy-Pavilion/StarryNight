package com.starrynight.starrynight.system.campaign.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.campaign.dto.OpsCampaignDTO;
import com.starrynight.starrynight.system.campaign.service.OpsCampaignService;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

@RestController
@RequestMapping("/api/campaigns")
@RequiredArgsConstructor
public class CampaignPublicController {

    private final OpsCampaignService opsCampaignService;

    @GetMapping("/visible")
    public ResponseVO<List<OpsCampaignDTO>> visible() {
        return ResponseVO.success(opsCampaignService.listPublishedVisible());
    }
}
