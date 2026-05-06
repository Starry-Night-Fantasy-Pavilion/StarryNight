package com.starrynight.starrynight.system.auth.realname;

import java.util.HashMap;
import java.util.Map;

/**
 * 支付宝实名核验网关；有 {@code alipay-sdk-java} 时为 {@link AlipaySdkRealnameGateway}，否则为 {@link NoopAlipayRealnameGateway}。
 */
public interface AlipayRealnameGateway {

    String buildFaceRedirectUrl(long userId, String realName, String idCardNo, String notifyUrl, String returnUrl)
            throws Exception;

    Long resolveUserIdAfterNotify(Map<String, String> params) throws Exception;

    boolean queryPassed(String certifyId) throws Exception;

    default Map<String, String> toSingleValueMap(Map<String, String[]> parameterMap) {
        Map<String, String> m = new HashMap<>();
        if (parameterMap == null) {
            return m;
        }
        parameterMap.forEach((k, v) -> {
            if (v != null && v.length > 0 && v[0] != null) {
                m.put(k, v[0]);
            }
        });
        return m;
    }
}
