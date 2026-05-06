package com.starrynight.starrynight.system.auth.realname;

import com.fasterxml.jackson.databind.JsonNode;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.auth.service.AuthService;
import com.starrynight.starrynight.system.auth.vo.RealnameStartVO;
import com.starrynight.starrynight.system.billing.epay.RealnameFeeEpayService;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import jakarta.servlet.http.HttpServletRequest;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.util.Locale;
import java.util.Map;

@Service
public class RealnameVerificationService {

    private static final Logger log = LoggerFactory.getLogger(RealnameVerificationService.class);

    @Autowired
    private RuntimeConfigService runtimeConfigService;
    @Autowired
    private AuthUserRepository authUserRepository;
    @Autowired
    private AuthService authService;
    @Autowired
    private AlipayRealnameGateway alipayRealnameGateway;
    @Autowired
    private OvooaRealnameGateway ovooaRealnameGateway;
    @Autowired
    private RealnameFeeEpayService realnameFeeEpayService;

    public static String normalizedProvider(RuntimeConfigService cfg) {
        if (!cfg.getBoolean("auth.realname.enabled", false)) {
            return "basic";
        }
        String p = cfg.getString("auth.realname.verify_provider", "alipay").trim().toLowerCase(Locale.ROOT);
        if ("miaoyuxin".equals(p)) {
            p = "ovooa";
        }
        if ("basic".equals(p)) {
            p = "alipay";
        }
        if ("alipay".equals(p) || "ovooa".equals(p)) {
            return p;
        }
        return "alipay";
    }

    @Transactional(rollbackFor = Exception.class)
    public RealnameStartVO start(long userId) throws Exception {
        AuthUser user = authUserRepository.selectById(userId);
        if (user == null || user.getDeleted() != 0 || (user.getIsAdmin() != null && user.getIsAdmin() == 1)) {
            throw new BusinessException(401, "Unauthorized");
        }
        if (!runtimeConfigService.getBoolean("auth.realname.enabled", false)) {
            throw new BusinessException("未开启实名认证");
        }
        if (!StringUtils.hasText(user.getRealName()) || !StringUtils.hasText(user.getIdCardNo())) {
            throw new BusinessException("请先在个人中心填写真实姓名与证件号并保存，再发起核验");
        }
        if (user.getRealNameVerified() != null && user.getRealNameVerified() == 1) {
            throw new BusinessException("已通过实名核验");
        }
        BigDecimal feeYuan = readConfiguredFeeYuan();
        if (feeYuan.compareTo(BigDecimal.ZERO) > 0) {
            realnameFeeEpayService.assertCashFeePaidIfRequired(user, feeYuan);
        }

        String provider = normalizedProvider(runtimeConfigService);

        String backendBase = normalizeBase(runtimeConfigService.getString("auth.oauth.public-base-url", ""));
        if (!StringUtils.hasText(backendBase)) {
            throw new BusinessException("请先配置站点公网根 URL（auth.oauth.public-base-url），用于回调通知");
        }
        RealnameStartVO vo = new RealnameStartVO();
        if (feeYuan.compareTo(BigDecimal.ZERO) > 0) {
            vo.setFeeChargedYuan(feeYuan);
        }
        if ("alipay".equals(provider)) {
            String notifyUrl = backendBase + "/api/auth/realname/alipay/notify";
            String returnUrl = backendBase + "/auth/realname-result?channel=alipay";
            String jump = alipayRealnameGateway.buildFaceRedirectUrl(
                    userId, user.getRealName().trim(), user.getIdCardNo().trim(), notifyUrl, returnUrl);
            vo.setMode("ALIPAY");
            vo.setRedirectUrl(jump);
            return vo;
        }
        if ("ovooa".equals(provider)) {
            String notifyUrl = backendBase + "/api/auth/realname/ovooa/callback";
            String jump =
                    ovooaRealnameGateway.invokeForRedirectUrl(userId, user.getRealName().trim(), user.getIdCardNo().trim(), notifyUrl);
            vo.setMode("MIAOYUXIN");
            vo.setRedirectUrl(jump);
            return vo;
        }
        throw new BusinessException("不支持的实名核验方式");
    }

    /**
     * 运营配置：是否收取认证费（人民币元，经易支付收取，不走星夜币）。
     */
    public static BigDecimal readConfiguredFeeYuanPublic(RuntimeConfigService cfg) {
        if (!cfg.getBoolean("auth.realname.fee.enabled", false)) {
            return BigDecimal.ZERO;
        }
        String raw = cfg.getString("auth.realname.fee.amount-yuan", "0").trim();
        if (!StringUtils.hasText(raw)) {
            return BigDecimal.ZERO;
        }
        try {
            BigDecimal d = new BigDecimal(raw);
            return d.compareTo(BigDecimal.ZERO) <= 0 ? BigDecimal.ZERO : d.setScale(2, RoundingMode.HALF_UP);
        } catch (NumberFormatException ignored) {
            return BigDecimal.ZERO;
        }
    }

