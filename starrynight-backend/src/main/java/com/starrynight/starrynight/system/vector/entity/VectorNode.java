package com.starrynight.starrynight.system.vector.entity;

import com.baomidou.mybatisplus.annotation.IdType;
import com.baomidou.mybatisplus.annotation.TableId;
import com.baomidou.mybatisplus.annotation.TableName;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("t_vector_node")
public class VectorNode {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String name;

    private String host;

    private Integer port;

    private String apiKey;

    private Integer maxVectors;

    private Integer maxStorage;

    private String status;

    private Integer enabled;

    private LocalDateTime createTime;

    private LocalDateTime updateTime;
}