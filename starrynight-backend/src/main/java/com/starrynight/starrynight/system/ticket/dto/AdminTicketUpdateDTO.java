package com.starrynight.starrynight.system.ticket.dto;

import lombok.Data;

@Data
public class AdminTicketUpdateDTO {

    /** OPEN / IN_PROGRESS / RESOLVED / CLOSED */
    private String status;

    /** LOW / NORMAL / HIGH / URGENT */
    private String priority;

    private Long assignedTo;

    private String closeReason;
}
