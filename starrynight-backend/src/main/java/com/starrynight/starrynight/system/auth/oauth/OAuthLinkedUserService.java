package com.starrynight.starrynight.system.auth.oauth;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.util.ValidationUtil;
import com.starrynight.starrynight.system.auth.entity.AuthOauthLink;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.entity.OpsAccount;
import com.starrynight.starrynight.system.auth.repository.AuthOauthLinkRepository;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.auth.repository.OpsAccountRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;

import java.security.SecureRandom;
import java.time.LocalDateTime;
import java.util.Base64;
import java.util.Locale;

@Service
public class OAuthLinkedUserService {

    private static final SecureRandom RANDOM = new SecureRandom();

    private final AuthUserRepository authUserRepository;
    private final AuthOauthLinkRepository authOauthLinkRepository;
    private final OpsAccountRepository opsAccountRepository;
    private final BCryptPasswordEncoder passwordEncoder = new BCryptPasswordEncoder();

    @Autowired
    public OAuthLinkedUserService(
            AuthUserRepository authUserRepository,
            AuthOauthLinkRepository authOauthLinkRepository,
            OpsAccountRepository opsAccountRepository) {
        this.authUserRepository = authUserRepository;
        this.authOauthLinkRepository = authOauthLinkRepository;
        this.opsAccountRepository = opsAccountRepository;
    }

    public AuthUser findOrCreate(String provider, OAuthUserProfile profile) {
        if (!StringUtils.hasText(provider)) {
            throw new BusinessException("缺少 OAuth 渠道");
        }
        if (profile == null || !StringUtils.hasText(profile.externalId())) {
            throw new BusinessException("第三方用户标识无效");
        }
        String externalId = profile.externalId().trim();

        AuthOauthLink link = authOauthLinkRepository.selectOne(
                new LambdaQueryWrapper<AuthOauthLink>()
                        .eq(AuthOauthLink::getProvider, provider)
                        .eq(AuthOauthLink::getExternalId, externalId));
        if (link != null) {
            AuthUser u = authUserRepository.selectById(link.getUserId());
            if (u != null && u.getDeleted() == 0 && u.getStatus() != null && u.getStatus() == 1
                    && (u.getIsAdmin() == null || u.getIsAdmin() == 0)) {
                maybeSyncProfile(u, profile);
                return u;
            }
        }

        String emailRaw = profile.email() != null ? profile.email().trim() : "";
        if (ValidationUtil.isValidEmail(emailRaw)) {
            AuthUser byEmail = authUserRepository.selectOne(
                    new LambdaQueryWrapper<AuthUser>()
                            .eq(AuthUser::getDeleted, 0)
                            .eq(AuthUser::getIsAdmin, 0)
                            .apply("LOWER(email) = LOWER({0})", emailRaw));
            if (byEmail != null && byEmail.getStatus() != null && byEmail.getStatus() == 1) {
                insertLinkIfAbsent(provider, byEmail.getId(), externalId);
                maybeSyncProfile(byEmail, profile);
                return byEmail;
            }
        }

        String username = allocateUsername(
                pickUsernameCandidate(profile.usernameHint(), externalId, usernamePrefix(provider)),
                externalId,
                usernamePrefix(provider));
        AuthUser nu = new AuthUser();
        nu.setUsername(username);
        nu.setPassword(randomPasswordHash());
        nu.setEmail(ValidationUtil.isValidEmail(emailRaw) ? emailRaw : null);
        String avatar = profile.avatarUrl() != null ? profile.avatarUrl().trim() : "";
        nu.setAvatar(StringUtils.hasText(avatar) ? avatar : null);
        nu.setStatus(1);
        nu.setIsAdmin(0);
        authUserRepository.insert(nu);
        insertLinkIfAbsent(provider, nu.getId(), externalId);
        return nu;
    }

    private static String usernamePrefix(String provider) {
        if (provider != null && provider.startsWith("ZEVOST_") && provider.length() > 7) {
            return "zv" + provider.substring(7).toLowerCase(Locale.ROOT);
        }
        return switch (provider) {
            case "LINUXDO" -> "ldo";
            case "GITHUB" -> "gh";
            case "GOOGLE" -> "gl";
            case "WECHAT" -> "wx";
            case "QQ" -> "qq";
            default -> "oa";
        };
    }

