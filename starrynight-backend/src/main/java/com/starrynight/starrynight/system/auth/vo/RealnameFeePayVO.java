package com.starrynight.starrynight.system.auth.vo;

import lombok.Data;

import java.math.BigDecimal;

@Data
public class RealnameFeePayVO {

    /** 浏览器跳转打开的易支付收银台完整 URL（GET） */
    private String payUrl;

    /** 本地业务单号，对应 {@code recharge_record.record_no} */
    private String recordNo;

    private BigDecimal amountYuan;

    /** 易支付通道类型，如 alipay、wxpay */
    private String payType;
}
