package com.starrynight.starrynight.system.user.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ValidationUtil;
import com.starrynight.starrynight.system.auth.entity.AuthOauthLink;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.entity.OpsAccount;
import com.starrynight.starrynight.system.auth.repository.AuthOauthLinkRepository;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.auth.repository.OpsAccountRepository;
import com.starrynight.starrynight.system.auth.service.AuthService;
import com.starrynight.starrynight.system.billing.PayMethodCodes;
import com.starrynight.starrynight.system.billing.entity.RechargeRecord;
import com.starrynight.starrynight.system.billing.entity.UserBalance;
import com.starrynight.starrynight.system.billing.mapper.RechargeRecordMapper;
import com.starrynight.starrynight.system.billing.service.BillingService;
import com.starrynight.starrynight.system.user.dto.AdminUserBalanceUpdateDTO;
import com.starrynight.starrynight.system.user.dto.AdminUserCreateDTO;
import com.starrynight.starrynight.system.user.dto.AdminUserDetailDTO;
import com.starrynight.starrynight.system.user.dto.AdminUserDTO;
import com.starrynight.starrynight.system.user.dto.AdminUserMembershipUpdateDTO;
import com.starrynight.starrynight.system.user.entity.UserProfile;
import com.starrynight.starrynight.system.user.repository.UserProfileRepository;
import com.starrynight.starrynight.system.billing.mapper.UserBalanceMapper;
import com.starrynight.starrynight.system.novel.entity.Novel;
import com.starrynight.starrynight.system.novel.mapper.NovelMapper;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Comparator;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.function.Function;
import java.util.stream.Collectors;

@Service
public class AdminUserService {

    private final BCryptPasswordEncoder passwordEncoder = new BCryptPasswordEncoder();

    @Autowired
    private AuthUserRepository authUserRepository;
    @Autowired
    private UserProfileRepository userProfileRepository;
    @Autowired
    private OpsAccountRepository opsAccountRepository;
    @Autowired
    private UserBalanceMapper userBalanceMapper;
    @Autowired
    private BillingService billingService;
    @Autowired
    private AuthOauthLinkRepository authOauthLinkRepository;
    @Autowired
    private NovelMapper novelMapper;
    @Autowired
    private RechargeRecordMapper rechargeRecordMapper;
    @Autowired
    private AuthService authService;

