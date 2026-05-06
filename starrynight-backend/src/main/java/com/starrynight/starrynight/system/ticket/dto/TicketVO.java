package com.starrynight.starrynight.system.ticket.dto;

import lombok.Data;

import java.time.LocalDateTime;
import java.util.List;

@Data
public class TicketVO {
    private Long id;
    private String ticketNo;
    private Long userId;
    private String username;
    private String category;
    private String title;
    private String content;
    private String status;
    private String priority;
    private Long assignedTo;
    private String assignedToName;
    private String closeReason;
    private LocalDateTime resolvedAt;
    private LocalDateTime createTime;
    private LocalDateTime updateTime;
    private List<TicketReplyVO> replies;
    private int replyCount;
}
