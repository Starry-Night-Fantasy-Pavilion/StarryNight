package com.starrynight.starrynight.system.billing.epay;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.auth.realname.RealnameVerificationService;
import com.starrynight.starrynight.system.auth.vo.RealnameFeePayVO;
import com.starrynight.starrynight.system.billing.PayMethodCodes;
import com.starrynight.starrynight.system.billing.entity.RechargeRecord;
import com.starrynight.starrynight.system.billing.mapper.RechargeRecordMapper;
import com.starrynight.starrynight.system.billing.service.RechargeService;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.LinkedHashMap;
import java.util.Locale;
import java.util.Map;

@Slf4j
@Service
@RequiredArgsConstructor
public class RealnameFeeEpayService {

    private final RuntimeConfigService runtimeConfigService;
    private final RechargeService rechargeService;
    private final RechargeRecordMapper rechargeRecordMapper;
    private final AuthUserRepository authUserRepository;

    /**
     * 创建易支付 GET 跳转链接；需已登录用户，且运营已开启认证费与易支付。
     *
     * @param payType 易支付 {@code type} 参数，常见 {@code alipay}、{@code wxpay}
     */
    public RealnameFeePayVO createPayUrl(long userId, String payType) {
        assertEpayReady();
        BigDecimal feeYuan = RealnameVerificationService.readConfiguredFeeYuanPublic(runtimeConfigService);
        if (feeYuan.compareTo(BigDecimal.ZERO) <= 0) {
            throw new BusinessException("当前未配置有效的实名认证费金额");
        }
        AuthUser user = authUserRepository.selectById(userId);
        if (user == null || user.getDeleted() != 0 || (user.getIsAdmin() != null && user.getIsAdmin() == 1)) {
            throw new BusinessException(401, "Unauthorized");
        }
        if (user.getRealNameVerified() != null && user.getRealNameVerified() == 1) {
            throw new BusinessException("已通过实名核验，无需缴费");
        }

        String type = StringUtils.hasText(payType) ? payType.trim().toLowerCase(Locale.ROOT) : "alipay";
        RechargeRecord record = rechargeService.createRealnameFeePendingOrder(userId, feeYuan);

        String backendBase = normalizeBase(runtimeConfigService.getString("auth.oauth.public-base-url", ""));
        if (!StringUtils.hasText(backendBase)) {
            throw new BusinessException("请先配置站点公网根 URL（auth.oauth.public-base-url），用于支付异步通知");
        }
        String notifyUrl = backendBase + "/api/auth/realname/fee/epay/notify";
        String returnUrl = backendBase + "/profile?realnameFeePaid=1";

        String pid = runtimeConfigService.getString("payment.epay.pid", "").trim();
        String gateway = runtimeConfigService.getString("payment.epay.gateway", "").trim();
        String key = runtimeConfigService.getString("payment.epay.key", "");
        String signType = runtimeConfigService.getString("payment.epay.sign-type", "md5").trim();
        if (!StringUtils.hasText(signType)) {
            signType = "md5";
        }

        Map<String, String> signParams = new LinkedHashMap<>();
        signParams.put("pid", pid);
        signParams.put("type", type);
        signParams.put("out_trade_no", record.getRecordNo());
        signParams.put("notify_url", notifyUrl);
        signParams.put("return_url", returnUrl);
        signParams.put("name", "实名认证费");
        signParams.put("money", record.getAmount().setScale(2, RoundingMode.HALF_UP).toPlainString());
        signParams.put("sign_type", signType);

        String sign = EpayMd5SignUtil.sign(signParams, key);
        signParams.put("sign", sign);

        String payUrl = gateway + (gateway.contains("?") ? "&" : "?") + buildQuery(signParams);

        RealnameFeePayVO vo = new RealnameFeePayVO();
        vo.setPayUrl(payUrl);
        vo.setRecordNo(record.getRecordNo());
        vo.setAmountYuan(record.getAmount());
        vo.setPayType(type);
        return vo;
    }

