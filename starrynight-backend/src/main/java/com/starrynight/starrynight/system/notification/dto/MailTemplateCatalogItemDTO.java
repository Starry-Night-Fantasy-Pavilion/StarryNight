package com.starrynight.starrynight.system.notification.dto;

import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.util.Map;

@Data
@NoArgsConstructor
@AllArgsConstructor
public class MailTemplateCatalogItemDTO {

    private String key;

    private String title;

    /** VERIFY 或 CAMPAIGN */
    private String category;

    private String placeholderHint;

    private String description;

    /** 预览用占位符示例 */
    private Map<String, String> previewSampleVariables;
}
