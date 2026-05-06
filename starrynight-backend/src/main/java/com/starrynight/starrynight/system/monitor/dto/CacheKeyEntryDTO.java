package com.starrynight.starrynight.system.monitor.dto;

import lombok.Builder;
import lombok.Data;

@Data
@Builder
public class CacheKeyEntryDTO {

    private String key;

    private Long ttlSeconds;

    private String valuePreview;
}
