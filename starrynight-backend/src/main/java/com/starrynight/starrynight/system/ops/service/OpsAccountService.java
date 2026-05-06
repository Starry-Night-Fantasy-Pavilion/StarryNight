package com.starrynight.starrynight.system.ops.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ValidationUtil;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.entity.OpsAccount;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.auth.repository.OpsAccountRepository;
import com.starrynight.starrynight.system.ops.dto.OpsAccountCreateDTO;
import com.starrynight.starrynight.system.ops.dto.OpsAccountDTO;
import com.starrynight.starrynight.system.ops.dto.OpsAccountPasswordDTO;
import com.starrynight.starrynight.system.ops.dto.OpsAccountUpdateDTO;
import com.starrynight.starrynight.system.ops.security.OpsSecurityHelper;
import com.starrynight.starrynight.system.rbac.entity.AdminRole;
import com.starrynight.starrynight.system.rbac.repository.AdminRoleRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

@Service
public class OpsAccountService {

    @Autowired
    private OpsAccountRepository opsAccountRepository;
    @Autowired
    private AdminRoleRepository adminRoleRepository;
    @Autowired
    private AuthUserRepository authUserRepository;
    @Autowired
    private OpsSecurityHelper opsSecurityHelper;

    private final BCryptPasswordEncoder passwordEncoder = new BCryptPasswordEncoder();

    public Page<OpsAccountDTO> list(String keyword, Integer status, int page, int size) {
        LambdaQueryWrapper<OpsAccount> wrapper = new LambdaQueryWrapper<>();
        if (status != null) {
            wrapper.eq(OpsAccount::getStatus, status);
        }
        if (keyword != null && !keyword.isBlank()) {
            String kw = keyword.trim();
            wrapper.and(w -> w.like(OpsAccount::getUsername, kw).or().like(OpsAccount::getEmail, kw));
        }
        wrapper.orderByDesc(OpsAccount::getCreateTime);
        Page<OpsAccount> accountPage = opsAccountRepository.selectPage(new Page<>(page, size), wrapper);

        Set<Long> roleIds = accountPage.getRecords().stream()
                .map(OpsAccount::getRoleId)
                .filter(id -> id != null && id > 0)
                .collect(Collectors.toSet());
        Map<Long, AdminRole> roleMap = roleIds.isEmpty()
                ? Map.of()
                : adminRoleRepository.selectBatchIds(roleIds).stream()
                .collect(Collectors.toMap(AdminRole::getId, r -> r, (a, b) -> a, HashMap::new));

        List<OpsAccountDTO> records = accountPage.getRecords().stream().map(acc -> {
            OpsAccountDTO dto = new OpsAccountDTO();
            dto.setId(acc.getId());
            dto.setUsername(acc.getUsername());
            dto.setEmail(acc.getEmail());
            dto.setRoleId(acc.getRoleId());
            AdminRole role = roleMap.get(acc.getRoleId());
            dto.setRoleName(role == null ? null : role.getName());
            dto.setStatus(acc.getStatus());
            dto.setCreateTime(acc.getCreateTime());
            return dto;
        }).collect(Collectors.toList());

        Page<OpsAccountDTO> res = new Page<>(accountPage.getCurrent(), accountPage.getSize(), accountPage.getTotal());
        res.setRecords(records);
        return res;
    }

    @Transactional
    public OpsAccountDTO create(OpsAccountCreateDTO dto) {
        opsSecurityHelper.requireSuperAdmin();
        String username = dto.getUsername().trim();
        validateCommon(username, dto.getRoleId(), dto.getStatus());
        ensureUsernameNotUsed(username, null);

        String emailNorm = null;
        if (dto.getEmail() != null && !dto.getEmail().isBlank()) {
            emailNorm = dto.getEmail().trim();
            if (!ValidationUtil.isValidEmail(emailNorm)) {
                throw new BusinessException("邮箱格式无效");
            }
            ensureEmailNotUsed(emailNorm, null);
        }

        OpsAccount account = new OpsAccount();
        account.setUsername(username);
        account.setEmail(emailNorm);
        account.setPassword(passwordEncoder.encode(dto.getPassword()));
        account.setRoleId(dto.getRoleId());
        account.setStatus(dto.getStatus());
        opsAccountRepository.insert(account);
        return toDTO(account);
    }

