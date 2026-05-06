package com.starrynight.starrynight.system.bookstore.dto;

import lombok.Data;

@Data
public class BookstoreSyncRequestDTO {

    /** 最多抓取章节数（含已存在跳过），默认 80 */
    private Integer maxChapters = 80;

    /** 是否覆盖已有章节正文 */
    private Boolean overwrite = false;

    /** 章节请求间隔毫秒，防止过快请求对方站点 */
    private Integer requestDelayMs = 150;
}
