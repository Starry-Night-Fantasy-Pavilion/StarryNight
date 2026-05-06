package com.starrynight.starrynight.system.auth.realname;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import org.springframework.boot.autoconfigure.condition.ConditionalOnMissingClass;
import org.springframework.stereotype.Service;

import java.util.Map;

/**
 * 未引入 {@code alipay-sdk-java} 时的占位实现，保证 Spring 能创建 {@link AlipayRealnameGateway}；选用 alipay 核验时会得到明确错误提示。
 */
@Service
@ConditionalOnMissingClass("com.alipay.api.AlipayClient")
public class NoopAlipayRealnameGateway implements AlipayRealnameGateway {

    private static final String MSG = "运行环境未加载支付宝 SDK（com.alipay.api.AlipayClient）。请在 pom 中保留 alipay-sdk-java 并确认启动类路径包含该 JAR，或将核验方式改为喵雨欣开发平台（ovooa）。";

    @Override
    public String buildFaceRedirectUrl(long userId, String realName, String idCardNo, String notifyUrl, String returnUrl) {
        throw new BusinessException(MSG);
    }

    @Override
    public Long resolveUserIdAfterNotify(Map<String, String> params) {
        return null;
    }

    @Override
    public boolean queryPassed(String certifyId) {
        return false;
    }
}
