package com.starrynight.starrynight.system.portal;

import lombok.Data;

/** 用户端页脚联系行（公开 JSON，不含密钥） */
@Data
public class FooterContactLineVO {
    private String label;
    private String text;
    /** 可选，如 https:// 或 mailto: */
    private String href;
}
