package com.starrynight.starrynight.system.growth.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.system.billing.entity.UserBalance;
import com.starrynight.starrynight.system.billing.mapper.UserBalanceMapper;
import com.starrynight.starrynight.system.growth.entity.*;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.growth.mapper.*;
import com.starrynight.starrynight.system.notification.service.NotificationService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.temporal.ChronoUnit;
import java.util.*;

@Slf4j
@Service
@RequiredArgsConstructor
public class GrowthService {

    private final CheckinRecordMapper checkinRecordMapper;
    private final CheckinConfigMapper checkinConfigMapper;
    private final PointsTransactionMapper pointsTransactionMapper;
    private final TaskConfigMapper taskConfigMapper;
    private final TaskCompletionMapper taskCompletionMapper;
    private final UserBalanceMapper userBalanceMapper;
    private final NotificationService notificationService;

    @Transactional(rollbackFor = Exception.class)
    public CheckinResult doCheckin(Long userId) {
        LocalDate today = LocalDate.now();

        CheckinRecord existing = checkinRecordMapper.findByUserIdAndDate(userId, today);
        if (existing != null) {
            return CheckinResult.alreadyCheckedIn(today);
        }

        LocalDate lastCheckin = checkinRecordMapper.findLastCheckinDate(userId);
        int continuousDays = calculateContinuousDays(lastCheckin, today);
        int maxContinuous = getConfigAsInt("max_continuous_days", 365);
        if (continuousDays > maxContinuous) {
            continuousDays = 1;
        }

        Long baseReward = getConfigAsLong("daily_checkin_reward", 50L);
        Long bonusReward = 0L;
        boolean isFirst = isFirstCheckin(userId);

        if (isFirst) {
            bonusReward = getConfigAsLong("first_checkin_reward", 200L);
        }

        int streakBonusThreshold = continuousDays;
        if (streakBonusThreshold >= 30) {
            bonusReward += getConfigAsLong("checkin_streak_30_bonus", 500L);
        } else if (streakBonusThreshold >= 15) {
            bonusReward += getConfigAsLong("checkin_streak_15_bonus", 300L);
        } else if (streakBonusThreshold >= 7) {
            bonusReward += getConfigAsLong("checkin_streak_7_bonus", 100L);
        }

        Long totalReward = baseReward + bonusReward;

        CheckinRecord record = new CheckinRecord();
        record.setUserId(userId);
        record.setCheckinDate(today);
        record.setCheckinTime(LocalDateTime.now());
        record.setRewardType("free_quota");
        record.setRewardAmount(totalReward);
        record.setContinuousDays(continuousDays);
        record.setIsFirstCheckin(isFirst ? 1 : 0);
        checkinRecordMapper.insert(record);

        addPointsToUser(userId, totalReward, "checkin", record.getId(), "每日签到奖励");

        String rewardType = getConfig("checkin_streak_bonus_type", "free_quota");
        if (isFirst) {
            notificationService.sendActivityNotification(userId, "🎉 首次签到成功",
                    "恭喜完成首次签到，获得" + bonusReward + "创作点奖励！");
        } else if (bonusReward > 0) {
            notificationService.sendActivityNotification(userId, "🔥 连续签到" + continuousDays + "天",
                    "连续签到奖励：额外获得" + bonusReward + "创作点！");
        }

        return CheckinResult.success(
                today,
                continuousDays,
                baseReward,
                bonusReward,
                totalReward,
                isFirst
        );
    }

