package com.starrynight.starrynight.system.auth.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.conditions.update.LambdaUpdateWrapper;
import com.starrynight.starrynight.system.auth.AuthPortal;
import com.starrynight.starrynight.system.auth.AuthSubjectType;
import com.starrynight.starrynight.system.auth.dto.LoginDTO;
import com.starrynight.starrynight.system.auth.dto.ResetPasswordDTO;
import com.starrynight.starrynight.system.auth.dto.RegisterDTO;
import com.starrynight.starrynight.system.auth.dto.SendCodeDTO;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.entity.OpsAccount;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.auth.repository.OpsAccountRepository;
import com.starrynight.starrynight.system.auth.vo.AuthVO;
import com.starrynight.starrynight.system.auth.vo.RegisterOptionsVO;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.util.ValidationUtil;
import com.starrynight.starrynight.system.notification.MailTemplateKind;
import com.starrynight.starrynight.system.notification.service.MailSendService;
import com.starrynight.starrynight.system.notification.service.MailTemplateDispatchService;
import com.starrynight.starrynight.system.notification.service.SmsSendService;
import com.starrynight.starrynight.system.rbac.entity.AdminRole;
import com.starrynight.starrynight.system.rbac.repository.AdminRoleRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.cache.Cache;
import org.springframework.cache.CacheManager;
import org.springframework.security.authentication.AnonymousAuthenticationToken;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.cache.annotation.Cacheable;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;

import java.security.SecureRandom;
import java.time.LocalDateTime;
import java.util.Locale;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
@Service
public class AuthService {

    private static final Logger log = LoggerFactory.getLogger(AuthService.class);
    private static final SecureRandom RANDOM = new SecureRandom();
    private static final int CODE_EXPIRE_MINUTES = 5;
    private final Map<String, CodeEntry> resetCodeStore = new ConcurrentHashMap<>();

    @Autowired
    private AuthUserRepository authUserRepository;
    @Autowired
    private OpsAccountRepository opsAccountRepository;
    @Autowired
    private AdminRoleRepository adminRoleRepository;

    @Autowired
    private JwtService jwtService;
    @Autowired(required = false)
    private CacheManager cacheManager;
    @Autowired
    private RuntimeConfigService runtimeConfigService;
    @Autowired
    private MailSendService mailSendService;
    @Autowired
    private MailTemplateDispatchService mailTemplateDispatchService;
    @Autowired
    private SmsSendService smsSendService;

    private final BCryptPasswordEncoder passwordEncoder = new BCryptPasswordEncoder();

    public RegisterOptionsVO getRegisterOptions() {
        return new RegisterOptionsVO(
                runtimeConfigService.getBoolean("auth.register.email.enabled", true),
                runtimeConfigService.getBoolean("auth.register.phone.enabled", true),
                runtimeConfigService.getBoolean("auth.realname.enabled", false),
                resolveRealNameVerifyProvider());
    }

