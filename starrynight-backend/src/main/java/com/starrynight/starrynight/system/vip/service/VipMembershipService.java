package com.starrynight.starrynight.system.vip.service;

import com.alibaba.fastjson2.JSON;
import com.alibaba.fastjson2.JSONObject;
import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.system.user.entity.UserProfile;
import com.starrynight.starrynight.system.user.mapper.UserProfileMapper;
import com.starrynight.starrynight.system.vip.entity.MemberBenefitConfig;
import com.starrynight.starrynight.system.vip.entity.MemberSubscription;
import com.starrynight.starrynight.system.vip.entity.VipPackage;
import com.starrynight.starrynight.system.vip.mapper.MemberBenefitConfigMapper;
import com.starrynight.starrynight.system.vip.mapper.MemberSubscriptionMapper;
import com.starrynight.starrynight.system.vip.mapper.VipPackageMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.time.LocalDateTime;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@Slf4j
@Service
@RequiredArgsConstructor
public class VipMembershipService {

    private final VipPackageMapper packageMapper;
    private final MemberSubscriptionMapper subscriptionMapper;
    private final MemberBenefitConfigMapper benefitConfigMapper;
    private final UserProfileMapper userProfileMapper;

    public static final int LEVEL_FREE = 1;
    public static final int LEVEL_VIP = 2;
    public static final int LEVEL_SVIP = 3;

    public List<VipPackage> getActivePackages() {
        return packageMapper.selectList(new LambdaQueryWrapper<VipPackage>()
                .eq(VipPackage::getStatus, 1)
                .eq(VipPackage::getDeleted, 0)
                .orderByAsc(VipPackage::getSortOrder));
    }

    public List<VipPackage> getPackagesByLevel(Integer memberLevel) {
        return packageMapper.selectList(new LambdaQueryWrapper<VipPackage>()
                .eq(VipPackage::getMemberLevel, memberLevel)
                .eq(VipPackage::getStatus, 1)
                .eq(VipPackage::getDeleted, 0)
                .orderByAsc(VipPackage::getSortOrder));
    }

    public VipPackage getPackageById(Long id) {
        return packageMapper.selectById(id);
    }

    public MemberSubscription getActiveSubscription(Long userId) {
        return subscriptionMapper.findActiveSubscription(userId, LocalDateTime.now());
    }

    public Integer getMemberLevel(Long userId) {
        MemberSubscription subscription = getActiveSubscription(userId);
        if (subscription != null) {
            return subscription.getMemberLevel();
        }

        UserProfile profile = userProfileMapper.selectOne(new LambdaQueryWrapper<UserProfile>()
                .eq(UserProfile::getUserId, userId));
        return profile != null ? profile.getMemberLevel() : LEVEL_FREE;
    }

    public Long getDailyFreeQuota(Long userId) {
        Integer memberLevel = getMemberLevel(userId);
        MemberBenefitConfig config = benefitConfigMapper.findByLevelAndKey(memberLevel, "daily_free_quota");

        if (config != null && config.getBenefitValue() != null) {
            JSONObject valueObj = JSON.parseObject(config.getBenefitValue());
            return valueObj.getLong("value");
        }

        return 10000L;
    }

    public Map<String, Object> getMemberBenefits(Long userId) {
        Integer memberLevel = getMemberLevel(userId);
        List<MemberBenefitConfig> configs = benefitConfigMapper.findByMemberLevel(memberLevel);

        Map<String, Object> benefits = new HashMap<>();
        benefits.put("memberLevel", memberLevel);
        benefits.put("memberLevelName", getMemberLevelName(memberLevel));

        MemberSubscription subscription = getActiveSubscription(userId);
        if (subscription != null) {
            benefits.put("expireTime", subscription.getExpireTime());
            benefits.put("isActive", true);
        } else {
            benefits.put("isActive", false);
        }

        for (MemberBenefitConfig config : configs) {
            if (config.getBenefitValue() != null) {
                JSONObject valueObj = JSON.parseObject(config.getBenefitValue());
                benefits.put(config.getBenefitKey(), valueObj);
            } else {
                benefits.put(config.getBenefitKey(), true);
            }
        }

        return benefits;
    }