    /**
     * 易支付异步通知：验签成功后更新订单并标记用户已缴认证费。
     *
     * @return 应答给易支付网关的纯文本，须为 {@code success} 表示处理成功
     */
    @Transactional(rollbackFor = Exception.class)
    public String handleEpayNotify(Map<String, String> params) {
        if (params == null || params.isEmpty()) {
            return "fail";
        }
        String key = runtimeConfigService.getString("payment.epay.key", "");
        if (!StringUtils.hasText(key) || !EpayMd5SignUtil.verify(params, key)) {
            log.warn("epay realname fee notify: bad sign or missing key");
            return "fail";
        }
        String configuredPid = runtimeConfigService.getString("payment.epay.pid", "").trim();
        String pid = firstNonBlank(params.get("pid"));
        if (StringUtils.hasText(configuredPid) && !configuredPid.equals(pid)) {
            log.warn("epay realname fee notify: pid mismatch");
            return "fail";
        }
        String tradeStatus = firstNonBlank(params.get("trade_status"));
        if (!"TRADE_SUCCESS".equalsIgnoreCase(tradeStatus)) {
            log.info("epay realname fee notify: non-success status {}", tradeStatus);
            return "success";
        }
        String outTradeNo = firstNonBlank(params.get("out_trade_no"));
        if (!StringUtils.hasText(outTradeNo)) {
            return "fail";
        }
        String tradeNo = firstNonBlank(params.get("trade_no"));
        String moneyRaw = firstNonBlank(params.get("money"));
        if (!StringUtils.hasText(moneyRaw)) {
            return "fail";
        }
        BigDecimal paidMoney;
        try {
            paidMoney = new BigDecimal(moneyRaw).setScale(2, RoundingMode.HALF_UP);
        } catch (NumberFormatException e) {
            return "fail";
        }

        RechargeRecord record = rechargeRecordMapper.selectOne(
                new LambdaQueryWrapper<RechargeRecord>().eq(RechargeRecord::getRecordNo, outTradeNo));
        if (record == null) {
            log.warn("epay realname fee notify: unknown out_trade_no {}", outTradeNo);
            return "fail";
        }
        if (!PayMethodCodes.REALNAME_FEE.equalsIgnoreCase(record.getPayMethod())) {
            log.warn("epay realname fee notify: record not REALNAME_FEE {}", outTradeNo);
            return "fail";
        }
        if (paidMoney.compareTo(record.getAmount().setScale(2, RoundingMode.HALF_UP)) != 0) {
            log.warn("epay realname fee notify: money mismatch expect {} got {}", record.getAmount(), paidMoney);
            return "fail";
        }
        if ("SUCCESS".equalsIgnoreCase(record.getPayStatus())) {
            return "success";
        }
        if (!"PENDING".equalsIgnoreCase(record.getPayStatus())) {
            return "fail";
        }

        record.setPayStatus("SUCCESS");
        record.setPayTime(java.time.LocalDateTime.now());
        if (StringUtils.hasText(tradeNo)) {
            record.setTransactionId(tradeNo.length() > 120 ? tradeNo.substring(0, 120) : tradeNo);
        }
        rechargeRecordMapper.updateById(record);

        AuthUser user = authUserRepository.selectById(record.getUserId());
        if (user != null && user.getDeleted() == 0) {
            user.setRealnameFeePaidRecordNo(record.getRecordNo());
            authUserRepository.updateById(user);
        }
        log.info("epay realname fee paid: userId={}, recordNo={}", record.getUserId(), record.getRecordNo());
        return "success";
    }

    /** 个人中心展示：当前账号是否已有一笔与运营配置金额一致的成功认证费支付。 */
    public boolean hasValidCashRealnameFee(AuthUser user, BigDecimal feeYuan) {
        if (feeYuan.compareTo(BigDecimal.ZERO) <= 0) {
            return true;
        }
        String ref = user.getRealnameFeePaidRecordNo();
        if (!StringUtils.hasText(ref)) {
            return false;
        }
        RechargeRecord r = rechargeRecordMapper.selectOne(
                new LambdaQueryWrapper<RechargeRecord>().eq(RechargeRecord::getRecordNo, ref.trim()));
        if (r == null
                || !user.getId().equals(r.getUserId())
                || !PayMethodCodes.REALNAME_FEE.equalsIgnoreCase(r.getPayMethod())
                || !"SUCCESS".equalsIgnoreCase(r.getPayStatus())) {
            return false;
        }
        return r.getAmount().setScale(2, RoundingMode.HALF_UP).compareTo(feeYuan.setScale(2, RoundingMode.HALF_UP)) == 0;
    }

    /** 发起人脸核验前调用：已配置认证费则必须存在一笔成功的易支付认证费订单且金额一致。 */
    public void assertCashFeePaidIfRequired(AuthUser user, BigDecimal feeYuan) {
        if (feeYuan.compareTo(BigDecimal.ZERO) <= 0) {
            return;
        }
        if (hasValidCashRealnameFee(user, feeYuan)) {
            return;
        }
        if (!StringUtils.hasText(user.getRealnameFeePaidRecordNo())) {
            throw new BusinessException(
                    "实名认证需先支付 "
                            + feeYuan.stripTrailingZeros().toPlainString()
                            + " 元（易支付）。请在个人中心点击「支付认证费」完成付款后再发起核验");
        }
        throw new BusinessException("认证费支付未完成或与当前配置金额不一致，请重新支付认证费");
    }

    private void assertEpayReady() {
        if (!runtimeConfigService.getBoolean("payment.epay.enabled", false)) {
            throw new BusinessException("未启用易支付（payment.epay.enabled），无法收取实名认证费");
        }
        String gw = runtimeConfigService.getString("payment.epay.gateway", "").trim();
        String pid = runtimeConfigService.getString("payment.epay.pid", "").trim();
        String key = runtimeConfigService.getString("payment.epay.key", "");
        if (!StringUtils.hasText(gw) || !StringUtils.hasText(pid) || !StringUtils.hasText(key)) {
            throw new BusinessException("易支付网关、PID 或密钥未配置完整，请在运营后台「支付配置」中填写");
        }
    }

    private static String buildQuery(Map<String, String> params) {
        StringBuilder q = new StringBuilder();
        int i = 0;
        for (Map.Entry<String, String> e : params.entrySet()) {
            if (i++ > 0) {
                q.append('&');
            }
            q.append(urlEnc(e.getKey()))
                    .append('=')
                    .append(urlEnc(e.getValue()));
        }
        return q.toString();
    }

    private static String urlEnc(String s) {
        return URLEncoder.encode(s == null ? "" : s, StandardCharsets.UTF_8).replace("+", "%20");
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

    private static String firstNonBlank(String s) {
        if (!StringUtils.hasText(s)) {
            return "";
        }
        return s.trim();
    }
}