    private String resolveRealNameVerifyProvider() {
        if (!runtimeConfigService.getBoolean("auth.realname.enabled", false)) {
            return "basic";
        }
        String p = runtimeConfigService.getString("auth.realname.verify_provider", "alipay").trim().toLowerCase(Locale.ROOT);
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

    public void evictUserInfoCache(Long principalId) {
        if (cacheManager == null || principalId == null) {
            return;
        }
        Cache cache = cacheManager.getCache("userInfo");
        if (cache != null) {
            cache.evict(principalId);
        }
    }

    public AuthVO.UserInfo opsUserInfoFromAccount(OpsAccount account) {
        if (account == null) {
            throw new BusinessException(401, "Unauthorized");
        }
        ensureOpsRoleUsable(account.getRoleId());
        return buildOpsUserInfo(account);
    }

    public AuthVO login(LoginDTO dto, String clientIp) {
        String username = dto.getUsername() != null ? dto.getUsername().trim() : null;
        AuthPortal portal = AuthPortal.fromLoginRequest(dto.getPortal());
        if (portal == AuthPortal.OPS) {
            OpsAccount account = opsAccountRepository.selectOne(
                    new LambdaQueryWrapper<OpsAccount>()
                            .eq(OpsAccount::getUsername, username)
                            .eq(OpsAccount::getDeleted, 0)
            );
            if (account == null) {
                account = opsAccountRepository.selectOne(
                        new LambdaQueryWrapper<OpsAccount>()
                                .eq(OpsAccount::getDeleted, 0)
                                .apply("LOWER(email) = LOWER({0})", username)
                );
            }
            if (account == null || !passwordEncoder.matches(dto.getPassword(), account.getPassword())) {
                throw new BusinessException("账号或密码错误");
            }
            if (account.getStatus() == null || account.getStatus() != 1) {
                throw new BusinessException("运营账号已禁用");
            }
            ensureOpsRoleUsable(account.getRoleId());
            return buildOpsAuthVO(account);
        }
        AuthUser user = authUserRepository.selectOne(
                new LambdaQueryWrapper<AuthUser>()
                        .eq(AuthUser::getUsername, username)
                        .eq(AuthUser::getDeleted, 0)
                        .eq(AuthUser::getIsAdmin, 0)
        );
        if (user == null || !passwordEncoder.matches(dto.getPassword(), user.getPassword())) {
            throw new BusinessException("账号或密码错误");
        }
        if (user.getStatus() != 1) {
            throw new BusinessException("User account is disabled");
        }
        AuthVO vo = buildUserAuthVO(user);
        if (clientIp != null && !clientIp.isBlank()) {
            authUserRepository.update(
                    null,
                    new LambdaUpdateWrapper<AuthUser>()
                            .eq(AuthUser::getId, user.getId())
                            .set(AuthUser::getLastLoginTime, LocalDateTime.now())
                            .set(AuthUser::getLastLoginIp, clientIp.trim()));
        }
        return vo;
    }

    /**
     * 第三方 OAuth 回调成功后颁发与用户密码登录一致的 JWT；可选写入最近登录 IP。
     */
    /** OAuth 换票后按主键加载用户；门户校验在 issuePortalSession 中完成。 */
    public AuthUser getAuthUserByIdForOAuth(Long id) {
        if (id == null) {
            return null;
        }
        return authUserRepository.selectById(id);
    }

    public AuthVO issuePortalSession(AuthUser user, String clientIp) {
        if (user == null || user.getDeleted() != 0 || user.getStatus() != 1) {
            throw new BusinessException(401, "Unauthorized");
        }
        if (user.getIsAdmin() != null && user.getIsAdmin() == 1) {
            throw new BusinessException("不支持通过第三方登录此账号");
        }
        if (clientIp != null && !clientIp.isBlank()) {
            authUserRepository.update(
                    null,
                    new LambdaUpdateWrapper<AuthUser>()
                            .eq(AuthUser::getId, user.getId())
                            .set(AuthUser::getLastLoginTime, LocalDateTime.now())
                            .set(AuthUser::getLastLoginIp, clientIp.trim()));
        }
        return buildUserAuthVO(user);
    }

    public AuthVO register(RegisterDTO dto, String clientIp) {
        if (!ValidationUtil.isValidUsername(dto.getUsername())) {
            throw new BusinessException("Invalid username format");
        }

        if (!ValidationUtil.isValidPassword(dto.getPassword())) {
            throw new BusinessException("Password must be 6-32 characters");
        }
        String emailRaw = dto.getEmail() != null ? dto.getEmail().trim() : "";
        String phoneRaw = dto.getPhone() != null ? dto.getPhone().trim() : "";
        if (StringUtils.hasText(emailRaw) && !runtimeConfigService.getBoolean("auth.register.email.enabled", true)) {
            throw new BusinessException("当前已关闭邮箱注册，请勿填写邮箱");
        }
        if (StringUtils.hasText(phoneRaw) && !runtimeConfigService.getBoolean("auth.register.phone.enabled", true)) {
            throw new BusinessException("当前已关闭手机号注册，请勿填写手机号");
        }
        String username = dto.getUsername().trim();

        AuthUser existUser = authUserRepository.selectOne(
                new LambdaQueryWrapper<AuthUser>()
                        .eq(AuthUser::getUsername, username)
                        .eq(AuthUser::getDeleted, 0)
        );

        if (existUser != null) {
            throw new BusinessException("Username already exists");
        }
        OpsAccount existOps = opsAccountRepository.selectOne(
                new LambdaQueryWrapper<OpsAccount>()
                        .eq(OpsAccount::getUsername, username)
                        .eq(OpsAccount::getDeleted, 0)
        );
        if (existOps != null) {
            throw new BusinessException("Username already exists");
        }
        if (StringUtils.hasText(emailRaw)) {
            String em = emailRaw;
            if (ValidationUtil.isValidEmail(em)) {
                OpsAccount emailOps = opsAccountRepository.selectOne(
                        new LambdaQueryWrapper<OpsAccount>()
                                .eq(OpsAccount::getDeleted, 0)
                                .apply("LOWER(email) = LOWER({0})", em)
                );
                if (emailOps != null) {
                    throw new BusinessException("邮箱已被使用");
                }
            }
        }

        AuthUser user = new AuthUser();
        user.setUsername(username);
        user.setPassword(passwordEncoder.encode(dto.getPassword()));
        user.setEmail(StringUtils.hasText(emailRaw) ? emailRaw : null);
        user.setPhone(StringUtils.hasText(phoneRaw) ? phoneRaw : null);
        user.setRealName(null);
        user.setIdCardNo(null);
        user.setRealNameVerified(0);
        user.setStatus(1);
        user.setIsAdmin(0);
        if (clientIp != null && !clientIp.isBlank()) {
            user.setRegisterIp(clientIp.trim());
        }

        authUserRepository.insert(user);

        return buildUserAuthVO(user);
    }

    public AuthVO refreshToken(String refreshToken) {
        JwtClaims claims = jwtService.parseRefreshTokenClaims(refreshToken);
        AuthPortal portal = AuthPortal.fromJwtClaim(claims.portalClaim()).orElse(AuthPortal.USER);
        AuthSubjectType subjectType = AuthSubjectType.fromClaim(claims.subjectTypeClaim(), portal);
        if (subjectType == AuthSubjectType.OPS) {
            OpsAccount account = opsAccountRepository.selectById(claims.principalId());
            if (account == null || account.getDeleted() != 0 || account.getStatus() != 1) {
                throw new BusinessException(401, "Unauthorized");
            }
            ensureOpsRoleUsable(account.getRoleId());
            return buildOpsAuthVO(account);
        }
        AuthUser user = authUserRepository.selectById(claims.principalId());
        if (user == null || user.getDeleted() != 0 || user.getStatus() != 1 || (user.getIsAdmin() != null && user.getIsAdmin() == 1)) {
            throw new BusinessException(401, "Unauthorized");
        }
        return buildUserAuthVO(user);
    }

    public void sendCode(SendCodeDTO dto) {
        AuthUser user = authUserRepository.selectOne(
                new LambdaQueryWrapper<AuthUser>()
                        .eq(AuthUser::getUsername, dto.getUsername())
                        .eq(AuthUser::getDeleted, 0)
                        .eq(AuthUser::getIsAdmin, 0)
        );
        if (user == null) {
            throw new ResourceNotFoundException("User not found");
        }
        String code = String.format("%06d", RANDOM.nextInt(1_000_000));
        resetCodeStore.put(dto.getUsername(), new CodeEntry(code, LocalDateTime.now().plusMinutes(CODE_EXPIRE_MINUTES)));

        boolean delivered = false;
        String phone = user.getPhone();
        String email = user.getEmail();

        if (smsSendService.canSend() && StringUtils.hasText(phone)) {
            try {
                smsSendService.sendVerificationCode(phone, code);
                delivered = true;
                log.info("send_reset_code delivered by SMS username={}", dto.getUsername());
            } catch (BusinessException e) {
                log.warn("SMS send failed for {}: {}", dto.getUsername(), e.getMessage());
            }
        }
        if (!delivered && mailSendService.canSend() && StringUtils.hasText(email) && ValidationUtil.isValidEmail(email.trim())) {
            try {
                mailTemplateDispatchService.send(
                        email.trim(),
                        MailTemplateKind.RESET_PASSWORD.getKey(),
                        Map.of(
                                "code", code,
                                "minutes", String.valueOf(CODE_EXPIRE_MINUTES),
                                "username", dto.getUsername()));
                delivered = true;
                log.info("send_reset_code delivered by mail username={}", dto.getUsername());
            } catch (BusinessException e) {
                log.warn("Mail send failed for {}: {}", dto.getUsername(), e.getMessage());
            }
        }
        if (!delivered) {
            log.info("send_reset_code username={} code={} expireIn={}m（未发短信/邮件：未启用或配置不全，仅日志）",
                    dto.getUsername(), code, CODE_EXPIRE_MINUTES);
        }
    }

    public void resetPassword(ResetPasswordDTO dto) {
        if (!ValidationUtil.isValidPassword(dto.getNewPassword())) {
            throw new BusinessException("Password must be 6-32 characters");
        }
        AuthUser user = authUserRepository.selectOne(
                new LambdaQueryWrapper<AuthUser>()
                        .eq(AuthUser::getUsername, dto.getUsername())
                        .eq(AuthUser::getDeleted, 0)
                        .eq(AuthUser::getIsAdmin, 0)
        );
        if (user == null) {
            throw new ResourceNotFoundException("User not found");
        }
        CodeEntry codeEntry = resetCodeStore.get(dto.getUsername());
        if (codeEntry == null || LocalDateTime.now().isAfter(codeEntry.expireAt())) {
            resetCodeStore.remove(dto.getUsername());
            throw new BusinessException("Verification code expired");
        }
        if (!codeEntry.code().equals(dto.getCode())) {
            throw new BusinessException("Invalid verification code");
        }
        user.setPassword(passwordEncoder.encode(dto.getNewPassword()));
        authUserRepository.updateById(user);
        resetCodeStore.remove(dto.getUsername());
    }

    /**
     * userId 为空（未登录）时不能使用 null 作为缓存键；此时跳过缓存，走下方 resolveAuthContext（通常返回 401）。
     */
    @Cacheable(
            value = "userInfo",
            key = "T(com.starrynight.starrynight.framework.common.util.ThreadLocalUtil).getUserId()",
            condition = "T(com.starrynight.starrynight.framework.common.util.ThreadLocalUtil).getUserId() != null",
            unless = "#result == null")
    public AuthVO.UserInfo getCurrentUser() {
        AuthContext ctx = resolveAuthContext();
        if (ctx == null) {
            throw new BusinessException(401, "Unauthorized");
        }
        if (ctx.portal() == AuthPortal.OPS) {
            OpsAccount account = opsAccountRepository.selectById(ctx.principalId());
            if (account == null || account.getDeleted() != 0 || account.getStatus() != 1) {
                throw new BusinessException(401, "Unauthorized");
            }
            ensureOpsRoleUsable(account.getRoleId());
            return buildOpsUserInfo(account);
        } else {
            AuthUser user = authUserRepository.selectById(ctx.principalId());
            if (user == null || user.getDeleted() != 0 || user.getStatus() != 1 || (user.getIsAdmin() != null && user.getIsAdmin() == 1)) {
                throw new BusinessException(401, "Unauthorized");
            }
            return buildUserInfo(user);
        }
    }

    /**
     * 退出登录（当前阶段为无状态 JWT，服务端做最小审计校验）。
     */
    public void logout() {
        AuthContext ctx = resolveAuthContext();
        if (ctx == null) {
            throw new BusinessException(401, "Unauthorized");
        }
        if (ctx.portal() == AuthPortal.OPS) {
            OpsAccount account = opsAccountRepository.selectById(ctx.principalId());
            if (account == null || account.getDeleted() != 0) {
                throw new BusinessException(401, "Unauthorized");
            }
        } else {
            AuthUser user = authUserRepository.selectById(ctx.principalId());
            if (user == null || user.getDeleted() != 0 || (user.getIsAdmin() != null && user.getIsAdmin() == 1)) {
                throw new BusinessException(401, "Unauthorized");
            }
        }
    }

    private AuthVO buildUserAuthVO(AuthUser user) {
        AuthVO vo = new AuthVO();
        vo.setAuthPortal(AuthPortal.USER.name());
        vo.setAccessToken(jwtService.generateAccessToken(user.getId(), AuthPortal.USER.name(), AuthSubjectType.USER.name()));
        vo.setRefreshToken(jwtService.generateRefreshToken(user.getId(), AuthPortal.USER.name(), AuthSubjectType.USER.name()));
        vo.setExpiresIn(86400000L);
        vo.setUser(buildUserInfo(user));
        return vo;
    }

    private AuthVO buildOpsAuthVO(OpsAccount account) {
        AuthVO vo = new AuthVO();
        vo.setAuthPortal(AuthPortal.OPS.name());
        vo.setAccessToken(jwtService.generateAccessToken(account.getId(), AuthPortal.OPS.name(), AuthSubjectType.OPS.name()));
        vo.setRefreshToken(jwtService.generateRefreshToken(account.getId(), AuthPortal.OPS.name(), AuthSubjectType.OPS.name()));
        vo.setExpiresIn(86400000L);
        vo.setUser(buildOpsUserInfo(account));
        return vo;
    }

    private AuthVO.UserInfo buildUserInfo(AuthUser user) {
        AuthVO.UserInfo userInfo = new AuthVO.UserInfo();
        userInfo.setId(user.getId());
        userInfo.setUsername(user.getUsername());
        userInfo.setEmail(user.getEmail());
        userInfo.setPhone(user.getPhone());
        userInfo.setAvatar(user.getAvatar());
        userInfo.setStatus(user.getStatus());
        userInfo.setIsAdmin(user.getIsAdmin());
        boolean rnOk = user.getRealNameVerified() != null && user.getRealNameVerified() == 1;
        userInfo.setRealNameVerified(rnOk);
        return userInfo;
    }

    private AuthVO.UserInfo buildOpsUserInfo(OpsAccount account) {
        AuthVO.UserInfo userInfo = new AuthVO.UserInfo();
        userInfo.setId(account.getId());
        userInfo.setUsername(account.getUsername());
        userInfo.setEmail(account.getEmail());
        userInfo.setStatus(account.getStatus());
        userInfo.setIsAdmin(1);
        if (account.getRoleId() != null) {
            AdminRole role = adminRoleRepository.selectById(account.getRoleId());
            if (role != null) {
                userInfo.setRoleCode(role.getCode());
                userInfo.setRoleName(role.getName());
            }
        }
        return userInfo;
    }

    private void ensureOpsRoleUsable(Long roleId) {
        if (roleId == null || roleId <= 0) {
            throw new BusinessException("Ops role not assigned");
        }
        AdminRole role = adminRoleRepository.selectById(roleId);
        if (role == null || role.getStatus() == null || role.getStatus() != 1) {
            throw new BusinessException("Ops role is disabled");
        }
    }

    private AuthContext resolveAuthContext() {
        Long userId = ThreadLocalUtil.getUserId();
        AuthPortal portal = null;
        Authentication auth = SecurityContextHolder.getContext().getAuthentication();
        if (auth != null && auth.getDetails() instanceof JwtClaims claims) {
            portal = AuthPortal.fromJwtClaim(claims.portalClaim()).orElse(AuthPortal.USER);
        }
        if (userId != null) {
            if (portal == null) {
                portal = AuthPortal.USER;
            }
            return new AuthContext(userId, portal);
        }
        if (auth != null
                && !(auth instanceof AnonymousAuthenticationToken)
                && auth.getPrincipal() instanceof Long principalId) {
            if (portal == null) {
                portal = AuthPortal.USER;
            }
            return new AuthContext(principalId, portal);
        }
        return null;
    }

    private record AuthContext(Long principalId, AuthPortal portal) {}

    private record CodeEntry(String code, LocalDateTime expireAt) {
    }

}

