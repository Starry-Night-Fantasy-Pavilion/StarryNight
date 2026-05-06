package com.starrynight.starrynight.system.community.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.community.dto.CommunityReportCreateDTO;
import com.starrynight.starrynight.system.community.service.CommunityReportService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/community/report")
public class CommunityReportController {

    @Autowired
    private CommunityReportService communityReportService;

    @PostMapping
    public ResponseVO<Void> create(@Valid @RequestBody CommunityReportCreateDTO body) {
        communityReportService.create(body);
        return ResponseVO.success();
    }
}

