package com.starrynight.starrynight.system.vector.dto;

import lombok.Data;

@Data
public class VectorCollectionDTO {
    private Long id;
    private String name;
    private String type;
    private Integer vectorCount;
    private Integer dimension;
    private String embeddingModel;
    private String distance;
    private Integer maxVectors;
    private String status;
}