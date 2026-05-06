package com.starrynight.starrynight.system.billing.task;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.system.billing.entity.DailyFreeQuotaLog;
import com.starrynight.starrynight.system.billing.entity.UserBalance;
import com.starrynight.starrynight.system.user.entity.UserProfile;
import com.starrynight.starrynight.system.billing.mapper.DailyFreeQuotaLogMapper;
import com.starrynight.starrynight.system.billing.mapper.UserBalanceMapper;
import com.starrynight.starrynight.system.billing.service.BillingConfigService;
import com.starrynight.starrynight.system.billing.service.BillingService;
import com.starrynight.starrynight.system.user.mapper.UserProfileMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.time.LocalDateTime;

@Slf4j
@Component
@RequiredArgsConstructor
public class DailyFreeQuotaTask {

    private final UserBalanceMapper userBalanceMapper;
    private final UserProfileMapper userProfileMapper;
    private final DailyFreeQuotaLogMapper freeQuotaLogMapper;
    private final BillingService billingService;
    private final BillingConfigService configService;

    @Scheduled(cron = "0 0 0 * * ?")
    @Transactional(rollbackFor = Exception.class)
    public void grantDailyFreeQuota() {
        log.info("Starting daily free quota grant task...");

        Long dailyQuota = configService.getDailyFreeQuota();
        LocalDate today = LocalDate.now();

        LambdaQueryWrapper<UserBalance> balanceQuery = new LambdaQueryWrapper<>();
        balanceQuery.isNotNull(UserBalance::getUserId);
        var balances = userBalanceMapper.selectList(balanceQuery);

        int grantedCount = 0;
        for (UserBalance balance : balances) {
            try {
                String userGroup = getUserGroup(balance.getUserId());

                Long userQuota = getUserQuota(dailyQuota, userGroup);

                billingService.grantDailyFreeQuota(balance.getUserId(), userQuota, userGroup);

                DailyFreeQuotaLog logEntry = new DailyFreeQuotaLog();
                logEntry.setUserId(balance.getUserId());
                logEntry.setQuotaDate(today);
                logEntry.setGrantedQuota(userQuota);
                logEntry.setUserGroup(userGroup);
                freeQuotaLogMapper.insert(logEntry);

                grantedCount++;
            } catch (Exception e) {
                log.error("Failed to grant quota for user {}: {}", balance.getUserId(), e.getMessage());
            }
        }

        log.info("Daily free quota grant completed: total={}, granted={}", balances.size(), grantedCount);
    }

    private String getUserGroup(Long userId) {
        UserProfile profile = userProfileMapper.selectOne(new LambdaQueryWrapper<UserProfile>()
                .eq(UserProfile::getUserId, userId));

        if (profile == null) {
            return "NORMAL";
        }

        if (profile.getMemberExpireTime() != null && profile.getMemberExpireTime().isBefore(LocalDateTime.now())) {
            return "NORMAL";
        }

        switch (profile.getMemberLevel()) {
            case 3:
                return "SVIP";
            case 2:
                return "VIP";
            default:
                return "NORMAL";
        }
    }

    private Long getUserQuota(Long baseQuota, String userGroup) {
        switch (userGroup) {
            case "SVIP":
                return baseQuota * 10;
            case "VIP":
                return baseQuota * 5;
            case "TEST":
                return baseQuota * 100;
            default:
                return baseQuota;
        }
    }
}
