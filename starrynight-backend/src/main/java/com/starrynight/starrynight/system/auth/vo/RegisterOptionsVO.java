package com.starrynight.starrynight.system.auth.vo;

import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;

@Data
@NoArgsConstructor
@AllArgsConstructor
public class RegisterOptionsVO {

    /** 是否允许在注册时填写邮箱（关闭后前端可不展示，后端拒绝带邮箱注册） */
    private boolean emailRegisterEnabled;

    /** 是否允许在注册时填写手机号 */
    private boolean phoneRegisterEnabled;

    /** 站点是否开启实名（个人中心登记证件；未核验则限制导出等） */
    private boolean realNameVerificationEnabled;

    /**
     * alipay：支付宝开放平台人脸核验；
     * ovooa：喵雨欣开发平台（HTTP 模板；miaoyuxin 为同义别名）。
     * 实名关闭时为 basic（仅占位，与核验无关）。
     */
    private String realNameVerifyProvider = "basic";
}
