package com.starrynight.starrynight.system.rbac.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.auth.entity.OpsAccount;
import com.starrynight.starrynight.system.auth.repository.OpsAccountRepository;
import com.starrynight.starrynight.system.rbac.dto.AdminRoleDTO;
import com.starrynight.starrynight.system.rbac.entity.AdminRole;
import com.starrynight.starrynight.system.rbac.repository.AdminRoleRepository;
import org.springframework.beans.BeanUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.Collections;
import java.util.List;
import java.util.stream.Collectors;

@Service
public class AdminRoleService {

    @Autowired
    private AdminRoleRepository adminRoleRepository;
    @Autowired
    private OpsAccountRepository opsAccountRepository;

    private final ObjectMapper objectMapper = new ObjectMapper();

    public List<AdminRoleDTO> list(Integer status) {
        LambdaQueryWrapper<AdminRole> wrapper = new LambdaQueryWrapper<>();
        if (status != null) {
            wrapper.eq(AdminRole::getStatus, status);
        }
        wrapper.orderByDesc(AdminRole::getCreateTime);
        return adminRoleRepository.selectList(wrapper).stream().map(this::toDTO).collect(Collectors.toList());
    }

    public AdminRoleDTO getById(Long id) {
        AdminRole role = adminRoleRepository.selectById(id);
        if (role == null) {
            throw new ResourceNotFoundException("Role not found");
        }
        return toDTO(role);
    }

    @Transactional
    public AdminRoleDTO create(AdminRoleDTO dto) {
        AdminRole exists = adminRoleRepository.selectOne(
                new LambdaQueryWrapper<AdminRole>().eq(AdminRole::getCode, dto.getCode())
        );
        if (exists != null) {
            throw new BusinessException("Role code already exists");
        }
        AdminRole role = toEntity(dto);
        adminRoleRepository.insert(role);
        return toDTO(role);
    }

    @Transactional
    public AdminRoleDTO update(Long id, AdminRoleDTO dto) {
        AdminRole role = adminRoleRepository.selectById(id);
        if (role == null) {
            throw new ResourceNotFoundException("Role not found");
        }
        if (!role.getCode().equals(dto.getCode())) {
            AdminRole exists = adminRoleRepository.selectOne(
                    new LambdaQueryWrapper<AdminRole>().eq(AdminRole::getCode, dto.getCode())
            );
            if (exists != null) {
                throw new BusinessException("Role code already exists");
            }
        }
        role.setName(dto.getName());
        role.setCode(dto.getCode());
        role.setDescription(dto.getDescription());
        role.setStatus(dto.getStatus());
        role.setMenuPermissions(serializePermissions(dto.getMenuPermissions()));
        adminRoleRepository.updateById(role);
        return toDTO(role);
    }

    @Transactional
    public void delete(Long id) {
        AdminRole role = adminRoleRepository.selectById(id);
        if (role == null) {
            throw new ResourceNotFoundException("Role not found");
        }
        if ("SUPER_ADMIN".equals(role.getCode())) {
            throw new BusinessException("系统内置超级管理员角色不可删除");
        }
        Long boundCount = opsAccountRepository.selectCount(
                new LambdaQueryWrapper<OpsAccount>()
                        .eq(OpsAccount::getRoleId, id)
                        .eq(OpsAccount::getDeleted, 0)
        );
        if (boundCount != null && boundCount > 0) {
            throw new BusinessException("Role has bound ops accounts");
        }
        adminRoleRepository.deleteById(id);
    }

    private AdminRoleDTO toDTO(AdminRole role) {
        AdminRoleDTO dto = new AdminRoleDTO();
        BeanUtils.copyProperties(role, dto);
        dto.setMenuPermissions(parsePermissions(role.getMenuPermissions()));
        Long userCount = opsAccountRepository.selectCount(
                new LambdaQueryWrapper<OpsAccount>()
                        .eq(OpsAccount::getRoleId, role.getId())
                        .eq(OpsAccount::getDeleted, 0)
        );
        dto.setUserCount(userCount == null ? 0 : userCount.intValue());
        return dto;
    }

    private AdminRole toEntity(AdminRoleDTO dto) {
        AdminRole role = new AdminRole();
        role.setName(dto.getName());
        role.setCode(dto.getCode());
        role.setDescription(dto.getDescription());
        role.setStatus(dto.getStatus());
        role.setMenuPermissions(serializePermissions(dto.getMenuPermissions()));
        return role;
    }

    private String serializePermissions(List<String> permissions) {
        try {
            return objectMapper.writeValueAsString(permissions == null ? Collections.emptyList() : permissions);
        } catch (Exception e) {
            throw new BusinessException("Serialize permissions failed");
        }
    }

    private List<String> parsePermissions(String value) {
        if (value == null || value.isBlank()) {
            return Collections.emptyList();
        }
        try {
            return objectMapper.readValue(value, new TypeReference<List<String>>() {});
        } catch (Exception e) {
            return Collections.emptyList();
        }
    }
}
