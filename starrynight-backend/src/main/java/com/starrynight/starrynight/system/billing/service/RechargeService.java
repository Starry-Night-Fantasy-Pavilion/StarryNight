package com.starrynight.starrynight.system.billing.service;

import com.alibaba.fastjson2.JSON;
import com.alibaba.fastjson2.JSONArray;
import com.alibaba.fastjson2.JSONObject;
import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.system.billing.dto.RechargeRequest;
import com.starrynight.starrynight.system.billing.dto.RechargeResult;
import com.starrynight.starrynight.system.billing.entity.RechargeRecord;
import com.starrynight.starrynight.system.billing.entity.UserBalance;
import com.starrynight.starrynight.system.billing.mapper.RechargeRecordMapper;
import com.starrynight.starrynight.system.billing.mapper.UserBalanceMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.util.UUID;

import static com.starrynight.starrynight.system.billing.PayMethodCodes.REALNAME_FEE;

@Slf4j
@Service
@RequiredArgsConstructor
public class RechargeService {

    private final RechargeRecordMapper rechargeRecordMapper;
    private final UserBalanceMapper userBalanceMapper;
    private final BillingConfigService configService;

    private static final BigDecimal PLATFORM_CURRENCY_RATE = new BigDecimal("10");

    @Transactional(rollbackFor = Exception.class)
    public RechargeResult createRechargeOrder(RechargeRequest request) {
        String recordNo = generateRecordNo();

        BigDecimal platformCurrencyRate = getPlatformCurrencyRate();
        BigDecimal bonusCurrency = calculateBonus(request.getAmount());

        RechargeRecord record = new RechargeRecord();
        record.setRecordNo(recordNo);
        record.setUserId(request.getUserId());
        record.setAmount(request.getAmount());
        record.setPlatformCurrency(request.getAmount().multiply(platformCurrencyRate));
        record.setBonusCurrency(bonusCurrency);
        record.setPayMethod(request.getPayMethod());
        record.setPayStatus("PENDING");
        rechargeRecordMapper.insert(record);

        RechargeResult result = new RechargeResult();
        result.setRecordNo(recordNo);
        result.setAmount(request.getAmount());
        result.setPlatformCurrency(record.getPlatformCurrency());
        result.setBonusCurrency(bonusCurrency);
        result.setPayStatus("PENDING");

        log.info("Recharge order created: recordNo={}, userId={}, amount={}, platformCurrency={}",
                recordNo, request.getUserId(), request.getAmount(), record.getPlatformCurrency());

        return result;
    }

    /**
     * 实名认证费待支付订单：不入账星夜币，仅作易支付对账与 {@link com.starrynight.starrynight.system.auth.entity.AuthUser#realnameFeePaidRecordNo} 关联。
     */
    @Transactional(rollbackFor = Exception.class)
    public RechargeRecord createRealnameFeePendingOrder(long userId, BigDecimal amountYuan) {
        String recordNo = "RF" + System.currentTimeMillis() + UUID.randomUUID().toString().substring(0, 8).toUpperCase();
        RechargeRecord record = new RechargeRecord();
        record.setRecordNo(recordNo);
        record.setUserId(userId);
        record.setAmount(amountYuan.setScale(2, RoundingMode.HALF_UP));
        record.setPlatformCurrency(BigDecimal.ZERO);
        record.setBonusCurrency(BigDecimal.ZERO);
        record.setPayMethod(REALNAME_FEE);
        record.setPayStatus("PENDING");
        rechargeRecordMapper.insert(record);
        log.info("Realname fee order created: recordNo={}, userId={}, amount={}", recordNo, userId, record.getAmount());
        return record;
    }

