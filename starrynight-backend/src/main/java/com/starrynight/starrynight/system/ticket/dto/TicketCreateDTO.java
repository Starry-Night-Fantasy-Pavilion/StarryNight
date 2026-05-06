package com.starrynight.starrynight.system.ticket.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.Data;

@Data
public class TicketCreateDTO {

    @NotBlank(message = "工单分类不能为空")
    private String category;

    @NotBlank(message = "标题不能为空")
    @Size(max = 200, message = "标题不超过200字")
    private String title;

    @NotBlank(message = "内容不能为空")
    @Size(max = 5000, message = "内容不超过5000字")
    private String content;
}