    @Transactional
    public OpsAccountDTO update(Long id, OpsAccountUpdateDTO dto) {
        opsSecurityHelper.requireSuperAdmin();
        validateCommon(null, dto.getRoleId(), dto.getStatus());
        OpsAccount account = opsAccountRepository.selectById(id);
        if (account == null || account.getDeleted() != 0) {
            throw new ResourceNotFoundException("Ops account not found");
        }
        if (dto.getEmail() != null) {
            String raw = dto.getEmail().trim();
            if (raw.isEmpty()) {
                account.setEmail(null);
            } else {
                if (!ValidationUtil.isValidEmail(raw)) {
                    throw new BusinessException("邮箱格式无效");
                }
                ensureEmailNotUsed(raw, id);
                account.setEmail(raw);
            }
        }
        account.setRoleId(dto.getRoleId());
        account.setStatus(dto.getStatus());
        opsAccountRepository.updateById(account);
        return toDTO(account);
    }

    @Transactional
    public void resetPassword(Long id, OpsAccountPasswordDTO dto) {
        opsSecurityHelper.requireSuperAdmin();
        if (!ValidationUtil.isValidPassword(dto.getPassword())) {
            throw new BusinessException("Password must be 6-32 characters");
        }
        OpsAccount account = opsAccountRepository.selectById(id);
        if (account == null || account.getDeleted() != 0) {
            throw new ResourceNotFoundException("Ops account not found");
        }
        account.setPassword(passwordEncoder.encode(dto.getPassword()));
        opsAccountRepository.updateById(account);
    }

    private void validateCommon(String username, Long roleId, Integer status) {
        if (username != null && !ValidationUtil.isValidUsername(username)) {
            throw new BusinessException("Invalid username format");
        }
        if (status == null || (status != 0 && status != 1)) {
            throw new BusinessException("Invalid status");
        }
        AdminRole role = adminRoleRepository.selectById(roleId);
        if (role == null || role.getStatus() == null || role.getStatus() != 1) {
            throw new BusinessException("Role not available");
        }
    }

    private void ensureUsernameNotUsed(String username, Long excludeId) {
        OpsAccount existingOps = opsAccountRepository.selectOne(
                new LambdaQueryWrapper<OpsAccount>()
                        .eq(OpsAccount::getUsername, username)
                        .eq(OpsAccount::getDeleted, 0)
        );
        if (existingOps != null && (excludeId == null || !existingOps.getId().equals(excludeId))) {
            throw new BusinessException("Username already exists");
        }
        AuthUser existingUser = authUserRepository.selectOne(
                new LambdaQueryWrapper<AuthUser>()
                        .eq(AuthUser::getUsername, username)
                        .eq(AuthUser::getDeleted, 0)
        );
        if (existingUser != null) {
            throw new BusinessException("Username already exists");
        }
    }

    /**
     * 供个人中心修改用户名时复用校验逻辑。
     */
    public void ensureUsernameAvailable(String username, Long excludeOpsId) {
        ensureUsernameNotUsed(username, excludeOpsId);
    }

    /**
     * 运营邮箱与用户端邮箱均不可冲突（同库双体系）。
     */
    public void ensureEmailNotUsed(String email, Long excludeOpsId) {
        if (email == null || email.isBlank()) {
            return;
        }
        String e = email.trim();
        OpsAccount existingOps = opsAccountRepository.selectOne(
                new LambdaQueryWrapper<OpsAccount>()
                        .eq(OpsAccount::getDeleted, 0)
                        .apply("LOWER(email) = LOWER({0})", e)
        );
        if (existingOps != null && (excludeOpsId == null || !existingOps.getId().equals(excludeOpsId))) {
            throw new BusinessException("该邮箱已被运营账号占用");
        }
        AuthUser existingUser = authUserRepository.selectOne(
                new LambdaQueryWrapper<AuthUser>()
                        .eq(AuthUser::getDeleted, 0)
                        .apply("LOWER(email) = LOWER({0})", e)
        );
        if (existingUser != null) {
            throw new BusinessException("该邮箱已被用户端占用");
        }
    }

    private OpsAccountDTO toDTO(OpsAccount account) {
        OpsAccountDTO dto = new OpsAccountDTO();
        dto.setId(account.getId());
        dto.setUsername(account.getUsername());
        dto.setEmail(account.getEmail());
        dto.setRoleId(account.getRoleId());
        AdminRole role = account.getRoleId() == null ? null : adminRoleRepository.selectById(account.getRoleId());
        dto.setRoleName(role == null ? null : role.getName());
        dto.setStatus(account.getStatus());
        dto.setCreateTime(account.getCreateTime());
        return dto;
    }
}
