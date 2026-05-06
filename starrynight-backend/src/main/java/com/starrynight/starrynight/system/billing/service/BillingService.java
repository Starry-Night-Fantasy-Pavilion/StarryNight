package com.starrynight.starrynight.system.billing.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.conditions.update.LambdaUpdateWrapper;
import com.starrynight.starrynight.system.billing.dto.*;
import com.starrynight.starrynight.system.billing.entity.*;
import com.starrynight.starrynight.system.billing.mapper.*;
import com.starrynight.starrynight.system.user.mapper.UserProfileMapper;
import com.starrynight.starrynight.system.user.entity.UserProfile;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.UUID;

@Slf4j
@Service
@RequiredArgsConstructor
public class BillingService {

    private final UserBalanceMapper userBalanceMapper;
    private final BillingChannelMapper channelMapper;
    private final BillingRecordMapper recordMapper;
    private final BillingConfigMapper configMapper;
    private final BillingMarginConfigMapper marginConfigMapper;
    private final UserProfileMapper userProfileMapper;

    private static final BigDecimal PLATFORM_CURRENCY_RATE = new BigDecimal("10");
    private static final Long CREATION_POINT_RATE = 1000L;
    private static final BigDecimal DEFAULT_PROFIT_MARGIN = new BigDecimal("0.30");

    @Transactional(rollbackFor = Exception.class)
    public ChargeResult charge(ChargeRequest request) {
        String recordNo = generateRecordNo();

        UserBalance balance = getOrCreateUserBalance(request.getUserId());
        LocalDate today = LocalDate.now();

        if (balance.getFreeQuotaDate() == null || !balance.getFreeQuotaDate().equals(today)) {
            balance.setFreeQuota(0L);
            balance.setFreeQuotaDate(today);
        }

        BigDecimal profitMargin = getEffectiveProfitMargin(request.getContentType(), request.getUserId());
        BillingChannel channel = selectChannel(request.getChannelId());

        BigDecimal channelCost = calculateChannelCost(channel, request);
        BigDecimal userPrice = channelCost.multiply(BigDecimal.ONE.add(profitMargin));
        Integer creationPoints = userPrice.multiply(new BigDecimal("1000")).setScale(0, RoundingMode.CEILING).intValue();

        Long freeQuota = balance.getFreeQuota();
        Long pointsNeeded = creationPoints.longValue();
        Long paidPointsAvailable = balance.getPlatformCurrency().multiply(new BigDecimal(CREATION_POINT_RATE)).longValue();

        Integer freePointsUsed = 0;
        Integer paidPointsUsed = 0;
        BigDecimal platformCurrencyUsed = BigDecimal.ZERO;

        if (freeQuota >= pointsNeeded) {
            freePointsUsed = pointsNeeded.intValue();
            balance.setFreeQuota(freeQuota - pointsNeeded);
        } else if (balance.getEnableMixedPayment() == 1 && paidPointsAvailable > 0) {
            freePointsUsed = freeQuota.intValue();
            Long remaining = pointsNeeded - freeQuota;
            Long paidPointsToUse = Math.min(remaining, paidPointsAvailable);
            paidPointsUsed = paidPointsToUse.intValue();
            platformCurrencyUsed = new BigDecimal(paidPointsToUse).divide(new BigDecimal(CREATION_POINT_RATE), 2, RoundingMode.DOWN);

            balance.setFreeQuota(0L);
            balance.setPlatformCurrency(balance.getPlatformCurrency().subtract(platformCurrencyUsed));
        } else if (paidPointsAvailable > 0) {
            Long paidPointsToUse = Math.min(pointsNeeded, paidPointsAvailable);
            paidPointsUsed = paidPointsToUse.intValue();
            platformCurrencyUsed = new BigDecimal(paidPointsToUse).divide(new BigDecimal(CREATION_POINT_RATE), 2, RoundingMode.DOWN);

            balance.setFreeQuota(0L);
            balance.setPlatformCurrency(balance.getPlatformCurrency().subtract(platformCurrencyUsed));
        } else {
            log.warn("Insufficient balance for user {}, needed {} points", request.getUserId(), pointsNeeded);
            return ChargeResult.fail(recordNo, "INSUFFICIENT_BALANCE", "额度不足，无法生成");
        }

        userBalanceMapper.updateById(balance);

        BillingRecord record = new BillingRecord();
        record.setRecordNo(recordNo);
        record.setUserId(request.getUserId());
        record.setChannelId(channel.getId());
        record.setContentType(request.getContentType());
        record.setContentId(request.getContentId());
        record.setInputTokens(request.getInputTokens());
        record.setOutputTokens(request.getOutputTokens());
        record.setTotalTokens(request.getInputTokens() + request.getOutputTokens());
        record.setChannelCost(channelCost);
        record.setProfitMargin(profitMargin);
        record.setUserPrice(userPrice);
        record.setCreationPoints(creationPoints);
        record.setFreePointsUsed(freePointsUsed);
        record.setPaidPointsUsed(paidPointsUsed);
        record.setPlatformCurrencyUsed(platformCurrencyUsed);
        record.setGenerationSuccess(1);
        recordMapper.insert(record);

        log.info("Billing recorded: recordNo={}, userId={}, points={}, free={}, paid={}",
                recordNo, request.getUserId(), creationPoints, freePointsUsed, paidPointsUsed);

        return ChargeResult.success(recordNo, creationPoints, freePointsUsed, paidPointsUsed, platformCurrencyUsed, userPrice);
    }

