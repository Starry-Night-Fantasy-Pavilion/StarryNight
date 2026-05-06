package com.starrynight.starrynight.system.ticket.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.ticket.dto.TicketCreateDTO;
import com.starrynight.starrynight.system.ticket.dto.TicketReplyDTO;
import com.starrynight.starrynight.system.ticket.dto.TicketVO;
import com.starrynight.starrynight.system.ticket.service.SupportTicketService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/tickets")
@PreAuthorize("isAuthenticated()")
public class SupportTicketController {

    @Autowired
    private SupportTicketService ticketService;

    @PostMapping
    public ResponseVO<TicketVO> create(@Valid @RequestBody TicketCreateDTO dto) {
        return ResponseVO.success(ticketService.userCreate(dto));
    }

    @GetMapping
    public ResponseVO<PageVO<TicketVO>> list(
            @RequestParam(required = false) String status,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size) {
        Page<TicketVO> p = ticketService.userList(status, page, size);
        return ResponseVO.success(PageVO.of(p.getTotal(), p.getRecords(), p.getCurrent(), p.getSize()));
    }

    @GetMapping("/{id}")
    public ResponseVO<TicketVO> get(@PathVariable Long id) {
        return ResponseVO.success(ticketService.userGet(id));
    }

    @PostMapping("/{id}/reply")
    public ResponseVO<Void> reply(@PathVariable Long id, @Valid @RequestBody TicketReplyDTO dto) {
        ticketService.userReply(id, dto);
        return ResponseVO.success();
    }
}
