package com.starrynight.starrynight.system.vector.dto;

import lombok.Data;

@Data
public class VectorNodeDTO {
    private Long id;
    private String name;
    private String host;
    private Integer port;
    private String apiKey;
    private Integer maxVectors;
    private Integer maxStorage;
    private String status;
    private Integer enabled;
    private Long vectorCount;
    private Integer load;
    private String storageUsed;
    private String address;
}