package com.starrynight.starrynight.system.vector.dto;

import lombok.Data;

@Data
public class VectorStatsDTO {
    private Integer totalNodes;
    private String totalVectors;
    private String storageUsed;
    private String clusterStatus;
}