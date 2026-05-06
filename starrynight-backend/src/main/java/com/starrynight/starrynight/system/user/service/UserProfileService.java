package com.starrynight.starrynight.system.user.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.util.ValidationUtil;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.auth.service.AuthService;
import com.starrynight.starrynight.system.user.dto.UserProfileDTO;
import com.starrynight.starrynight.system.user.dto.UserProfileUpdateDTO;
import com.starrynight.starrynight.system.user.entity.UserProfile;
import com.starrynight.starrynight.system.auth.realname.RealnameVerificationService;
import com.starrynight.starrynight.system.billing.epay.RealnameFeeEpayService;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import com.starrynight.starrynight.system.user.repository.UserProfileRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
@Service
public class UserProfileService {

    private final AuthUserRepository authUserRepository;
    private final UserProfileRepository userProfileRepository;

    @Autowired
    private RuntimeConfigService runtimeConfigService;

    @Autowired
    private AuthService authService;
    @Autowired
    private RealnameFeeEpayService realnameFeeEpayService;

    public UserProfileService(AuthUserRepository authUserRepository,
                              UserProfileRepository userProfileRepository) {
        this.authUserRepository = authUserRepository;
        this.userProfileRepository = userProfileRepository;
    }

    public UserProfileDTO getCurrentProfile() {
        Long userId = requireUserId();
        AuthUser user = authUserRepository.selectById(userId);
        if (user == null || user.getDeleted() != 0) {
            throw new ResourceNotFoundException("User not found");
        }
        UserProfile profile = userProfileRepository.selectOne(
                new LambdaQueryWrapper<UserProfile>()
                        .eq(UserProfile::getUserId, userId)
                        .eq(UserProfile::getDeleted, 0)
        );
        if (profile == null) {
            profile = new UserProfile();
            profile.setUserId(userId);
            profile.setNickname(user.getUsername());
            profile.setMemberLevel(0);
            profile.setPoints(0);
            userProfileRepository.insert(profile);
        }
        return toDTO(user, profile);
    }

    @Transactional
    public UserProfileDTO updateCurrentProfile(UserProfileUpdateDTO dto) {
        Long userId = requireUserId();
        AuthUser user = authUserRepository.selectById(userId);
        if (user == null || user.getDeleted() != 0) {
            throw new ResourceNotFoundException("User not found");
        }

        if (dto.getEmail() != null && !dto.getEmail().isBlank() && !ValidationUtil.isValidEmail(dto.getEmail())) {
            throw new BusinessException("Invalid email format");
        }
        if (dto.getPhone() != null && !dto.getPhone().isBlank() && !ValidationUtil.isValidPhone(dto.getPhone())) {
            throw new BusinessException("Invalid phone format");
        }

        if (dto.getEmail() != null) {
            user.setEmail(dto.getEmail().isBlank() ? null : dto.getEmail());
        }
        if (dto.getPhone() != null) {
            user.setPhone(dto.getPhone().isBlank() ? null : dto.getPhone());
        }
        if (dto.getAvatar() != null) {
            user.setAvatar(dto.getAvatar().isBlank() ? null : dto.getAvatar());
        }

        applyRealNameFieldsIfPresent(user, userId, dto);

        authUserRepository.updateById(user);

        UserProfile profile = userProfileRepository.selectOne(
                new LambdaQueryWrapper<UserProfile>()
                        .eq(UserProfile::getUserId, userId)
                        .eq(UserProfile::getDeleted, 0)
        );
        if (profile == null) {
            profile = new UserProfile();
            profile.setUserId(userId);
            profile.setMemberLevel(0);
            profile.setPoints(0);
        }
        if (dto.getNickname() != null) {
            profile.setNickname(dto.getNickname().isBlank() ? user.getUsername() : dto.getNickname());
        }
        if (profile.getId() == null) {
            userProfileRepository.insert(profile);
        } else {
            userProfileRepository.updateById(profile);
        }
        authService.evictUserInfoCache(userId);
        return toDTO(user, profile);
    }

