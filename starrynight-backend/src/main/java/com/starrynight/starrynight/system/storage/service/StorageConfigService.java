package com.starrynight.starrynight.system.storage.service;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.storage.dto.StorageConfigDTO;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import com.starrynight.starrynight.system.system.service.SystemConfigService;
import io.minio.BucketExistsArgs;
import io.minio.MinioClient;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.util.Collections;
import java.util.List;

/**
 * 存储运营配置：与 {@code system_config} 中 {@code storage.minio.*} 键同步，并刷新 {@link RuntimeConfigService}。
 */
@Slf4j
@Service
@RequiredArgsConstructor
public class StorageConfigService {

    public static final Long VIRTUAL_DEFAULT_ID = 1L;

    public static final String KEY_ENDPOINT = "storage.minio.endpoint";
    public static final String KEY_ACCESS = "storage.minio.access-key";
    public static final String KEY_SECRET = "storage.minio.secret-key";
    public static final String KEY_BUCKET = "storage.minio.bucket";

    private final RuntimeConfigService runtimeConfigService;
    private final SystemConfigService systemConfigService;

    public List<StorageConfigDTO> listConfigs() {
        return Collections.singletonList(buildVirtualDto(VIRTUAL_DEFAULT_ID));
    }

    public StorageConfigDTO getConfig(Long id) {
        if (id == null || !VIRTUAL_DEFAULT_ID.equals(id)) {
            throw new BusinessException(404, "仅支持默认 MinIO 配置（id=1）");
        }
        return buildVirtualDto(VIRTUAL_DEFAULT_ID);
    }

    public StorageConfigDTO getDefaultConfig() {
        return buildVirtualDto(VIRTUAL_DEFAULT_ID);
    }

    @Transactional
    public StorageConfigDTO createConfig(StorageConfigDTO dto) {
        return upsertVirtual(dto);
    }

    @Transactional
    public StorageConfigDTO updateConfig(Long id, StorageConfigDTO dto) {
        if (id == null || !VIRTUAL_DEFAULT_ID.equals(id)) {
            throw new BusinessException(404, "仅支持更新默认 MinIO 配置（id=1）");
        }
        return upsertVirtual(dto);
    }

    public void deleteConfig(Long id) {
        if (id != null && VIRTUAL_DEFAULT_ID.equals(id)) {
            throw new BusinessException("不能删除默认存储配置，可在系统配置中清空密钥以停用 MinIO。");
        }
        throw new BusinessException(404, "配置不存在");
    }

    public void testConnection(Long id) {
        if (id == null || !VIRTUAL_DEFAULT_ID.equals(id)) {
            throw new BusinessException(404, "仅支持测试默认 MinIO 配置（id=1）");
        }
        StorageConfigDTO cfg = buildVirtualDto(VIRTUAL_DEFAULT_ID);
        if (!StringUtils.hasText(cfg.getEndpoint())
                || !StringUtils.hasText(cfg.getAccessKey())
                || !StringUtils.hasText(cfg.getSecretKey())) {
            throw new BusinessException("请先填写 endpoint、accessKey、secretKey");
        }
        try {
            MinioClient client = MinioClient.builder()
                    .endpoint(cfg.getEndpoint().trim())
                    .credentials(cfg.getAccessKey().trim(), cfg.getSecretKey().trim())
                    .build();
            if (!StringUtils.hasText(cfg.getBucket())) {
                throw new BusinessException("请先在运营端填写 Bucket");
            }
            String bucket = cfg.getBucket().trim();
            client.bucketExists(BucketExistsArgs.builder().bucket(bucket).build());
            log.info("MinIO connection test OK: endpoint={}, bucket={}", cfg.getEndpoint(), bucket);
        } catch (Exception e) {
            log.warn("MinIO connection test failed", e);
            throw new BusinessException("连接失败: " + e.getMessage());
        }
    }

    public StorageConfigDTO getStats() {
        StorageConfigDTO stats = new StorageConfigDTO();
        stats.setId(VIRTUAL_DEFAULT_ID);
        stats.setName("存储统计");
        stats.setTotalStorage(100L * 1024 * 1024 * 1024);
        stats.setUsedStorage(0L);
        stats.setStatus("online");
        return stats;
    }

    private StorageConfigDTO buildVirtualDto(Long id) {
        StorageConfigDTO dto = new StorageConfigDTO();
        dto.setId(id);
        dto.setName("MinIO");
        dto.setType("minio");
        dto.setEndpoint(runtimeConfigService.getString(KEY_ENDPOINT, ""));
        dto.setAccessKey(runtimeConfigService.getString(KEY_ACCESS, ""));
        dto.setSecretKey(runtimeConfigService.getString(KEY_SECRET, ""));
        dto.setBucket(runtimeConfigService.getString(KEY_BUCKET, ""));
        dto.setDomain("");
        boolean enabled = StringUtils.hasText(dto.getAccessKey()) && StringUtils.hasText(dto.getSecretKey());
        dto.setEnabled(enabled);
        dto.setIsDefault(true);
        dto.setStatus(enabled ? "online" : "offline");
        dto.setTotalStorage(100L * 1024 * 1024 * 1024);
        dto.setUsedStorage(0L);
        return dto;
    }

    private StorageConfigDTO upsertVirtual(StorageConfigDTO dto) {
        if (dto.getEndpoint() != null) {
            systemConfigService.upsertConfigValue(KEY_ENDPOINT, dto.getEndpoint(), "MinIO 端点", "storage", "storage.minio.endpoint");
        }
        if (dto.getAccessKey() != null) {
            systemConfigService.upsertConfigValue(KEY_ACCESS, dto.getAccessKey(), "MinIO Access Key", "storage", "storage.minio.access-key");
        }
        if (dto.getSecretKey() != null) {
            systemConfigService.upsertConfigValue(KEY_SECRET, dto.getSecretKey(), "MinIO Secret Key", "storage", "storage.minio.secret-key");
        }
        if (dto.getBucket() != null) {
            systemConfigService.upsertConfigValue(KEY_BUCKET, dto.getBucket(), "MinIO 桶名", "storage", "storage.minio.bucket");
        }
        return buildVirtualDto(VIRTUAL_DEFAULT_ID);
    }
}