    public Page<AdminUserDTO> list(
            String keyword,
            Integer status,
            Integer memberLevel,
            Integer memberLevelMin,
            int page,
            int size) {
        LambdaQueryWrapper<AuthUser> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(AuthUser::getDeleted, 0);
        // 运营端账号隔离：运营账号不进入「用户管理」列表
        wrapper.eq(AuthUser::getIsAdmin, 0);
        if (status != null) {
            wrapper.eq(AuthUser::getStatus, status);
        }
        if (memberLevel != null) {
            wrapper.apply(
                    "EXISTS (SELECT 1 FROM user_profile p WHERE p.user_id = auth_user.id AND p.deleted = 0 AND p.member_level = {0})",
                    memberLevel);
        } else if (memberLevelMin != null) {
            wrapper.apply(
                    "EXISTS (SELECT 1 FROM user_profile p WHERE p.user_id = auth_user.id AND p.deleted = 0 AND p.member_level >= {0})",
                    memberLevelMin);
        }
        if (keyword != null && !keyword.isBlank()) {
            wrapper.and(w -> w
                    .like(AuthUser::getUsername, keyword)
                    .or()
                    .like(AuthUser::getEmail, keyword)
                    .or()
                    .like(AuthUser::getPhone, keyword));
        }
        wrapper.orderByDesc(AuthUser::getCreateTime);

        Page<AuthUser> userPage = authUserRepository.selectPage(new Page<>(page, size), wrapper);
        List<AuthUser> records = userPage.getRecords();
        Set<Long> userIds = records.stream().map(AuthUser::getId).collect(Collectors.toSet());

        Map<Long, UserProfile> profileMap;
        if (userIds.isEmpty()) {
            profileMap = Collections.emptyMap();
        } else {
            profileMap = userProfileRepository.selectList(
                    new LambdaQueryWrapper<UserProfile>()
                            .in(UserProfile::getUserId, userIds)
                            .eq(UserProfile::getDeleted, 0)
            ).stream().collect(Collectors.toMap(UserProfile::getUserId, Function.identity(), (a, b) -> a));
        }

        Map<Long, UserBalance> balanceMap;
        if (userIds.isEmpty()) {
            balanceMap = Collections.emptyMap();
        } else {
            balanceMap = userBalanceMapper.selectList(
                    new LambdaQueryWrapper<UserBalance>().in(UserBalance::getUserId, userIds)
            ).stream().collect(Collectors.toMap(UserBalance::getUserId, Function.identity(), (a, b) -> a));
        }

        List<AdminUserDTO> result = records.stream().map(user -> {
            AdminUserDTO dto = new AdminUserDTO();
            dto.setId(user.getId());
            dto.setUsername(user.getUsername());
            dto.setEmail(user.getEmail());
            dto.setPhone(user.getPhone());
            dto.setStatus(user.getStatus());
            dto.setIsAdmin(user.getIsAdmin());
            dto.setCreateTime(user.getCreateTime());
            dto.setRegisterIp(user.getRegisterIp());
            dto.setLastLoginTime(user.getLastLoginTime());
            dto.setLastLoginIp(user.getLastLoginIp());
            UserProfile profile = profileMap.get(user.getId());
            dto.setMemberLevel(profile != null ? profile.getMemberLevel() : 1);
            dto.setMemberExpireTime(profile != null ? profile.getMemberExpireTime() : null);
            dto.setPoints(profile != null ? profile.getPoints() : 0);
            UserBalance bal = balanceMap.get(user.getId());
            dto.setFreeQuota(bal != null && bal.getFreeQuota() != null ? bal.getFreeQuota() : 0L);
            dto.setPlatformCurrency(
                    bal != null && bal.getPlatformCurrency() != null ? bal.getPlatformCurrency() : BigDecimal.ZERO
            );
            return dto;
        }).collect(Collectors.toList());

        Page<AdminUserDTO> response = new Page<>(userPage.getCurrent(), userPage.getSize(), userPage.getTotal());
        response.setRecords(result);
        return response;
    }