    private UserProfileDTO toDTO(AuthUser user, UserProfile profile) {
        UserProfileDTO dto = new UserProfileDTO();
        dto.setUserId(user.getId());
        dto.setUsername(user.getUsername());
        dto.setEmail(user.getEmail());
        dto.setPhone(user.getPhone());
        dto.setAvatar(user.getAvatar());
        dto.setNickname(profile.getNickname());
        dto.setMemberLevel(profile.getMemberLevel());
        dto.setPoints(profile.getPoints());
        Integer rv = user.getRealNameVerified();
        dto.setRealNameVerified(rv);
        boolean rnEnabled = runtimeConfigService.getBoolean("auth.realname.enabled", false);
        dto.setRealNameGateEnabled(rnEnabled);
        dto.setHasRealNameOnFile(StringUtils.hasText(user.getRealName()) && StringUtils.hasText(user.getIdCardNo()));
        String pv = RealnameVerificationService.normalizedProvider(runtimeConfigService);
        dto.setRealNameVerifyProvider(pv);
        boolean faceLike = rnEnabled && ("alipay".equals(pv) || "ovooa".equals(pv));
        boolean verified = rv != null && rv == 1;
        dto.setRealNameVerifyPending(faceLike && Boolean.TRUE.equals(dto.getHasRealNameOnFile()) && !verified);

        boolean feeOn = runtimeConfigService.getBoolean("auth.realname.fee.enabled", false);
        dto.setRealnameFeeEnabled(feeOn);
        BigDecimal feeYuan = RealnameVerificationService.readConfiguredFeeYuanPublic(runtimeConfigService);
        dto.setRealnameFeeAmountYuan(feeYuan.compareTo(BigDecimal.ZERO) > 0 ? feeYuan : null);
        if (feeOn && feeYuan.compareTo(BigDecimal.ZERO) > 0) {
            dto.setRealnameFeeCashPaid(realnameFeeEpayService.hasValidCashRealnameFee(user, feeYuan));
        }
        return dto;
    }

    private void applyRealNameFieldsIfPresent(AuthUser user, Long userId, UserProfileUpdateDTO dto) {
        boolean keyPresent = dto.getRealName() != null || dto.getIdCardNo() != null;
        if (!keyPresent) {
            return;
        }
        String rn = dto.getRealName() != null ? dto.getRealName().trim() : "";
        String idc = dto.getIdCardNo() != null ? dto.getIdCardNo().trim() : "";
        boolean anyText = StringUtils.hasText(rn) || StringUtils.hasText(idc);
        int verified = user.getRealNameVerified() == null ? 0 : user.getRealNameVerified();
        if (verified == 1 && anyText) {
            throw new BusinessException("已通过实名核验，不可修改证件信息");
        }
        boolean rnGate = runtimeConfigService.getBoolean("auth.realname.enabled", false);
        if (!rnGate) {
            if (anyText) {
                throw new BusinessException("当前未开启实名，无需填写证件信息");
            }
            return;
        }
        if (!anyText) {
            return;
        }
        if (!StringUtils.hasText(rn) || rn.length() < 2 || rn.length() > 32) {
            throw new BusinessException("请填写真实姓名（2-32 个字符）");
        }
        if (!StringUtils.hasText(idc) || !ValidationUtil.isLikelyMainlandIdCard18(idc)) {
            throw new BusinessException("请填写有效的 18 位身份证号码");
        }
        user.setRealName(rn);
        user.setIdCardNo(idc);
        user.setRealNameVerified(0);
        user.setRealNameVerifyOuterNo(null);
        user.setRealnameFeePaidRecordNo(null);
    }

    private Long requireUserId() {
        Long userId = ThreadLocalUtil.getUserId();
        if (userId == null) {
            throw new BusinessException(401, "Unauthorized");
        }
        return userId;
    }
}

