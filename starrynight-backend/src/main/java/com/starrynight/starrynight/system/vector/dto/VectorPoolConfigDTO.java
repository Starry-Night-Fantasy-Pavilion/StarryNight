package com.starrynight.starrynight.system.vector.dto;

import lombok.Data;

@Data
public class VectorPoolConfigDTO {
    private Integer maxConnections;
    private Integer minIdle;
    private Integer connectionTimeout;
    private Long maxVectors;
    private Integer maxStorage;
}