    @Transactional(rollbackFor = Exception.class)
    public void rollback(String recordNo, String reason) {
        BillingRecord original = recordMapper.selectOne(new LambdaQueryWrapper<BillingRecord>()
                .eq(BillingRecord::getRecordNo, recordNo));

        if (original == null) {
            log.warn("Rollback failed: record not found {}", recordNo);
            return;
        }

        if (original.getRollbackRecordNo() != null) {
            log.warn("Record {} already rolled back", recordNo);
            return;
        }

        String rollbackNo = generateRecordNo();

        UserBalance balance = userBalanceMapper.selectOne(new LambdaQueryWrapper<UserBalance>()
                .eq(UserBalance::getUserId, original.getUserId()));

        if (balance == null) {
            log.error("User balance not found for rollback, userId={}", original.getUserId());
            return;
        }

        balance.setFreeQuota(balance.getFreeQuota() + original.getFreePointsUsed());
        balance.setPlatformCurrency(balance.getPlatformCurrency().add(original.getPlatformCurrencyUsed()));
        userBalanceMapper.updateById(balance);

        original.setRollbackRecordNo(rollbackNo);
        recordMapper.updateById(original);

        BillingRecord rollbackRecord = new BillingRecord();
        rollbackRecord.setRecordNo(rollbackNo);
        rollbackRecord.setUserId(original.getUserId());
        rollbackRecord.setContentType("ROLLBACK");
        rollbackRecord.setFreePointsUsed(-original.getFreePointsUsed());
        rollbackRecord.setPaidPointsUsed(-original.getPaidPointsUsed());
        rollbackRecord.setPlatformCurrencyUsed(original.getPlatformCurrencyUsed().negate());
        rollbackRecord.setErrorMessage(reason);
        rollbackRecord.setGenerationSuccess(0);
        recordMapper.insert(rollbackRecord);

        log.info("Rollback completed: original={}, rollback={}, reason={}", recordNo, rollbackNo, reason);
    }

    public EstimateResult estimateCost(String contentType, Long userId, Integer inputTokens, Integer outputTokens) {
        UserBalance balance = getOrCreateUserBalance(userId);
        LocalDate today = LocalDate.now();

        if (balance.getFreeQuotaDate() == null || !balance.getFreeQuotaDate().equals(today)) {
            balance.setFreeQuota(0L);
            balance.setFreeQuotaDate(today);
        }

        BigDecimal profitMargin = getEffectiveProfitMargin(contentType, userId);
        BillingChannel channel = selectChannel(null);

        BigDecimal channelCost = calculateChannelCost(channel, inputTokens, outputTokens);
        BigDecimal userPrice = channelCost.multiply(BigDecimal.ONE.add(profitMargin));
        Integer estimatedPoints = userPrice.multiply(new BigDecimal("1000")).setScale(0, RoundingMode.CEILING).intValue();

        Long freeQuota = balance.getFreeQuota();
        Long pointsNeeded = estimatedPoints.longValue();
        Long paidPointsAvailable = balance.getPlatformCurrency().multiply(new BigDecimal(CREATION_POINT_RATE)).longValue();

        if (freeQuota >= pointsNeeded) {
            return EstimateResult.sufficient(estimatedPoints);
        } else if (balance.getEnableMixedPayment() == 1 && paidPointsAvailable > 0) {
            return EstimateResult.mixedPayment(estimatedPoints, freeQuota, BigDecimal.ZERO);
        } else if (freeQuota > 0 && paidPointsAvailable == 0) {
            return EstimateResult.freeInsufficient(estimatedPoints, freeQuota);
        } else if (paidPointsAvailable > 0) {
            return EstimateResult.paidInsufficient(estimatedPoints, balance.getPlatformCurrency());
        } else {
            return EstimateResult.insufficient();
        }
    }

    public UserBalanceDTO getUserBalance(Long userId) {
        UserBalance balance = getOrCreateUserBalance(userId);
        LocalDate today = LocalDate.now();

        if (balance.getFreeQuotaDate() == null || !balance.getFreeQuotaDate().equals(today)) {
            balance.setFreeQuota(0L);
            balance.setFreeQuotaDate(today);
            userBalanceMapper.updateById(balance);
        }

        Long todayFreeUsed = recordMapper.selectTodayFreePointsUsed(userId);
        if (todayFreeUsed == null) todayFreeUsed = 0L;

        UserBalanceDTO dto = new UserBalanceDTO();
        dto.setUserId(userId);
        dto.setFreeQuota(balance.getFreeQuota());
        dto.setFreeQuotaDate(balance.getFreeQuotaDate());
        dto.setPlatformCurrency(balance.getPlatformCurrency());
        dto.setPlatformCurrencyInPoints(balance.getPlatformCurrency().multiply(new BigDecimal(CREATION_POINT_RATE)).longValue());
        dto.setEnableMixedPayment(balance.getEnableMixedPayment() == 1);
        dto.setTodayFreeUsed(todayFreeUsed);

        return dto;
    }

