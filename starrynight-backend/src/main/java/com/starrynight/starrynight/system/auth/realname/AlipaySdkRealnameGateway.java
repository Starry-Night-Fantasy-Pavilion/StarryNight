package com.starrynight.starrynight.system.auth.realname;

import com.alibaba.fastjson2.JSONObject;
import com.alipay.api.AlipayApiException;
import com.alipay.api.AlipayClient;
import com.alipay.api.DefaultAlipayClient;
import com.alipay.api.internal.util.AlipaySignature;
import com.alipay.api.request.AlipayUserCertifyOpenCertifyRequest;
import com.alipay.api.request.AlipayUserCertifyOpenInitializeRequest;
import com.alipay.api.request.AlipayUserCertifyOpenQueryRequest;
import com.alipay.api.response.AlipayUserCertifyOpenCertifyResponse;
import com.alipay.api.response.AlipayUserCertifyOpenInitializeResponse;
import com.alipay.api.response.AlipayUserCertifyOpenQueryResponse;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.util.StringUtils;

import java.util.Map;

/**
 * 支付宝开放平台「身份核验」实现；由 {@link AlipayRealnameConfiguration} 在 classpath 存在 SDK 时注册，避免无 jar 时类加载失败。
 */
public final class AlipaySdkRealnameGateway implements AlipayRealnameGateway {

    private static final Logger log = LoggerFactory.getLogger(AlipaySdkRealnameGateway.class);

    private final RuntimeConfigService runtimeConfigService;
    private final RealnameCertifyPendingStore pendingStore;

    public AlipaySdkRealnameGateway(RuntimeConfigService runtimeConfigService, RealnameCertifyPendingStore pendingStore) {
        this.runtimeConfigService = runtimeConfigService;
        this.pendingStore = pendingStore;
    }

    @Override
    public String buildFaceRedirectUrl(long userId, String realName, String idCardNo, String notifyUrl, String returnUrl)
            throws AlipayApiException {
        AlipayClient client = clientOrThrow();
        String outerOrderNo = "RN" + userId + "_" + System.currentTimeMillis();

        JSONObject identity = new JSONObject();
        identity.put("identity_type", "CERT_INFO");
        identity.put("cert_type", "IDENTITY_CARD");
        identity.put("cert_name", realName);
        identity.put("cert_no", idCardNo);

        JSONObject merchant = new JSONObject();
        merchant.put("return_url", returnUrl);

        JSONObject biz = new JSONObject();
        biz.put("outer_order_no", outerOrderNo);
        biz.put("biz_code", faceBizCode());
        biz.put("identity_param", identity);
        biz.put("merchant_config", merchant);

        AlipayUserCertifyOpenInitializeRequest initReq = new AlipayUserCertifyOpenInitializeRequest();
        initReq.setBizContent(biz.toJSONString());
        AlipayUserCertifyOpenInitializeResponse initRes = client.execute(initReq);
        if (!initRes.isSuccess()) {
            throw new BusinessException("支付宝核验初始化失败：" + firstNonBlank(initRes.getSubMsg(), initRes.getMsg()));
        }
        String certifyId = initRes.getCertifyId();
        if (!StringUtils.hasText(certifyId)) {
            throw new BusinessException("支付宝未返回 certify_id");
        }
        pendingStore.put(certifyId, userId);
        pendingStore.put(outerOrderNo, userId);

        JSONObject certifyBiz = new JSONObject();
        certifyBiz.put("certify_id", certifyId);
        AlipayUserCertifyOpenCertifyRequest certifyReq = new AlipayUserCertifyOpenCertifyRequest();
        certifyReq.setBizContent(certifyBiz.toJSONString());
        AlipayUserCertifyOpenCertifyResponse certifyRes = client.execute(certifyReq);
        if (!certifyRes.isSuccess()) {
            throw new BusinessException("支付宝获取核验地址失败：" + firstNonBlank(certifyRes.getSubMsg(), certifyRes.getMsg()));
        }
        String url = extractCertifyJumpUrl(certifyRes);
        if (!StringUtils.hasText(url)) {
            throw new BusinessException("支付宝未返回核验跳转 URL");
        }
        return url.trim();
    }

    @Override
    public Long resolveUserIdAfterNotify(Map<String, String> params) throws AlipayApiException {
        String pub = runtimeConfigService.getString("auth.realname.alipay.alipay-public-key", "").trim();
        if (!StringUtils.hasText(pub)) {
            log.warn("alipay realname notify skipped: missing alipay public key");
            return null;
        }
        if (!AlipaySignature.rsaCheckV1(params, pub, "UTF-8", "RSA2")) {
            log.warn("alipay realname notify signature mismatch");
            return null;
        }
        String certifyId = params.get("certify_id");
        if (!StringUtils.hasText(certifyId)) {
            return null;
        }
        if (!queryPassed(certifyId.trim())) {
            return null;
        }
        Long uid = pendingStore.consume(certifyId.trim());
        String outer = params.get("outer_order_no");
        if (uid == null && StringUtils.hasText(outer)) {
            uid = pendingStore.consume(outer.trim());
        }
        return uid;
    }

    @Override
    public boolean queryPassed(String certifyId) throws AlipayApiException {
        AlipayClient client = clientOrThrow();
        JSONObject biz = new JSONObject();
        biz.put("certify_id", certifyId);
        AlipayUserCertifyOpenQueryRequest q = new AlipayUserCertifyOpenQueryRequest();
        q.setBizContent(biz.toJSONString());
        AlipayUserCertifyOpenQueryResponse res = client.execute(q);
        if (!res.isSuccess()) {
            log.warn("alipay certify query failed: {}", res.getSubMsg());
            return false;
        }
        String passed = res.getPassed();
        return "T".equalsIgnoreCase(passed);
    }

    private AlipayClient clientOrThrow() {
        String appId = runtimeConfigService.getString("auth.realname.alipay.app-id", "").trim();
        String pk = runtimeConfigService.getString("auth.realname.alipay.private-key", "").trim();
        String gateway = runtimeConfigService.getString("auth.realname.alipay.gateway", "https://openapi.alipay.com/gateway.do")
                .trim();
        if (!StringUtils.hasText(appId) || !StringUtils.hasText(pk)) {
            throw new BusinessException("未配置支付宝 AppID 或应用私钥");
        }
        String pub = runtimeConfigService.getString("auth.realname.alipay.alipay-public-key", "").trim();
        return new DefaultAlipayClient(gateway, appId, pk, "json", "UTF-8", pub, "RSA2");
    }

    private String faceBizCode() {
        String c = runtimeConfigService.getString("auth.realname.alipay.face-biz-code", "FACE_CERTIFY").trim();
        return StringUtils.hasText(c) ? c : "FACE_CERTIFY";
    }

    private static String extractCertifyJumpUrl(AlipayUserCertifyOpenCertifyResponse certifyRes) {
        try {
            String body = certifyRes.getBody();
            if (StringUtils.hasText(body)) {
                JSONObject jo = JSONObject.parseObject(body);
                String u = jo.getString("certify_url");
                if (!StringUtils.hasText(u)) {
                    u = jo.getString("certifyUrl");
                }
                if (StringUtils.hasText(u)) {
                    return u.trim();
                }
            }
        } catch (Exception ignored) {
            // fall through
        }
        return "";
    }

    private static String firstNonBlank(String a, String b) {
        if (StringUtils.hasText(a)) {
            return a.trim();
        }
        if (StringUtils.hasText(b)) {
            return b.trim();
        }
        return "未知错误";
    }
}