    public CheckinStatus getCheckinStatus(Long userId) {
        LocalDate today = LocalDate.now();
        CheckinRecord todayRecord = checkinRecordMapper.findByUserIdAndDate(userId, today);

        CheckinStatus status = new CheckinStatus();
        status.setCheckedIn(todayRecord != null);

        if (todayRecord != null) {
            status.setTodayReward(todayRecord.getRewardAmount());
            status.setContinuousDays(todayRecord.getContinuousDays());
        } else {
            LocalDate lastCheckin = checkinRecordMapper.findLastCheckinDate(userId);
            int continuousDays = calculateContinuousDays(lastCheckin, today);
            status.setContinuousDays(continuousDays);

            Long baseReward = getConfigAsLong("daily_checkin_reward", 50L);
            status.setTodayReward(baseReward);
        }

        List<CheckinRecord> recentRecords = checkinRecordMapper.findRecentByUserId(userId, 30);
        status.setCheckedDates(new ArrayList<>());
        for (CheckinRecord record : recentRecords) {
            status.getCheckedDates().add(record.getCheckinDate());
        }

        Integer maxContinuous = checkinRecordMapper.findMaxContinuousDays(userId);
        status.setMaxContinuousDays(maxContinuous != null ? maxContinuous : 0);

        Integer totalCheckins = checkinRecordMapper.countTotalCheckins(userId);
        status.setTotalCheckins(totalCheckins != null ? totalCheckins : 0);

        return status;
    }

    @Transactional(rollbackFor = Exception.class)
    public void addPointsToUser(Long userId, Long points, String transactionType, Long sourceId, String description) {
        UserBalance balance = getOrCreateUserBalance(userId);
        Long newBalance = balance.getFreeQuota() + points;

        balance.setFreeQuota(newBalance);
        userBalanceMapper.updateById(balance);

        PointsTransaction transaction = new PointsTransaction();
        transaction.setUserId(userId);
        transaction.setTransactionType(transactionType);
        transaction.setPointsChange(points.intValue());
        transaction.setBalanceBefore(balance.getFreeQuota() - points);
        transaction.setBalanceAfter(newBalance);
        transaction.setSourceId(sourceId);
        transaction.setDescription(description);
        pointsTransactionMapper.insert(transaction);

        log.info("Points added: userId={}, points={}, newBalance={}, type={}", userId, points, newBalance, transactionType);
    }

    @Transactional(rollbackFor = Exception.class)
    public void grantRedeemFreeQuota(Long userId, long points, Long redemptionId, String description) {
        if (points <= 0) {
            throw new BusinessException("创作点奖励必须大于 0");
        }
        if (points > Integer.MAX_VALUE) {
            throw new BusinessException("单次创作点奖励过大");
        }
        addPointsToUser(userId, points, "redeem", redemptionId, description);
    }

    @Transactional(rollbackFor = Exception.class)
    public void grantRedeemPlatformCurrency(Long userId, BigDecimal amount, String description) {
        if (amount == null || amount.compareTo(BigDecimal.ZERO) <= 0) {
            throw new BusinessException("平台币奖励必须大于 0");
        }
        BigDecimal scaled = amount.setScale(2, RoundingMode.HALF_UP);
        UserBalance balance = getOrCreateUserBalance(userId);
        balance.setPlatformCurrency(balance.getPlatformCurrency().add(scaled));
        userBalanceMapper.updateById(balance);
        log.info("Redeem platform currency: userId={}, amount={}, {}", userId, scaled, description);
    }

    @Transactional(rollbackFor = Exception.class)
    public void recordTaskCompletion(Long userId, String taskCode) {
        TaskConfig taskConfig = taskConfigMapper.selectOne(new LambdaQueryWrapper<TaskConfig>()
                .eq(TaskConfig::getTaskCode, taskCode)
                .eq(TaskConfig::getEnabled, 1));

        if (taskConfig == null) {
            log.warn("Task config not found or disabled: {}", taskCode);
            return;
        }

        LocalDate today = LocalDate.now();
        Integer currentCount = taskCompletionMapper.sumCompletionCountByDate(userId, taskCode, today);
        currentCount = currentCount != null ? currentCount : 0;

        if (taskConfig.getMaxDailyTimes() != null && currentCount >= taskConfig.getMaxDailyTimes()) {
            log.info("Task {} daily limit reached for user {}", taskCode, userId);
            return;
        }

        TaskCompletion completion = taskCompletionMapper.findByUserIdAndTaskCodeAndDate(userId, taskCode, today);
        if (completion == null) {
            completion = new TaskCompletion();
            completion.setUserId(userId);
            completion.setTaskId(taskConfig.getId());
            completion.setTaskCode(taskCode);
            completion.setCompletionDate(today);
            completion.setCompletionCount(1);
            completion.setRewardClaimed(0);
            taskCompletionMapper.insert(completion);
        } else {
            completion.setCompletionCount(completion.getCompletionCount() + 1);
            taskCompletionMapper.updateById(completion);
        }

        if (completion.getRewardClaimed() == 0) {
            claimTaskReward(userId, taskConfig, completion.getId());
        }

        log.info("Task completion recorded: userId={}, taskCode={}, count={}", userId, taskCode, completion.getCompletionCount());
    }