    public AdminUserDetailDTO getDetail(Long userId) {
        AuthUser user = requireEndUser(userId);
        UserProfile profile = userProfileRepository.selectOne(
                new LambdaQueryWrapper<UserProfile>()
                        .eq(UserProfile::getUserId, userId)
                        .eq(UserProfile::getDeleted, 0)
        );
        UserBalance bal = userBalanceMapper.selectOne(
                new LambdaQueryWrapper<UserBalance>().eq(UserBalance::getUserId, userId)
        );

        AdminUserDetailDTO dto = new AdminUserDetailDTO();
        dto.setId(user.getId());
        dto.setUsername(user.getUsername());
        dto.setEmail(user.getEmail());
        dto.setPhone(user.getPhone());
        dto.setAvatar(user.getAvatar());
        dto.setStatus(user.getStatus());
        dto.setIsAdmin(user.getIsAdmin());
        dto.setCreateTime(user.getCreateTime());
        dto.setRegisterIp(user.getRegisterIp());
        dto.setLastLoginTime(user.getLastLoginTime());
        dto.setLastLoginIp(user.getLastLoginIp());

        boolean hasRn = StringUtils.hasText(user.getRealName());
        boolean hasId = StringUtils.hasText(user.getIdCardNo());
        dto.setHasIdentityOnFile(hasRn && hasId);
        dto.setRealNameMasked(maskRealName(user.getRealName()));
        dto.setIdCardMasked(maskIdCard(user.getIdCardNo()));
        dto.setRealNameVerified(user.getRealNameVerified());
        dto.setRealNameVerifyOuterNo(user.getRealNameVerifyOuterNo());
        dto.setRealnameFeePaidRecordNo(user.getRealnameFeePaidRecordNo());
        fillRealnameFeePaySnapshot(dto, user.getRealnameFeePaidRecordNo());

        List<AuthOauthLink> links = authOauthLinkRepository.selectList(
                new LambdaQueryWrapper<AuthOauthLink>().eq(AuthOauthLink::getUserId, userId));
        List<String> providers = new ArrayList<>();
        for (AuthOauthLink link : links) {
            if (link.getProvider() != null && !link.getProvider().isBlank()) {
                providers.add(link.getProvider().trim());
            }
        }
        providers.sort(Comparator.naturalOrder());
        dto.setOauthProviders(providers);

        Long novelCt = novelMapper.selectCount(
                new LambdaQueryWrapper<Novel>()
                        .eq(Novel::getUserId, userId)
                        .eq(Novel::getIsDeleted, 0));
        dto.setNovelCount(novelCt != null ? novelCt.intValue() : 0);

        if (profile != null) {
            dto.setNickname(profile.getNickname());
            dto.setMemberLevel(profile.getMemberLevel());
            dto.setMemberExpireTime(profile.getMemberExpireTime());
            dto.setPoints(profile.getPoints());
            dto.setTotalWordCount(profile.getTotalWordCount() != null ? profile.getTotalWordCount() : 0L);
        } else {
            dto.setMemberLevel(1);
            dto.setPoints(0);
            dto.setTotalWordCount(0L);
        }
        if (bal != null) {
            dto.setFreeQuota(bal.getFreeQuota() != null ? bal.getFreeQuota() : 0L);
            dto.setPlatformCurrency(bal.getPlatformCurrency() != null ? bal.getPlatformCurrency() : BigDecimal.ZERO);
            dto.setFreeQuotaDate(bal.getFreeQuotaDate());
            dto.setEnableMixedPayment(bal.getEnableMixedPayment() != null && bal.getEnableMixedPayment() == 1);
            dto.setTotalFreeUsed(bal.getTotalFreeUsed() != null ? bal.getTotalFreeUsed() : 0L);
            dto.setTotalPaidUsed(bal.getTotalPaidUsed() != null ? bal.getTotalPaidUsed() : 0L);
            dto.setTotalRecharged(bal.getTotalRecharged() != null ? bal.getTotalRecharged() : 0L);
        } else {
            dto.setFreeQuota(0L);
            dto.setPlatformCurrency(BigDecimal.ZERO);
            dto.setEnableMixedPayment(true);
            dto.setTotalFreeUsed(0L);
            dto.setTotalPaidUsed(0L);
            dto.setTotalRecharged(0L);
        }
        return dto;
    }

    @Transactional
    public void updateRealnameVerified(Long userId, Integer verified) {
        AuthUser user = requireEndUser(userId);
        if (verified == null || (verified != 0 && verified != 1)) {
            throw new BusinessException("核验状态须为 0（未通过）或 1（已通过）");
        }
        if (verified == 1) {
            if (!StringUtils.hasText(user.getRealName()) || !StringUtils.hasText(user.getIdCardNo())) {
                throw new BusinessException("用户未登记真实姓名与证件号，无法标为核验通过");
            }
            user.setRealNameVerified(1);
            if (!StringUtils.hasText(user.getRealNameVerifyOuterNo())) {
                user.setRealNameVerifyOuterNo("ADMIN");
            }
            user.setRealnameFeePaidRecordNo(null);
        } else {
            user.setRealNameVerified(0);
            user.setRealNameVerifyOuterNo(null);
        }
        authUserRepository.updateById(user);
        authService.evictUserInfoCache(userId);
    }

    private void fillRealnameFeePaySnapshot(AdminUserDetailDTO dto, String recordNo) {
        if (!StringUtils.hasText(recordNo)) {
            return;
        }
        RechargeRecord r = rechargeRecordMapper.selectOne(
                new LambdaQueryWrapper<RechargeRecord>().eq(RechargeRecord::getRecordNo, recordNo.trim()));
        if (r == null || !PayMethodCodes.REALNAME_FEE.equalsIgnoreCase(r.getPayMethod())) {
            return;
        }
        dto.setRealnameFeePayStatus(r.getPayStatus());
        dto.setRealnameFeePayAmount(r.getAmount());
        dto.setRealnameFeePayTime(r.getPayTime());
    }

    private static String maskRealName(String raw) {
        if (!StringUtils.hasText(raw)) {
            return null;
        }
        String t = raw.trim();
        int n = t.length();
        if (n <= 1) {
            return "*";
        }
        if (n == 2) {
            return t.charAt(0) + "*";
        }
        return t.charAt(0) + "*".repeat(n - 2) + t.charAt(n - 1);
    }

