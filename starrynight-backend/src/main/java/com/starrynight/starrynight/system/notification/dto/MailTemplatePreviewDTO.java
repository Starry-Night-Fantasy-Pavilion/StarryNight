package com.starrynight.starrynight.system.notification.dto;

import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;

@Data
@NoArgsConstructor
@AllArgsConstructor
public class MailTemplatePreviewDTO {

    private String subject;

    /** 可直接用于 iframe srcdoc 的完整 HTML 文档 */
    private String htmlDocument;

    /** HTML_FILE=磁盘模板；PLAIN_TEXT=仅用数据库纯文本（已包装为简单 HTML） */
    private String bodySource;
}