    @Transactional(rollbackFor = Exception.class)
    public void claimTaskReward(Long userId, TaskConfig taskConfig, Long completionId) {
        if (taskConfig == null) return;

        addPointsToUser(userId, taskConfig.getRewardAmount(), "reward", completionId, taskConfig.getTaskName() + "任务奖励");

        TaskCompletion completion = taskCompletionMapper.selectById(completionId);
        if (completion != null) {
            completion.setRewardClaimed(1);
            completion.setRewardClaimTime(LocalDateTime.now());
            taskCompletionMapper.updateById(completion);
        }

        notificationService.sendActivityNotification(userId, "✅ 任务完成: " + taskConfig.getTaskName(),
                "完成" + taskConfig.getTaskName() + "，获得" + taskConfig.getRewardAmount() + "创作点奖励！");
    }

    public List<PointsTransaction> getPointsHistory(Long userId, int limit) {
        return pointsTransactionMapper.findRecentByUserId(userId, limit);
    }

    public PointsSummary getPointsSummary(Long userId) {
        PointsSummary summary = new PointsSummary();

        UserBalance balance = getOrCreateUserBalance(userId);
        summary.setCurrentBalance(balance.getFreeQuota());

        Long totalEarned = pointsTransactionMapper.sumPointsByType(userId, "checkin");
        Long totalCheckinReward = pointsTransactionMapper.sumPointsByType(userId, "reward");
        Long totalEarnedAll = (totalEarned != null ? totalEarned : 0) + (totalCheckinReward != null ? totalCheckinReward : 0);
        summary.setTotalEarned(totalEarnedAll);

        Long totalUsed = balance.getTotalFreeUsed();
        summary.setTotalUsed(totalUsed != null ? totalUsed : 0);

        Integer checkinCount = checkinRecordMapper.countTotalCheckins(userId);
        summary.setTotalCheckins(checkinCount != null ? checkinCount : 0);

        Integer maxContinuous = checkinRecordMapper.findMaxContinuousDays(userId);
        summary.setMaxContinuousDays(maxContinuous != null ? maxContinuous : 0);

        return summary;
    }

    public List<TaskStatus> getDailyTasks(Long userId) {
        LocalDate today = LocalDate.now();

        List<TaskConfig> dailyTasks = taskConfigMapper.selectList(
                new LambdaQueryWrapper<TaskConfig>()
                        .eq(TaskConfig::getTaskType, "daily")
                        .eq(TaskConfig::getEnabled, 1)
                        .orderByAsc(TaskConfig::getSortOrder)
        );

        List<TaskStatus> result = new ArrayList<>();
        for (TaskConfig task : dailyTasks) {
            TaskStatus status = new TaskStatus();
            status.setTaskCode(task.getTaskCode());
            status.setTaskName(task.getTaskName());
            status.setDescription(task.getDescription());
            status.setRewardAmount(task.getRewardAmount());

            Integer completedCount = taskCompletionMapper.sumCompletionCountByDate(userId, task.getTaskCode(), today);
            completedCount = completedCount != null ? completedCount : 0;

            status.setCompletedCount(completedCount);
            status.setMaxTimes(task.getMaxDailyTimes());
            status.setCompleted(completedCount >= (task.getMaxDailyTimes() != null ? task.getMaxDailyTimes() : 1));
            status.setRewardClaimed(completedCount > 0);

            result.add(status);
        }

        return result;
    }

    public List<TaskConfig> getAchievementTasks() {
        return taskConfigMapper.selectList(
                new LambdaQueryWrapper<TaskConfig>()
                        .eq(TaskConfig::getTaskType, "achievement")
                        .eq(TaskConfig::getEnabled, 1)
                        .orderByAsc(TaskConfig::getSortOrder)
        );
    }