    @Transactional(rollbackFor = Exception.class)
    public void setMixedPayment(Long userId, Boolean enabled) {
        UserBalance balance = getOrCreateUserBalance(userId);
        balance.setEnableMixedPayment(enabled ? 1 : 0);
        userBalanceMapper.updateById(balance);
    }

    @Transactional(rollbackFor = Exception.class)
    public void grantDailyFreeQuota(Long userId, Long quota, String userGroup) {
        UserBalance balance = getOrCreateUserBalance(userId);
        LocalDate today = LocalDate.now();

        balance.setFreeQuota(quota);
        balance.setFreeQuotaDate(today);
        userBalanceMapper.updateById(balance);

        log.info("Daily free quota granted: userId={}, quota={}, date={}", userId, quota, today);
    }

    public UserBalance getOrCreateUserBalance(Long userId) {
        UserBalance balance = userBalanceMapper.selectOne(new LambdaQueryWrapper<UserBalance>()
                .eq(UserBalance::getUserId, userId));

        if (balance == null) {
            balance = new UserBalance();
            balance.setUserId(userId);
            balance.setFreeQuota(0L);
            balance.setFreeQuotaDate(LocalDate.now());
            balance.setPlatformCurrency(BigDecimal.ZERO);
            balance.setEnableMixedPayment(1);
            balance.setTotalFreeUsed(0L);
            balance.setTotalPaidUsed(0L);
            balance.setTotalRecharged(0L);
            userBalanceMapper.insert(balance);
        }

        return balance;
    }

    private BillingChannel selectChannel(Long channelId) {
        if (channelId != null) {
            BillingChannel channel = channelMapper.selectById(channelId);
            if (channel != null && channel.getEnabled() == 1) {
                return channel;
            }
        }

        BillingChannel freeChannel = channelMapper.selectBestFreeChannel();
        if (freeChannel != null) {
            return freeChannel;
        }

        BillingChannel paidChannel = channelMapper.selectBestPaidChannel();
        if (paidChannel != null) {
            return paidChannel;
        }

        throw new RuntimeException("No available billing channel");
    }

    private BigDecimal calculateChannelCost(BillingChannel channel, ChargeRequest request) {
        return calculateChannelCost(channel, request.getInputTokens(), request.getOutputTokens());
    }

    private BigDecimal calculateChannelCost(BillingChannel channel, Integer inputTokens, Integer outputTokens) {
        BigDecimal cost = BigDecimal.ZERO;

        switch (channel.getChannelType()) {
            case "token":
                BigDecimal inputCost = channel.getCostPer1kInput()
                        .multiply(new BigDecimal(inputTokens))
                        .divide(new BigDecimal("1000"), 6, RoundingMode.HALF_UP);
                BigDecimal outputCost = channel.getCostPer1kOutput()
                        .multiply(new BigDecimal(outputTokens))
                        .divide(new BigDecimal("1000"), 6, RoundingMode.HALF_UP);
                cost = inputCost.add(outputCost);
                break;
            case "per_call":
                cost = channel.getCostPerCall();
                break;
            case "per_second":
                cost = channel.getCostPerSecond();
                break;
            case "hybrid":
                cost = channel.getBaseCost();
                break;
            default:
                cost = channel.getCostPerCall();
        }

        return cost;
    }

    private BigDecimal getEffectiveProfitMargin(String contentType, Long userId) {
        BillingMarginConfig contentConfig = marginConfigMapper.selectByContentType(contentType, LocalDateTime.now());
        if (contentConfig != null) {
            return contentConfig.getProfitMargin();
        }

        UserProfile profile = userProfileMapper.selectOne(new LambdaQueryWrapper<UserProfile>()
                .eq(UserProfile::getUserId, userId));
        String userGroup = "NORMAL";
        if (profile != null) {
            switch (profile.getMemberLevel()) {
                case 2:
                    userGroup = "VIP";
                    break;
                case 3:
                    userGroup = "SVIP";
                    break;
                default:
                    userGroup = "NORMAL";
            }
        }

        BillingMarginConfig userConfig = marginConfigMapper.selectByUserGroup(userGroup, LocalDateTime.now());
        if (userConfig != null) {
            return userConfig.getProfitMargin();
        }

        return DEFAULT_PROFIT_MARGIN;
    }

    private String generateRecordNo() {
        return "BL" + System.currentTimeMillis() + UUID.randomUUID().toString().substring(0, 8).toUpperCase();
    }
}