    public boolean hasBenefit(Long userId, String benefitKey) {
        Integer memberLevel = getMemberLevel(userId);
        MemberBenefitConfig config = benefitConfigMapper.findByLevelAndKey(memberLevel, benefitKey);
        return config != null && config.getEnabled() == 1;
    }

    public Integer getBenefitValue(Long userId, String benefitKey, Integer defaultValue) {
        Integer memberLevel = getMemberLevel(userId);
        MemberBenefitConfig config = benefitConfigMapper.findByLevelAndKey(memberLevel, benefitKey);

        if (config != null && config.getBenefitValue() != null) {
            JSONObject valueObj = JSON.parseObject(config.getBenefitValue());
            return valueObj.getInteger("value");
        }

        return defaultValue;
    }

    @Transactional(rollbackFor = Exception.class)
    public MemberSubscription activateMembership(Long userId, Long packageId) {
        VipPackage pkg = packageMapper.selectById(packageId);
        if (pkg == null || pkg.getStatus() != 1) {
            throw new RuntimeException("Package not available");
        }

        MemberSubscription existing = getActiveSubscription(userId);
        LocalDateTime startTime;
        LocalDateTime expireTime;

        if (existing != null && existing.getMemberLevel().equals(pkg.getMemberLevel())) {
            startTime = existing.getExpireTime();
            expireTime = startTime.plusDays(pkg.getDurationDays());
            existing.setExpireTime(expireTime);
            existing.setPackageId(packageId);
            subscriptionMapper.updateById(existing);
        } else {
            startTime = LocalDateTime.now();
            expireTime = startTime.plusDays(pkg.getDurationDays());

            MemberSubscription subscription = new MemberSubscription();
            subscription.setUserId(userId);
            subscription.setPackageId(packageId);
            subscription.setMemberLevel(pkg.getMemberLevel());
            subscription.setStartTime(startTime);
            subscription.setExpireTime(expireTime);
            subscription.setStatus("ACTIVE");
            subscription.setAutoRenew(0);
            subscriptionMapper.insert(subscription);
            existing = subscription;
        }

        UserProfile profile = userProfileMapper.selectOne(new LambdaQueryWrapper<UserProfile>()
                .eq(UserProfile::getUserId, userId));
        if (profile == null) {
            profile = new UserProfile();
            profile.setUserId(userId);
            profile.setMemberLevel(pkg.getMemberLevel());
            profile.setPoints(0);
            profile.setMemberExpireTime(expireTime);
            userProfileMapper.insert(profile);
        } else {
            profile.setMemberLevel(pkg.getMemberLevel());
            profile.setMemberExpireTime(expireTime);
            userProfileMapper.updateById(profile);
        }

        log.info("Membership activated: userId={}, packageId={}, level={}, expireTime={}",
                userId, packageId, pkg.getMemberLevel(), expireTime);

        return existing;
    }

    @Transactional(rollbackFor = Exception.class)
    public void expireMembership(Long userId) {
        MemberSubscription subscription = getActiveSubscription(userId);
        if (subscription != null) {
            subscription.setStatus("EXPIRED");
            subscriptionMapper.updateById(subscription);
        }

        UserProfile profile = userProfileMapper.selectOne(new LambdaQueryWrapper<UserProfile>()
                .eq(UserProfile::getUserId, userId));
        if (profile != null) {
            profile.setMemberLevel(LEVEL_FREE);
            userProfileMapper.updateById(profile);
        }

        log.info("Membership expired: userId={}", userId);
    }

    public boolean isMembershipActive(Long userId) {
        return getActiveSubscription(userId) != null;
    }

    public String getMemberLevelName(Integer memberLevel) {
        return switch (memberLevel) {
            case LEVEL_VIP -> "VIP";
            case LEVEL_SVIP -> "SVIP";
            default -> "普通用户";
        };
    }

    public List<MemberSubscription> getUserSubscriptionHistory(Long userId) {
        return subscriptionMapper.selectList(new LambdaQueryWrapper<MemberSubscription>()
                .eq(MemberSubscription::getUserId, userId)
                .orderByDesc(MemberSubscription::getCreateTime));
    }
}
