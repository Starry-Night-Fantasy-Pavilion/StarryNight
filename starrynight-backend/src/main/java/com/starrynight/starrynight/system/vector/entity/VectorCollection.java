package com.starrynight.starrynight.system.vector.entity;

import com.baomidou.mybatisplus.annotation.IdType;
import com.baomidou.mybatisplus.annotation.TableId;
import com.baomidou.mybatisplus.annotation.TableName;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("t_vector_collection")
public class VectorCollection {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String name;

    private String type;

    private Integer vectorCount;

    private Integer dimension;

    private String embeddingModel;

    private String distance;

    private Integer maxVectors;

    private String status;

    private LocalDateTime createTime;

    private LocalDateTime updateTime;
}