package com.starrynight.starrynight.system.auth.vo;

import lombok.Data;

@Data
public class RealnameFeePayRequest {

    /** 易支付 {@code type}，如 alipay、wxpay；空则默认 alipay */
    private String payType;
}