    private static String maskIdCard(String raw) {
        if (!StringUtils.hasText(raw)) {
            return null;
        }
        String t = raw.trim();
        if (t.length() < 8) {
            return "****";
        }
        return t.substring(0, 4) + "**********" + t.substring(t.length() - 4);
    }

    @Transactional
    public void updateBalance(Long userId, AdminUserBalanceUpdateDTO body) {
        requireEndUser(userId);
        if (body.getFreeQuota() == null && body.getPlatformCurrency() == null) {
            throw new BusinessException("请至少填写创作点或星夜币其中一项");
        }
        UserBalance balance = billingService.getOrCreateUserBalance(userId);
        if (body.getFreeQuota() != null) {
            if (body.getFreeQuota() < 0) {
                throw new BusinessException("创作点不能为负数");
            }
            balance.setFreeQuota(body.getFreeQuota());
            balance.setFreeQuotaDate(LocalDate.now());
        }
        if (body.getPlatformCurrency() != null) {
            if (body.getPlatformCurrency().compareTo(BigDecimal.ZERO) < 0) {
                throw new BusinessException("星夜币不能为负数");
            }
            if (body.getPlatformCurrency().compareTo(new BigDecimal("99999999.99")) > 0) {
                throw new BusinessException("星夜币数额过大");
            }
            balance.setPlatformCurrency(body.getPlatformCurrency().setScale(2, java.math.RoundingMode.HALF_UP));
        }
        userBalanceMapper.updateById(balance);
    }

    @Transactional
    public void updateMembership(Long userId, AdminUserMembershipUpdateDTO body) {
        requireEndUser(userId);
        int level = body.getMemberLevel();
        if (level < 1 || level > 3) {
            throw new BusinessException("会员等级须为 1（普通）、2（VIP）或 3（高级VIP）");
        }
        UserProfile profile = userProfileRepository.selectOne(
                new LambdaQueryWrapper<UserProfile>()
                        .eq(UserProfile::getUserId, userId)
                        .eq(UserProfile::getDeleted, 0)
        );
        if (profile == null) {
            profile = new UserProfile();
            profile.setUserId(userId);
            profile.setMemberLevel(level);
            profile.setMemberExpireTime(body.getMemberExpireTime());
            profile.setPoints(0);
            userProfileRepository.insert(profile);
        } else {
            profile.setMemberLevel(level);
            profile.setMemberExpireTime(body.getMemberExpireTime());
            userProfileRepository.updateById(profile);
        }
    }

    private AuthUser requireEndUser(Long userId) {
        if (userId == null) {
            throw new BusinessException("用户ID无效");
        }
        AuthUser user = authUserRepository.selectById(userId);
        if (user == null || user.getDeleted() != 0) {
            throw new ResourceNotFoundException("User not found");
        }
        if (user.getIsAdmin() != null && user.getIsAdmin() == 1) {
            throw new BusinessException("运营账号不在用户管理范围内");
        }
        return user;
    }

