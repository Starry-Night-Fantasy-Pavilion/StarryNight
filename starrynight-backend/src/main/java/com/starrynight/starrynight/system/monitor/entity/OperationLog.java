package com.starrynight.starrynight.system.monitor.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("operation_log")
public class OperationLog {

    @TableId(type = IdType.AUTO)
    private Long id;

    private Long userId;

    private String username;

    private String operation;

    private String module;

    private String method;

    private String requestUrl;

    private String requestMethod;

    private String requestParams;

    private String responseData;

    private String ipAddress;

    private String userAgent;

    private Integer status;

    private String errorMessage;

    private Integer executionTime;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;
}

