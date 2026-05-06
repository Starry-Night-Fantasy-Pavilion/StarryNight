package com.starrynight.starrynight.system.ticket.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.ticket.dto.AdminTicketUpdateDTO;
import com.starrynight.starrynight.system.ticket.dto.TicketReplyDTO;
import com.starrynight.starrynight.system.ticket.dto.TicketVO;
import com.starrynight.starrynight.system.ticket.service.SupportTicketService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.Map;

@RestController
@RequestMapping("/api/admin/tickets")
@PreAuthorize("hasRole('ADMIN')")
public class AdminSupportTicketController {

    @Autowired
    private SupportTicketService ticketService;

    @GetMapping
    public ResponseVO<PageVO<TicketVO>> list(
            @RequestParam(required = false) String status,
            @RequestParam(required = false) String category,
            @RequestParam(required = false) String keyword,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "20") int size) {
        Page<TicketVO> p = ticketService.adminList(status, category, keyword, page, size);
        return ResponseVO.success(PageVO.of(p.getTotal(), p.getRecords(), p.getCurrent(), p.getSize()));
    }

    @GetMapping("/stats")
    public ResponseVO<Map<String, Long>> stats() {
        return ResponseVO.success(Map.of("openCount", ticketService.countOpen()));
    }

    @GetMapping("/{id}")
    public ResponseVO<TicketVO> get(@PathVariable Long id) {
        return ResponseVO.success(ticketService.adminGet(id));
    }

    @PutMapping("/{id}")
    public ResponseVO<Void> update(@PathVariable Long id, @RequestBody AdminTicketUpdateDTO dto) {
        ticketService.adminUpdate(id, dto);
        return ResponseVO.success();
    }

    @PostMapping("/{id}/reply")
    public ResponseVO<Void> reply(@PathVariable Long id, @Valid @RequestBody TicketReplyDTO dto) {
        ticketService.adminReply(id, dto);
        return ResponseVO.success();
    }

    @PostMapping("/{id}/close")
    public ResponseVO<Void> close(@PathVariable Long id, @RequestBody(required = false) Map<String, String> body) {
        String reason = body != null ? body.get("reason") : null;
        ticketService.adminClose(id, reason);
        return ResponseVO.success();
    }
}
