package com.starrynight.starrynight.system.auth.vo;

import lombok.Data;

import java.math.BigDecimal;

@Data
public class RealnameStartVO {

    /** ALIPAY | MIAOYUXIN（喵雨欣开发平台，原 ovooa 通道） */
    private String mode;

    /** 浏览器跳转打开的人脸核验页 */
    private String redirectUrl;

    /** 运营配置的认证费（元）；与易支付已缴记录校验通过时返回，便于前端展示 */
    private BigDecimal feeChargedYuan;
}