    private UserBalance getOrCreateUserBalance(Long userId) {
        UserBalance balance = userBalanceMapper.selectOne(new LambdaQueryWrapper<UserBalance>()
                .eq(UserBalance::getUserId, userId));

        if (balance == null) {
            balance = new UserBalance();
            balance.setUserId(userId);
            balance.setFreeQuota(0L);
            balance.setFreeQuotaDate(LocalDate.now());
            balance.setPlatformCurrency(java.math.BigDecimal.ZERO);
            balance.setEnableMixedPayment(1);
            balance.setTotalFreeUsed(0L);
            balance.setTotalPaidUsed(0L);
            balance.setTotalRecharged(0L);
            userBalanceMapper.insert(balance);
        }

        return balance;
    }

    private boolean isFirstCheckin(Long userId) {
        Integer count = checkinRecordMapper.countTotalCheckins(userId);
        return count == null || count == 0;
    }

    private int calculateContinuousDays(LocalDate lastCheckin, LocalDate today) {
        if (lastCheckin == null) {
            return 1;
        }

        long daysBetween = ChronoUnit.DAYS.between(lastCheckin, today);
        if (daysBetween == 0) {
            return 1;
        } else if (daysBetween == 1) {
            Integer maxDays = checkinRecordMapper.findMaxContinuousDays(lastCheckin() != null ? lastCheckin() : null);
            return (maxDays != null ? maxDays : 0) + 1;
        } else {
            return 1;
        }
    }

    private Long lastCheckin() {
        return null;
    }

    private Long getConfigAsLong(String key, Long defaultValue) {
        String value = getConfig(key);
        if (value == null) return defaultValue;
        try {
            return Long.parseLong(value);
        } catch (NumberFormatException e) {
            return defaultValue;
        }
    }

    private Integer getConfigAsInt(String key, Integer defaultValue) {
        String value = getConfig(key);
        if (value == null) return defaultValue;
        try {
            return Integer.parseInt(value);
        } catch (NumberFormatException e) {
            return defaultValue;
        }
    }

    private String getConfig(String key) {
        return checkinConfigMapper.getConfigValue(key);
    }

    private String getConfig(String key, String defaultValue) {
        String v = getConfig(key);
        return v != null && !v.isBlank() ? v : defaultValue;
    }

    @lombok.Data
    public static class CheckinResult {
        private Boolean success;
        private LocalDate date;
        private Integer continuousDays;
        private Long baseReward;
        private Long bonusReward;
        private Long totalReward;
        private Boolean isFirst;
        private String message;

        public static CheckinResult success(LocalDate date, int continuousDays, long baseReward, long bonusReward, long totalReward, boolean isFirst) {
            CheckinResult result = new CheckinResult();
            result.setSuccess(true);
            result.setDate(date);
            result.setContinuousDays(continuousDays);
            result.setBaseReward(baseReward);
            result.setBonusReward(bonusReward);
            result.setTotalReward(totalReward);
            result.setIsFirst(isFirst);
            result.setMessage("签到成功！获得" + totalReward + "创作点");
            return result;
        }

        public static CheckinResult alreadyCheckedIn(LocalDate date) {
            CheckinResult result = new CheckinResult();
            result.setSuccess(false);
            result.setDate(date);
            result.setMessage("今日已签到");
            return result;
        }
    }

    @lombok.Data
    public static class CheckinStatus {
        private Boolean checkedIn;
        private Long todayReward;
        private Integer continuousDays;
        private List<LocalDate> checkedDates;
        private Integer maxContinuousDays;
        private Integer totalCheckins;
    }

    @lombok.Data
    public static class PointsSummary {
        private Long currentBalance;
        private Long totalEarned;
        private Long totalUsed;
        private Integer totalCheckins;
        private Integer maxContinuousDays;
    }

    @lombok.Data
    public static class TaskStatus {
        private String taskCode;
        private String taskName;
        private String description;
        private Long rewardAmount;
        private Integer completedCount;
        private Integer maxTimes;
        private Boolean completed;
        private Boolean rewardClaimed;
    }
}
