package com.starrynight.starrynight.system.storage;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import io.minio.BucketExistsArgs;
import io.minio.GetPresignedObjectUrlArgs;
import io.minio.MakeBucketArgs;
import io.minio.MinioClient;
import io.minio.PutObjectArgs;
import io.minio.RemoveObjectArgs;
import io.minio.http.Method;
import lombok.extern.slf4j.Slf4j;
import org.springframework.context.annotation.DependsOn;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;
import org.springframework.web.multipart.MultipartFile;

import jakarta.annotation.PostConstruct;
import java.util.UUID;
import java.util.concurrent.TimeUnit;

@Slf4j
@Service
@DependsOn("runtimeConfigService")
public class StorageService {

    private final RuntimeConfigService runtimeConfigService;

    private volatile MinioClient minioClient;
    private volatile String minioFingerprint = "";

    public StorageService(RuntimeConfigService runtimeConfigService) {
        this.runtimeConfigService = runtimeConfigService;
    }

    @PostConstruct
    private void init() {
        refreshMinioClientAndBucket();
    }

    private String minioFingerprint() {
        return String.join("\u0001",
                nz(runtimeConfigService.getProperty("storage.minio.endpoint")),
                nz(runtimeConfigService.getProperty("storage.minio.access-key")),
                nz(runtimeConfigService.getProperty("storage.minio.secret-key")),
                nz(runtimeConfigService.getProperty("storage.minio.bucket"))
        );
    }

    private static String nz(String s) {
        return s == null ? "" : s;
    }

    private MinioClient buildMinioClient() {
        String endpoint = runtimeConfigService.getString("storage.minio.endpoint", "");
        String access = runtimeConfigService.getString("storage.minio.access-key", "");
        String secret = runtimeConfigService.getString("storage.minio.secret-key", "");
        if (!StringUtils.hasText(endpoint) || !StringUtils.hasText(access) || !StringUtils.hasText(secret)) {
            return null;
        }
        return MinioClient.builder()
                .endpoint(endpoint.trim())
                .credentials(access.trim(), secret.trim())
                .build();
    }

    private synchronized void refreshMinioClientAndBucket() {
        String fp = minioFingerprint();
        if (minioClient != null && fp.equals(minioFingerprint)) {
            return;
        }
        minioFingerprint = fp;
        minioClient = buildMinioClient();
        if (minioClient == null) {
            log.info("MinIO 未启用：请在运营端「对象存储配置」填写端点、AccessKey、SecretKey 并保存（写入 system_config）。");
            return;
        }
        if (!StringUtils.hasText(bucketName())) {
            log.info("MinIO 凭证已配置，但未填写 Bucket。请在运营端「对象存储配置」填写 Bucket 并保存。");
            return;
        }
        try {
            String bucketName = bucketName();
            boolean found = minioClient.bucketExists(BucketExistsArgs.builder().bucket(bucketName).build());
            if (!found) {
                minioClient.makeBucket(MakeBucketArgs.builder().bucket(bucketName).build());
                log.info("MinIO bucket '{}' created successfully.", bucketName);
            } else {
                log.info("MinIO bucket '{}' already exists.", bucketName);
            }
        } catch (Exception e) {
            log.error("Error while initializing MinIO bucket", e);
            throw new BusinessException("Failed to initialize MinIO bucket: " + e.getMessage());
        }
    }

    private MinioClient ensureClient() {
        String fp = minioFingerprint();
        if (minioClient != null && fp.equals(minioFingerprint)) {
            return minioClient;
        }
        refreshMinioClientAndBucket();
        return minioClient;
    }

    private String bucketName() {
        String b = runtimeConfigService.getProperty("storage.minio.bucket");
        return b != null ? b.trim() : "";
    }

    /**
     * 运营端是否已填写 MinIO/OSS 连接参数（桶名非空）。未配置时站点素材可落本地磁盘并由 {@code /api/portal/public-asset} 提供访问。
     */
    public boolean isObjectStorageConfigured() {
        String endpoint = runtimeConfigService.getString("storage.minio.endpoint", "");
        String access = runtimeConfigService.getString("storage.minio.access-key", "");
        String secret = runtimeConfigService.getString("storage.minio.secret-key", "");
        return StringUtils.hasText(endpoint)
                && StringUtils.hasText(access)
                && StringUtils.hasText(secret)
                && StringUtils.hasText(bucketName());
    }

    public String uploadFile(MultipartFile file, String path) {
        MinioClient client = ensureClient();
        if (client == null) {
            throw new BusinessException(503, "未配置 MinIO。请在运营端「对象存储配置」中填写并保存。");
        }
        if (!StringUtils.hasText(bucketName())) {
            throw new BusinessException(503, "未配置存储桶。请在运营端「对象存储配置」中填写 Bucket。");
        }
        if (file == null || file.isEmpty()) {
            throw new BusinessException("File is empty");
        }
        try {
            String originalFilename = file.getOriginalFilename();
            String fileExtension = "";
            if (originalFilename != null && originalFilename.contains(".")) {
                fileExtension = originalFilename.substring(originalFilename.lastIndexOf("."));
            }
            String objectName = path + UUID.randomUUID().toString() + fileExtension;

            client.putObject(
                    PutObjectArgs.builder()
                            .bucket(bucketName())
                            .object(objectName)
                            .stream(file.getInputStream(), file.getSize(), -1)
                            .contentType(file.getContentType())
                            .build());

            return getFileUrl(objectName);
        } catch (Exception e) {
            log.error("Error uploading file to MinIO", e);
            throw new BusinessException("Failed to upload file: " + e.getMessage());
        }
    }

    public String getFileUrl(String objectName) {
        MinioClient client = ensureClient();
        if (client == null || !StringUtils.hasText(bucketName())) {
            return null;
        }
        try {
            return client.getPresignedObjectUrl(
                    GetPresignedObjectUrlArgs.builder()
                            .method(Method.GET)
                            .bucket(bucketName())
                            .object(objectName)
                            .expiry(7, TimeUnit.DAYS)
                            .build());
        } catch (Exception e) {
            log.error("Error getting presigned URL for object: {}", objectName, e);
            return null;
        }
    }

    public void deleteFile(String objectName) {
        MinioClient client = ensureClient();
        if (client == null) {
            log.warn("MinIO 未配置，跳过删除: {}", objectName);
            return;
        }
        if (!StringUtils.hasText(bucketName())) {
            log.warn("未配置 Bucket，跳过删除: {}", objectName);
            return;
        }
        try {
            client.removeObject(
                    RemoveObjectArgs.builder()
                            .bucket(bucketName())
                            .object(objectName)
                            .build());
            log.info("Successfully deleted object: {}", objectName);
        } catch (Exception e) {
            log.error("Error deleting object: {}", objectName, e);
            throw new BusinessException("Failed to delete file: " + e.getMessage());
        }
    }
}
