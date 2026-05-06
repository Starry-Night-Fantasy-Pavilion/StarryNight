package com.starrynight.starrynight.system.storage.dto;

import lombok.Data;

@Data
public class StorageConfigDTO {
    private Long id;
    private String name;
    private String type;
    private String endpoint;
    private String accessKey;
    private String secretKey;
    private String bucket;
    private String domain;
    private Boolean enabled;
    private Boolean isDefault;
    private Long totalStorage;
    private Long usedStorage;
    private String status;
}