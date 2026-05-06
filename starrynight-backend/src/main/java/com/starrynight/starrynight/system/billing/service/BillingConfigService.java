package com.starrynight.starrynight.system.billing.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.system.billing.entity.BillingConfig;
import com.starrynight.starrynight.system.billing.mapper.BillingConfigMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;

@Slf4j
@Service
@RequiredArgsConstructor
public class BillingConfigService {

    private final BillingConfigMapper configMapper;

    public Long getDailyFreeQuota() {
        String value = configMapper.selectValueByKey("daily_free_quota");
        return value != null ? Long.parseLong(value) : 10000L;
    }

    public BigDecimal getDefaultProfitMargin() {
        String value = configMapper.selectValueByKey("default_profit_margin");
        return value != null ? new BigDecimal(value) : new BigDecimal("0.30");
    }

    public Boolean getMixedPaymentDefault() {
        String value = configMapper.selectValueByKey("mixed_payment_default");
        return value != null ? Boolean.parseBoolean(value) : true;
    }

    public Integer getFreeQuotaResetHour() {
        String value = configMapper.selectValueByKey("free_quota_reset_hour");
        return value != null ? Integer.parseInt(value) : 0;
    }

    @Transactional(rollbackFor = Exception.class)
    public void updateConfig(String key, String value) {
        BillingConfig config = configMapper.selectOne(new LambdaQueryWrapper<BillingConfig>()
                .eq(BillingConfig::getConfigKey, key));

        if (config != null && config.getEditable() == 1) {
            config.setConfigValue(value);
            configMapper.updateById(config);
            log.info("Billing config updated: {} = {}", key, value);
        }
    }

    public String getConfigValue(String key) {
        return configMapper.selectValueByKey(key);
    }
}
