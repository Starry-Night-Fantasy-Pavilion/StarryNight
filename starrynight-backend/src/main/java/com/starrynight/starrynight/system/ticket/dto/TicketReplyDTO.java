package com.starrynight.starrynight.system.ticket.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.Data;

@Data
public class TicketReplyDTO {

    @NotBlank(message = "回复内容不能为空")
    @Size(max = 2000, message = "回复内容不超过2000字")
    private String content;

    /** 仅管理端有效，是否内部备注 */
    private Boolean internal;
}
