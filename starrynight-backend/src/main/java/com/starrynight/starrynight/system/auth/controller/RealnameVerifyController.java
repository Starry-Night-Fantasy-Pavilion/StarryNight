package com.starrynight.starrynight.system.auth.controller;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.auth.realname.AlipayRealnameGateway;
import com.starrynight.starrynight.system.auth.realname.RealnameVerificationService;
import com.starrynight.starrynight.system.auth.vo.RealnameFeePayRequest;
import com.starrynight.starrynight.system.auth.vo.RealnameFeePayVO;
import com.starrynight.starrynight.system.auth.vo.RealnameStartVO;
import com.starrynight.starrynight.system.billing.epay.RealnameFeeEpayService;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.MediaType;
import org.springframework.util.StringUtils;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.util.Enumeration;
import java.util.HashMap;
import java.util.Map;

/**
 * 实名人脸核验：支付宝开放平台 / 第三方 HTTP（Ovooa 等）。回调须登记站点公网根下路径。
 */
@RestController
@RequestMapping("/api/auth/realname")
public class RealnameVerifyController {

    @Autowired
    private RealnameVerificationService realnameVerificationService;
    @Autowired
    private AlipayRealnameGateway alipayRealnameGateway;
    @Autowired
    private RuntimeConfigService runtimeConfigService;
    @Autowired
    private ObjectMapper objectMapper;
    @Autowired
    private RealnameFeeEpayService realnameFeeEpayService;

    @PostMapping("/start")
    public ResponseVO<RealnameStartVO> start() throws Exception {
        Long uid = ThreadLocalUtil.getUserId();
        if (uid == null) {
            throw new BusinessException(401, "请先登录");
        }
        return ResponseVO.success(realnameVerificationService.start(uid));
    }

    /**
     * 创建实名认证费易支付跳转链接（需登录；运营须开启认证费并配置 payment.epay.*）。
     */
    @PostMapping("/fee/create-pay")
    public ResponseVO<RealnameFeePayVO> createRealnameFeePay(@RequestBody(required = false) RealnameFeePayRequest body) {
        Long uid = ThreadLocalUtil.getUserId();
        if (uid == null) {
            throw new BusinessException(401, "请先登录");
        }
        String payType = body != null ? body.getPayType() : null;
        return ResponseVO.success(realnameFeeEpayService.createPayUrl(uid, payType));
    }

    /**
     * 易支付异步通知（GET/POST，常见为 x-www-form-urlencoded），成功须返回纯文本 {@code success}。
     */
    @RequestMapping(value = "/fee/epay/notify", method = {RequestMethod.GET, RequestMethod.POST})
    public String epayRealnameFeeNotify(HttpServletRequest request) {
        Map<String, String> m = new HashMap<>();
        Enumeration<String> names = request.getParameterNames();
        while (names.hasMoreElements()) {
            String k = names.nextElement();
            m.put(k, request.getParameter(k));
        }
        return realnameFeeEpayService.handleEpayNotify(m);
    }

    /**
     * 支付宝异步通知（application/x-www-form-urlencoded），成功须返回小写 success。
     */
    @PostMapping(value = "/alipay/notify", consumes = MediaType.APPLICATION_FORM_URLENCODED_VALUE)
    public String alipayNotify(HttpServletRequest request) {
        return realnameVerificationService.handleAlipayNotify(alipayRealnameGateway.toSingleValueMap(request.getParameterMap()));
    }

    @GetMapping("/alipay/return")
    public void alipayReturn(HttpServletResponse response) throws IOException {
        String site = runtimeConfigService.getString("auth.oauth.public-base-url", "").trim();
        String base = site.replaceAll("/+$", "");
        if (!StringUtils.hasText(base)) {
            base = "/";
        }
        response.sendRedirect(base + "/auth/realname-result?channel=alipay");
    }

    /** Ovooa / 喵雨欣等平台服务端回调（JSON body），需在对方控制台登记为本 URL。 */
    @PostMapping("/ovooa/callback")
    public ResponseVO<String> ovooaCallback(HttpServletRequest request) throws IOException {
        byte[] buf = request.getInputStream().readAllBytes();
        String raw = new String(buf, StandardCharsets.UTF_8);
        JsonNode node = objectMapper.readTree(StringUtils.hasText(raw) ? raw : "{}");
        realnameVerificationService.handleOvooaCallback(node, request);
        return ResponseVO.success("ok");
    }
}
