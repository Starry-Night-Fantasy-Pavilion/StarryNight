package com.starrynight.starrynight.system.notification.dto;

import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;

@Data
@NoArgsConstructor
@AllArgsConstructor
public class MailTemplateHtmlStatusDTO {

    private boolean hasFile;

    private long sizeBytes;

    /** 文件最后修改时间（毫秒时间戳），无文件时为 null */
    private Long lastModifiedMillis;
}
