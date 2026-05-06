package com.starrynight.engine.consistency;

import lombok.Data;

@Data
public class ConsistencyIssue {
    private String category;
    private String severity;
    private String message;
    private String suggestion;
    /** 同人/特摄等子模块使用的细分类 */
    private String type;
    private String description;
    private String location;
    private boolean autoFixAvailable;
    private String autoFixAction;
}

