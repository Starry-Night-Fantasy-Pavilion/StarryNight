package com.starrynight.starrynight.system.system.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.system.dto.SystemConfigDTO;
import com.starrynight.starrynight.system.system.entity.SystemConfig;
import com.starrynight.starrynight.system.system.repository.SystemConfigRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.BeanUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.cache.annotation.CacheEvict;
import org.springframework.cache.annotation.Cacheable;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@Service
public class SystemConfigService {

    private static final Logger log = LoggerFactory.getLogger(SystemConfigService.class);

    @Autowired
    private SystemConfigRepository systemConfigRepository;

    @Autowired
    private RuntimeConfigService runtimeConfigService;

    public List<SystemConfigDTO> listByGroup(String group) {
        LambdaQueryWrapper<SystemConfig> wrapper = new LambdaQueryWrapper<>();
        if (group != null && !group.isEmpty()) {
            wrapper.eq(SystemConfig::getConfigGroup, group);
        }
        return systemConfigRepository.selectList(wrapper).stream()
                .map(this::toDTO)
                .collect(Collectors.toList());
    }

    @Cacheable(value = "systemConfig", key = "#key")
    public String getValue(String key) {
        SystemConfig config = systemConfigRepository.selectOne(
                new LambdaQueryWrapper<SystemConfig>()
                        .eq(SystemConfig::getConfigKey, key)
        );
        return config != null ? config.getConfigValue() : null;
    }

    public SystemConfigDTO getByKey(String key) {
        SystemConfig config = systemConfigRepository.selectOne(
                new LambdaQueryWrapper<SystemConfig>()
                        .eq(SystemConfig::getConfigKey, key)
        );
        if (config == null) {
            throw new BusinessException(404, "Config not found: " + key);
        }
        return toDTO(config);
    }

    @Transactional
    @CacheEvict(value = "systemConfig", key = "#dto.configKey")
    public SystemConfigDTO create(SystemConfigDTO dto) {
        SystemConfig exist = systemConfigRepository.selectOne(
                new LambdaQueryWrapper<SystemConfig>()
                        .eq(SystemConfig::getConfigKey, dto.getConfigKey())
        );
        if (exist != null) {
            throw new BusinessException("Config key already exists: " + dto.getConfigKey());
        }

        SystemConfig config = toEntity(dto);
        systemConfigRepository.insert(config);
        log.info("create_config key={}", dto.getConfigKey());
        runtimeConfigService.reloadFromDatabase();
        return toDTO(config);
    }

    @Transactional
    @CacheEvict(value = "systemConfig", key = "#dto.configKey")
    public SystemConfigDTO update(SystemConfigDTO dto) {
        if (dto.getId() == null) {
            throw new BusinessException("Config ID is required");
        }

        SystemConfig config = systemConfigRepository.selectById(dto.getId());
        if (config == null) {
            throw new BusinessException(404, "Config not found");
        }

        if (config.getEditable() == 0) {
            throw new BusinessException("Config is not editable");
        }

        config.setConfigValue(dto.getConfigValue());
        config.setConfigName(dto.getConfigName());
        config.setDescription(dto.getDescription());

        systemConfigRepository.updateById(config);
        log.info("update_config key={}", dto.getConfigKey());
        runtimeConfigService.reloadFromDatabase();
        return toDTO(config);
    }

    @Transactional
    @CacheEvict(value = "systemConfig", key = "#key")
    public void delete(String key) {
        SystemConfig config = systemConfigRepository.selectOne(
                new LambdaQueryWrapper<SystemConfig>()
                        .eq(SystemConfig::getConfigKey, key)
        );
        if (config == null) {
            throw new BusinessException(404, "Config not found");
        }

        if (config.getEditable() == 0) {
            throw new BusinessException("Config is not deletable");
        }

        systemConfigRepository.deleteById(config.getId());
        log.info("delete_config key={}", key);
        runtimeConfigService.reloadFromDatabase();
    }

    /**
     * 按 key 写入或更新配置（供存储等模块批量落库），并刷新运行时快照与缓存。
     */
    @Transactional
    @CacheEvict(value = "systemConfig", allEntries = true)
    public void upsertConfigValue(String key, String value, String configName, String configGroup, String description) {
        SystemConfig exist = systemConfigRepository.selectOne(
                new LambdaQueryWrapper<SystemConfig>()
                        .eq(SystemConfig::getConfigKey, key)
        );
        if (exist == null) {
            SystemConfig c = new SystemConfig();
            c.setConfigKey(key);
            c.setConfigValue(value);
            c.setConfigType("string");
            c.setConfigName(configName != null ? configName : key);
            c.setConfigGroup(configGroup != null ? configGroup : "system");
            c.setDescription(description);
            c.setEditable(1);
            systemConfigRepository.insert(c);
        } else {
            exist.setConfigValue(value);
            if (configName != null) {
                exist.setConfigName(configName);
            }
            if (configGroup != null) {
                exist.setConfigGroup(configGroup);
            }
            if (description != null) {
                exist.setDescription(description);
            }
            systemConfigRepository.updateById(exist);
        }
        runtimeConfigService.reloadFromDatabase();
    }

    /**
     * 从 MySQL 重新加载 {@code system_config} 到内存快照，并触发 Redis/Rabbit 等热切换钩子。
     * 适用于直改库、执行 SQL 脚本后；同时清空 {@code systemConfig} 读缓存，避免与库不一致。
     */
    @CacheEvict(value = "systemConfig", allEntries = true)
    public void reloadRuntimeSnapshot() {
        runtimeConfigService.reloadFromDatabase();
    }

    public Map<String, String> getGroupedConfigs() {
        List<SystemConfig> configs = systemConfigRepository.selectList(null);
        return configs.stream()
                .collect(Collectors.toMap(
                        SystemConfig::getConfigKey,
                        SystemConfig::getConfigValue,
                        (v1, v2) -> v1
                ));
    }

    private SystemConfigDTO toDTO(SystemConfig config) {
        SystemConfigDTO dto = new SystemConfigDTO();
        BeanUtils.copyProperties(config, dto);
        return dto;
    }

    private SystemConfig toEntity(SystemConfigDTO dto) {
        SystemConfig config = new SystemConfig();
        BeanUtils.copyProperties(dto, config);
        return config;
    }
}

