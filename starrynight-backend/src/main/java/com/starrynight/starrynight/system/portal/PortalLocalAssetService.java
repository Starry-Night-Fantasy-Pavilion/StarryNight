package com.starrynight.starrynight.system.portal;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.io.InputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.StandardCopyOption;
import java.util.Locale;
import java.util.Set;
import java.util.UUID;
import java.util.regex.Pattern;

/**
 * 未启用对象存储时，站点 Logo / 赞助 HTML 等写入本地目录，由 {@link PortalPublicAssetController} 匿名读取。
 * 目录默认 {@code data/portal-assets}，可通过 {@code portal.local-assets.storage-dir} 覆盖。
 */
@Service
public class PortalLocalAssetService {

    private static final Pattern SAFE_STORED_NAME = Pattern.compile(
            "^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\\.[a-zA-Z0-9]{1,12}$");

    private static final Set<String> SITE_IMAGE_EXT = Set.of(".png", ".jpg", ".jpeg", ".gif", ".webp", ".svg", ".ico");
    private static final Set<String> SPONSOR_HTML_EXT = Set.of(".html", ".htm");

    public static final String CATEGORY_SITE = "site";
    public static final String CATEGORY_SPONSOR = "sponsor";

    @Autowired
    private RuntimeConfigService runtimeConfigService;

    public Path storageRoot() {
        String dir = runtimeConfigService.getString("portal.local-assets.storage-dir", "data/portal-assets");
        if (!StringUtils.hasText(dir)) {
            dir = "data/portal-assets";
        }
        return Paths.get(dir.trim()).toAbsolutePath().normalize();
    }

    /**
     * 保存上传文件并返回站内相对 URL（以 /api 开头），供写入 system_config。
     */
    public String saveUploadedFile(MultipartFile file, String category) {
        if (!CATEGORY_SITE.equals(category) && !CATEGORY_SPONSOR.equals(category)) {
            throw new BusinessException("非法资源类别");
        }
        if (file == null || file.isEmpty()) {
            throw new BusinessException("文件为空");
        }
        String ext = extractExtension(file.getOriginalFilename());
        validateExtension(category, ext);
        String storedName = UUID.randomUUID().toString().toLowerCase(Locale.ROOT) + ext;
        Path dir = storageRoot().resolve(category);
        try {
            Files.createDirectories(dir);
            Path target = dir.resolve(storedName);
            try (InputStream in = file.getInputStream()) {
                Files.copy(in, target, StandardCopyOption.REPLACE_EXISTING);
            }
        } catch (IOException e) {
            throw new BusinessException("写入本地文件失败: " + e.getMessage());
        }
        return "/api/portal/public-asset/" + category + "/" + storedName;
    }

    /**
     * 校验并解析可读文件路径（防止路径穿越）。
     */
    public Path resolveExistingFile(String category, String filename) {
        if (!CATEGORY_SITE.equals(category) && !CATEGORY_SPONSOR.equals(category)) {
            throw new ResourceNotFoundException("资源不存在");
        }
        if (!StringUtils.hasText(filename) || !SAFE_STORED_NAME.matcher(filename).matches()) {
            throw new ResourceNotFoundException("资源不存在");
        }
        Path root = storageRoot();
        Path filePath = root.resolve(category).resolve(filename).normalize();
        if (!filePath.startsWith(root)) {
            throw new ResourceNotFoundException("资源不存在");
        }
        if (!Files.isRegularFile(filePath)) {
            throw new ResourceNotFoundException("资源不存在");
        }
        return filePath;
    }

    private static String extractExtension(String originalFilename) {
        if (!StringUtils.hasText(originalFilename) || !originalFilename.contains(".")) {
            return "";
        }
        return originalFilename.substring(originalFilename.lastIndexOf('.')).toLowerCase(Locale.ROOT);
    }

    private static void validateExtension(String category, String ext) {
        if (!StringUtils.hasText(ext)) {
            throw new BusinessException("缺少文件扩展名");
        }
        if (CATEGORY_SITE.equals(category)) {
            if (!SITE_IMAGE_EXT.contains(ext)) {
                throw new BusinessException("不支持的图片格式");
            }
        } else {
            if (!SPONSOR_HTML_EXT.contains(ext)) {
                throw new BusinessException("仅支持 .html / .htm");
            }
        }
    }
}
