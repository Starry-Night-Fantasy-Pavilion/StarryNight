package com.starrynight.starrynight.system.community.dto;

import lombok.Data;

@Data
public class CommunityReportHandleRequest {

    /** NONE / TAKE_DOWN_POST / DELETE_COMMENT */
    private String action;

    /** 可选 */
    private String note;
}