    @Transactional(rollbackFor = Exception.class)
    public void confirmRecharge(String recordNo, String transactionId) {
        RechargeRecord record = rechargeRecordMapper.selectOne(new LambdaQueryWrapper<RechargeRecord>()
                .eq(RechargeRecord::getRecordNo, recordNo));

        if (record == null) {
            throw new RuntimeException("Recharge record not found");
        }
        if (REALNAME_FEE.equalsIgnoreCase(record.getPayMethod())) {
            throw new IllegalStateException("实名认证费订单请走易支付回调逻辑，勿调用 confirmRecharge");
        }

        if (!"PENDING".equals(record.getPayStatus())) {
            log.warn("Recharge already processed: recordNo={}, status={}", recordNo, record.getPayStatus());
            return;
        }

        record.setPayStatus("SUCCESS");
        record.setPayTime(java.time.LocalDateTime.now());
        record.setTransactionId(transactionId);
        rechargeRecordMapper.updateById(record);

        UserBalance balance = userBalanceMapper.selectOne(new LambdaQueryWrapper<UserBalance>()
                .eq(UserBalance::getUserId, record.getUserId()));

        if (balance == null) {
            balance = new UserBalance();
            balance.setUserId(record.getUserId());
            balance.setFreeQuota(0L);
            balance.setFreeQuotaDate(java.time.LocalDate.now());
            balance.setPlatformCurrency(BigDecimal.ZERO);
            balance.setEnableMixedPayment(1);
            balance.setTotalFreeUsed(0L);
            balance.setTotalPaidUsed(0L);
            balance.setTotalRecharged(0L);
            userBalanceMapper.insert(balance);
        }

        BigDecimal totalCurrency = record.getPlatformCurrency().add(record.getBonusCurrency());
        balance.setPlatformCurrency(balance.getPlatformCurrency().add(totalCurrency));
        balance.setTotalRecharged(balance.getTotalRecharged() + record.getAmount().longValue());
        userBalanceMapper.updateById(balance);

        log.info("Recharge confirmed: recordNo={}, userId={}, currency={}",
                recordNo, record.getUserId(), totalCurrency);
    }

    @Transactional(rollbackFor = Exception.class)
    public void failRecharge(String recordNo) {
        RechargeRecord record = rechargeRecordMapper.selectOne(new LambdaQueryWrapper<RechargeRecord>()
                .eq(RechargeRecord::getRecordNo, recordNo));

        if (record != null) {
            record.setPayStatus("FAILED");
            rechargeRecordMapper.updateById(record);
            log.info("Recharge failed: recordNo={}", recordNo);
        }
    }

    private BigDecimal calculateBonus(BigDecimal amount) {
        String bonusRuleJson = configService.getConfigValue("recharge_bonus_rule");
        if (bonusRuleJson == null || bonusRuleJson.isEmpty()) {
            return BigDecimal.ZERO;
        }

        try {
            JSONObject bonusRule = JSON.parseObject(bonusRuleJson);
            JSONArray tiers = bonusRule.getJSONArray("tiers");

            BigDecimal maxBonus = BigDecimal.ZERO;
            for (int i = 0; i < tiers.size(); i++) {
                JSONObject tier = tiers.getJSONObject(i);
                BigDecimal minAmount = tier.getBigDecimal("min_amount");
                BigDecimal bonus = tier.getBigDecimal("bonus");

                if (amount.compareTo(minAmount) >= 0 && bonus.compareTo(maxBonus) > 0) {
                    maxBonus = bonus;
                }
            }

            return maxBonus;
        } catch (Exception e) {
            log.error("Failed to parse bonus rule: {}", e.getMessage());
            return BigDecimal.ZERO;
        }
    }

    /** 人民币元 →平台币 换算倍率（与充值入账一致），供实名认证费等场景复用。 */
    public BigDecimal getPlatformCurrencyRate() {
        String rate = configService.getConfigValue("platform_currency_rate");
        return rate != null ? new BigDecimal(rate) : PLATFORM_CURRENCY_RATE;
    }

    private String generateRecordNo() {
        return "RC" + System.currentTimeMillis() + UUID.randomUUID().toString().substring(0, 8).toUpperCase();
    }
}