    private BigDecimal readConfiguredFeeYuan() {
        return readConfiguredFeeYuanPublic(runtimeConfigService);
    }

    /**
     * 站点开启实名时，导出用户创作内容前须已通过核验（{@code auth_user.real_name_verified = 1}）。
     */
    public void requireVerifiedForContentExport(Long userId) {
        if (userId == null) {
            throw new BusinessException(401, "Unauthorized");
        }
        if (!runtimeConfigService.getBoolean("auth.realname.enabled", false)) {
            return;
        }
        AuthUser u = authUserRepository.selectById(userId);
        if (u == null || u.getDeleted() != 0) {
            throw new BusinessException(401, "Unauthorized");
        }
        if (u.getRealNameVerified() == null || u.getRealNameVerified() != 1) {
            throw new BusinessException(403, "请先完成实名核验后再导出内容，可在个人中心填写证件信息并发起核验");
        }
    }

    public String handleAlipayNotify(Map<String, String> params) {
        try {
            Long uid = alipayRealnameGateway.resolveUserIdAfterNotify(params);
            if (uid == null) {
                return "failure";
            }
            String certifyId = params.get("certify_id");
            markVerified(uid, certifyId != null ? certifyId.trim() : "alipay");
            return "success";
        } catch (Exception e) {
            log.warn("alipay realname notify handling failed", e);
            return "failure";
        }
    }

    public void handleOvooaCallback(JsonNode body, HttpServletRequest request) {
        String secretCfg = runtimeConfigService.getString("auth.realname.ovooa.callback-secret", "").trim();
        String headerName = runtimeConfigService.getString("auth.realname.ovooa.callback-secret-header", "X-Realname-Secret").trim();
        if (StringUtils.hasText(secretCfg) && StringUtils.hasText(headerName)) {
            String incoming = request.getHeader(headerName);
            if (!secretCfg.equals(incoming)) {
                throw new BusinessException(403, "回调密钥无效");
            }
        }
        if (body == null || body.isNull()) {
            throw new BusinessException("回调体为空");
        }
        if (!ovooaIndicatesSuccess(body)) {
            log.info("ovooa realname callback indicates failure: {}", body);
            throw new BusinessException("核验未通过");
        }
        long userId = extractUserId(body);
        if (userId <= 0) {
            throw new BusinessException("回调缺少 user_id");
        }
        String outer = firstText(body, "order_no", "trade_no", "serial_no", "request_id");
        markVerified(userId, StringUtils.hasText(outer) ? outer : "ovooa");
    }

    public void markVerified(long userId, String outerNo) {
        AuthUser u = authUserRepository.selectById(userId);
        if (u == null || u.getDeleted() != 0) {
            return;
        }
        u.setRealNameVerified(1);
        u.setRealnameFeePaidRecordNo(null);
        if (StringUtils.hasText(outerNo)) {
            String o = outerNo.trim();
            u.setRealNameVerifyOuterNo(o.length() > 80 ? o.substring(0, 80) : o);
        } else {
            u.setRealNameVerifyOuterNo(null);
        }
        authUserRepository.updateById(u);
        authService.evictUserInfoCache(userId);
    }

    private static boolean ovooaIndicatesSuccess(JsonNode body) {
        if (body.path("success").asBoolean(false)) {
            return true;
        }
        if (body.path("passed").asBoolean(false)) {
            return true;
        }
        if (body.path("code").asInt(-1) == 0) {
            return true;
        }
        String st = body.path("status").asText("");
        return "SUCCESS".equalsIgnoreCase(st) || "OK".equalsIgnoreCase(st);
    }

    private static long extractUserId(JsonNode body) {
        long v = readLongId(body.get("user_id"));
        if (v > 0) {
            return v;
        }
        v = readLongId(body.get("userId"));
        if (v > 0) {
            return v;
        }
        JsonNode data = body.get("data");
        if (data != null && data.isObject()) {
            v = readLongId(data.get("user_id"));
            if (v > 0) {
                return v;
            }
        }
        return -1L;
    }

    private static long readLongId(JsonNode n) {
        if (n == null || n.isNull()) {
            return -1L;
        }
        if (n.isNumber()) {
            return n.asLong();
        }
        if (n.isTextual()) {
            try {
                return Long.parseLong(n.asText().trim());
            } catch (NumberFormatException ignored) {
                return -1L;
            }
        }
        return -1L;
    }

    private static String firstText(JsonNode body, String... keys) {
        for (String k : keys) {
            String t = body.path(k).asText("");
            if (StringUtils.hasText(t)) {
                return t.trim();
            }
        }
        return "";
    }

    private static String normalizeBase(String raw) {
        if (!StringUtils.hasText(raw)) {
            return "";
        }
        String s = raw.trim();
        while (s.endsWith("/")) {
            s = s.substring(0, s.length() - 1);
        }
        return s;
    }

}
