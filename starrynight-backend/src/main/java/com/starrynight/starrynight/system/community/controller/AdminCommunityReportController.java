package com.starrynight.starrynight.system.community.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.community.dto.AdminCommunityReportDTO;
import com.starrynight.starrynight.system.community.dto.CommunityReportHandleRequest;
import com.starrynight.starrynight.system.community.service.CommunityReportService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.Map;

@RestController
@RequestMapping("/api/admin/community/reports")
@PreAuthorize("hasRole('ADMIN')")
public class AdminCommunityReportController {

    @Autowired
    private CommunityReportService communityReportService;

    @GetMapping("/list")
    public ResponseVO<PageVO<AdminCommunityReportDTO>> list(
            @RequestParam(required = false) Integer status,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "20") int size) {
        Page<AdminCommunityReportDTO> p = communityReportService.adminList(status, page, size);
        return ResponseVO.success(PageVO.of(p.getTotal(), p.getRecords(), p.getCurrent(), p.getSize()));
    }

    @GetMapping("/stats")
    public ResponseVO<Map<String, Long>> stats() {
        return ResponseVO.success(Map.of("pendingCount", communityReportService.countPending()));
    }

    @PostMapping("/{id}/ignore")
    public ResponseVO<Void> ignore(@PathVariable Long id, @RequestBody(required = false) CommunityReportHandleRequest body) {
        String note = body != null ? body.getNote() : null;
        communityReportService.adminIgnore(id, note);
        return ResponseVO.success();
    }

    @PostMapping("/{id}/resolve")
    public ResponseVO<Void> resolve(@PathVariable Long id, @RequestBody(required = false) CommunityReportHandleRequest body) {
        String action = body != null ? body.getAction() : null;
        String note = body != null ? body.getNote() : null;
        communityReportService.adminResolve(id, action, note);
        return ResponseVO.success();
    }
}

