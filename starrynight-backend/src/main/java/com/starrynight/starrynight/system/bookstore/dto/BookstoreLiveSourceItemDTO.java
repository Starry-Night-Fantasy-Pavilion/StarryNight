package com.starrynight.starrynight.system.bookstore.dto;

import com.fasterxml.jackson.annotation.JsonProperty;
import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

/** 前端「书源列表」项：id 即文档中的 sourceId，对应本站已配置书源规则的书目。 */
@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class BookstoreLiveSourceItemDTO {

    private Long id;

    /** 兼容阅读类书源的 bookSourceName */
    @JsonProperty("bookSourceName")
    private String name;

    /** 该书绑定的书源基准 URL（文档里补全相对 {@code url} 用，与书目书源 URL 同源） */
    private String baseUrl;
}