    @Transactional
    public AdminUserDTO create(AdminUserCreateDTO dto, String registerIp) {
        if (!ValidationUtil.isValidUsername(dto.getUsername().trim())) {
            throw new BusinessException("用户名须为 4-20 位字母、数字或下划线");
        }
        if (!ValidationUtil.isValidPassword(dto.getPassword())) {
            throw new BusinessException("密码须为 6-32 个字符");
        }
        String username = dto.getUsername().trim();

        AuthUser existUser = authUserRepository.selectOne(
                new LambdaQueryWrapper<AuthUser>()
                        .eq(AuthUser::getUsername, username)
                        .eq(AuthUser::getDeleted, 0)
        );
        if (existUser != null) {
            throw new BusinessException("用户名已存在");
        }
        OpsAccount existOps = opsAccountRepository.selectOne(
                new LambdaQueryWrapper<OpsAccount>()
                        .eq(OpsAccount::getUsername, username)
                        .eq(OpsAccount::getDeleted, 0)
        );
        if (existOps != null) {
            throw new BusinessException("用户名与运营账号冲突");
        }

        String email = dto.getEmail() != null && !dto.getEmail().isBlank() ? dto.getEmail().trim() : null;
        if (email != null) {
            if (!ValidationUtil.isValidEmail(email)) {
                throw new BusinessException("邮箱格式不正确");
            }
            Long emailDup = authUserRepository.selectCount(
                    new LambdaQueryWrapper<AuthUser>()
                            .eq(AuthUser::getDeleted, 0)
                            .apply("LOWER(email) = LOWER({0})", email)
            );
            if (emailDup != null && emailDup > 0) {
                throw new BusinessException("邮箱已被使用");
            }
            OpsAccount emailOps = opsAccountRepository.selectOne(
                    new LambdaQueryWrapper<OpsAccount>()
                            .eq(OpsAccount::getDeleted, 0)
                            .apply("LOWER(email) = LOWER({0})", email)
            );
            if (emailOps != null) {
                throw new BusinessException("邮箱已被运营账号使用");
            }
        }

        String phone = dto.getPhone() != null && !dto.getPhone().isBlank() ? dto.getPhone().trim() : null;
        if (phone != null) {
            if (!ValidationUtil.isValidPhone(phone)) {
                throw new BusinessException("手机号须为 11 位中国大陆号码");
            }
            Long phoneDup = authUserRepository.selectCount(
                    new LambdaQueryWrapper<AuthUser>()
                            .eq(AuthUser::getDeleted, 0)
                            .eq(AuthUser::getPhone, phone)
            );
            if (phoneDup != null && phoneDup > 0) {
                throw new BusinessException("手机号已被使用");
            }
        }

        AuthUser user = new AuthUser();
        user.setUsername(username);
        user.setPassword(passwordEncoder.encode(dto.getPassword()));
        user.setEmail(email);
        user.setPhone(phone);
        user.setStatus(1);
        user.setIsAdmin(0);
        if (registerIp != null && !registerIp.isBlank()) {
            user.setRegisterIp(registerIp.trim());
        }
        authUserRepository.insert(user);

        AdminUserDTO out = new AdminUserDTO();
        out.setId(user.getId());
        out.setUsername(user.getUsername());
        out.setEmail(user.getEmail());
        out.setPhone(user.getPhone());
        out.setStatus(user.getStatus());
        out.setIsAdmin(user.getIsAdmin());
        out.setCreateTime(user.getCreateTime());
        out.setRegisterIp(user.getRegisterIp());
        out.setLastLoginTime(user.getLastLoginTime());
        out.setLastLoginIp(user.getLastLoginIp());
        out.setMemberLevel(1);
        out.setPoints(0);
        return out;
    }

    @Transactional
    public void updateStatus(Long userId, Integer status) {
        if (status == null || (status != 0 && status != 1)) {
            throw new BusinessException("Invalid status");
        }
        AuthUser user = authUserRepository.selectById(userId);
        if (user == null || user.getDeleted() != 0) {
            throw new ResourceNotFoundException("User not found");
        }
        if (user.getIsAdmin() != null && user.getIsAdmin() == 1) {
            throw new BusinessException("Ops account is isolated from user management");
        }
        if (user.getIsAdmin() != null && user.getIsAdmin() == 1 && status == 0) {
            throw new BusinessException("Admin user cannot be disabled");
        }
        user.setStatus(status);
        authUserRepository.updateById(user);
    }

    @Transactional
    public void updatePoints(Long userId, Integer points) {
        if (points == null || points < 0) {
            throw new BusinessException("Points must be greater than or equal to 0");
        }
        AuthUser user = authUserRepository.selectById(userId);
        if (user == null || user.getDeleted() != 0) {
            throw new ResourceNotFoundException("User not found");
        }
        if (user.getIsAdmin() != null && user.getIsAdmin() == 1) {
            throw new BusinessException("Ops account is isolated from user management");
        }
        UserProfile profile = userProfileRepository.selectOne(
                new LambdaQueryWrapper<UserProfile>()
                        .eq(UserProfile::getUserId, userId)
                        .eq(UserProfile::getDeleted, 0)
        );
        if (profile == null) {
            profile = new UserProfile();
            profile.setUserId(userId);
            profile.setPoints(points);
            profile.setMemberLevel(1);
            userProfileRepository.insert(profile);
        } else {
            profile.setPoints(points);
            userProfileRepository.updateById(profile);
        }
    }
}
