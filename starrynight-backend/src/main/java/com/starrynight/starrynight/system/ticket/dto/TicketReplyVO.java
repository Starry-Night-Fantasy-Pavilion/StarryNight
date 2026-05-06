package com.starrynight.starrynight.system.ticket.dto;

import lombok.Data;

import java.time.LocalDateTime;

@Data
public class TicketReplyVO {
    private Long id;
    private Long ticketId;
    private String authorType;
    private Long authorId;
    private String authorName;
    private String content;
    private boolean internal;
    private LocalDateTime createTime;
}
