package com.starrynight.starrynight.system.ops.service;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.util.ValidationUtil;
import com.starrynight.starrynight.system.auth.entity.OpsAccount;
import com.starrynight.starrynight.system.auth.repository.OpsAccountRepository;
import com.starrynight.starrynight.system.auth.service.AuthService;
import com.starrynight.starrynight.system.auth.vo.AuthVO;
import com.starrynight.starrynight.system.ops.dto.OpsSelfPasswordDTO;
import com.starrynight.starrynight.system.ops.dto.OpsSelfProfileDTO;
import com.starrynight.starrynight.system.ops.security.OpsSecurityHelper;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.cache.CacheManager;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

@Service
public class OpsSelfService {

    @Autowired
    private OpsAccountRepository opsAccountRepository;
    @Autowired
    private OpsAccountService opsAccountService;
    @Autowired
    private OpsSecurityHelper opsSecurityHelper;
    @Autowired
    private AuthService authService;
    @Autowired(required = false)
    private CacheManager cacheManager;

    private final BCryptPasswordEncoder passwordEncoder = new BCryptPasswordEncoder();

    private void evictUserInfoCache(Long principalId) {
        if (cacheManager == null || principalId == null) {
            return;
        }
        var cache = cacheManager.getCache("userInfo");
        if (cache != null) {
            cache.evict(principalId);
        }
    }

    @Transactional
    public AuthVO.UserInfo updateProfile(OpsSelfProfileDTO dto) {
        Long id = ThreadLocalUtil.getUserId();
        if (id == null) {
            throw new BusinessException(401, "未登录");
        }
        OpsAccount account = opsAccountRepository.selectById(id);
        if (account == null || account.getDeleted() != 0 || account.getStatus() != 1) {
            throw new BusinessException(401, "账号不可用");
        }

        if (dto.getUsername() != null && !dto.getUsername().isBlank()) {
            if (!opsSecurityHelper.currentOpsIsSuperAdmin()) {
                throw new BusinessException("仅超级管理员可修改登录用户名");
            }
            String newUsername = dto.getUsername().trim();
            if (!newUsername.equals(account.getUsername())) {
                if (!ValidationUtil.isValidUsername(newUsername)) {
                    throw new BusinessException("用户名格式无效（4-20位字母数字下划线）");
                }
                opsAccountService.ensureUsernameAvailable(newUsername, id);
                account.setUsername(newUsername);
            }
        }

        if (dto.getEmail() != null) {
            String raw = dto.getEmail().trim();
            if (raw.isEmpty()) {
                account.setEmail(null);
            } else {
                if (!ValidationUtil.isValidEmail(raw)) {
                    throw new BusinessException("邮箱格式无效");
                }
                opsAccountService.ensureEmailNotUsed(raw, id);
                account.setEmail(raw);
            }
        }

        opsAccountRepository.updateById(account);
        evictUserInfoCache(id);
        OpsAccount reloaded = opsAccountRepository.selectById(id);
        if (reloaded == null) {
            throw new BusinessException("更新后加载账号失败");
        }
        return authService.opsUserInfoFromAccount(reloaded);
    }

    @Transactional
    public void updatePassword(OpsSelfPasswordDTO dto) {
        Long id = ThreadLocalUtil.getUserId();
        if (id == null) {
            throw new BusinessException(401, "未登录");
        }
        OpsAccount account = opsAccountRepository.selectById(id);
        if (account == null || account.getDeleted() != 0 || account.getStatus() != 1) {
            throw new BusinessException(401, "账号不可用");
        }
        if (!passwordEncoder.matches(dto.getOldPassword(), account.getPassword())) {
            throw new BusinessException("当前密码错误");
        }
        if (!ValidationUtil.isValidPassword(dto.getNewPassword())) {
            throw new BusinessException("新密码须为6-32位");
        }
        if (dto.getOldPassword().equals(dto.getNewPassword())) {
            throw new BusinessException("新密码不能与当前密码相同");
        }
        account.setPassword(passwordEncoder.encode(dto.getNewPassword()));
        opsAccountRepository.updateById(account);
        evictUserInfoCache(id);
    }
}