    private void insertLinkIfAbsent(String provider, long userId, String externalId) {
        Long cnt = authOauthLinkRepository.selectCount(
                new LambdaQueryWrapper<AuthOauthLink>()
                        .eq(AuthOauthLink::getProvider, provider)
                        .eq(AuthOauthLink::getExternalId, externalId));
        if (cnt != null && cnt > 0) {
            return;
        }
        AuthOauthLink row = new AuthOauthLink();
        row.setProvider(provider);
        row.setExternalId(externalId);
        row.setUserId(userId);
        row.setCreateTime(LocalDateTime.now());
        authOauthLinkRepository.insert(row);
    }

    private void maybeSyncProfile(AuthUser u, OAuthUserProfile profile) {
        boolean changed = false;
        String avatar = profile.avatarUrl() != null ? profile.avatarUrl().trim() : "";
        if (StringUtils.hasText(avatar) && !StringUtils.hasText(u.getAvatar())) {
            u.setAvatar(avatar);
            changed = true;
        }
        String emailRaw = profile.email() != null ? profile.email().trim() : "";
        if (ValidationUtil.isValidEmail(emailRaw) && !StringUtils.hasText(u.getEmail())) {
            AuthUser clash = authUserRepository.selectOne(
                    new LambdaQueryWrapper<AuthUser>()
                            .eq(AuthUser::getDeleted, 0)
                            .apply("LOWER(email) = LOWER({0})", emailRaw));
            if (clash == null || clash.getId().equals(u.getId())) {
                u.setEmail(emailRaw);
                changed = true;
            }
        }
        if (changed) {
            authUserRepository.updateById(u);
        }
    }

    private String pickUsernameCandidate(String hint, String externalId, String prefix) {
        String login = hint != null ? hint.trim() : "";
        if (ValidationUtil.isValidUsername(login)) {
            return login;
        }
        String digits = externalId.replaceAll("\\D", "");
        String base = prefix + (digits.length() >= 4 ? digits : externalId);
        if (base.length() > 20) {
            base = base.substring(0, 20);
        }
        if (ValidationUtil.isValidUsername(base)) {
            return base;
        }
        return prefix + externalId.substring(Math.max(0, externalId.length() - 17));
    }

    private String allocateUsername(String preferred, String externalId, String prefix) {
        String candidate = preferred;
        for (int i = 0; i < 40; i++) {
            if (ValidationUtil.isValidUsername(candidate) && !usernameTaken(candidate)) {
                return candidate;
            }
            String suffix = String.valueOf(100 + RANDOM.nextInt(900));
            String base = "u" + suffix + externalId.hashCode();
            base = base.replace("-", "");
            if (base.length() > 20) {
                base = base.substring(0, 20);
            }
            if (!ValidationUtil.isValidUsername(base)) {
                base = prefix + externalId.substring(Math.max(0, externalId.length() - 15));
                if (base.length() > 20) {
                    base = base.substring(0, 20);
                }
            }
            candidate = base;
        }
        throw new BusinessException("无法分配站内用户名，请稍后重试");
    }

    private boolean usernameTaken(String username) {
        Long u = authUserRepository.selectCount(
                new LambdaQueryWrapper<AuthUser>()
                        .eq(AuthUser::getUsername, username)
                        .eq(AuthUser::getDeleted, 0));
        if (u != null && u > 0) {
            return true;
        }
        Long o = opsAccountRepository.selectCount(
                new LambdaQueryWrapper<OpsAccount>()
                        .eq(OpsAccount::getUsername, username)
                        .eq(OpsAccount::getDeleted, 0));
        return o != null && o > 0;
    }

    private String randomPasswordHash() {
        byte[] b = new byte[24];
        RANDOM.nextBytes(b);
        String raw = Base64.getUrlEncoder().withoutPadding().encodeToString(b);
        return passwordEncoder.encode(raw);
    }
}
