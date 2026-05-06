package com.starrynight.starrynight.system.community.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.community.dto.CommunityWorkOrderDTO;
import com.starrynight.starrynight.system.community.service.CommunityWorkOrderService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import java.util.Map;

@RestController
@RequestMapping("/api/admin/community/work-orders")
@PreAuthorize("hasRole('ADMIN')")
public class AdminCommunityWorkOrderController {

    @Autowired
    private CommunityWorkOrderService communityWorkOrderService;

    @GetMapping("/list")
    public ResponseVO<PageVO<CommunityWorkOrderDTO>> list(
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "20") int size) {
        Page<CommunityWorkOrderDTO> p = communityWorkOrderService.list(page, size);
        return ResponseVO.success(PageVO.of(
                p.getTotal(),
                p.getRecords(),
                p.getCurrent(),
                p.getSize()));
    }

    @GetMapping("/stats")
    public ResponseVO<Map<String, Long>> stats() {
        return ResponseVO.success(Map.of("pendingCount", communityWorkOrderService.countPending()));
    }
}
