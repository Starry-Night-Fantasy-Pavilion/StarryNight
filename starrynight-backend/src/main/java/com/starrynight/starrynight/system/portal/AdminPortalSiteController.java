package com.starrynight.starrynight.system.portal;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.storage.StorageService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.MediaType;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.util.StringUtils;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import java.util.Map;

@RestController
@RequestMapping("/api/admin/portal/site")
@PreAuthorize("hasRole('ADMIN')")
public class AdminPortalSiteController {

    @Autowired
    private StorageService storageService;

    @Autowired
    private PortalLocalAssetService portalLocalAssetService;

    /**
     * 上传站点 Logo：已配置 MinIO/OSS 时写入桶并返回外链；否则写入服务器本地目录，返回站内路径（见 {@link PortalPublicAssetController}）。
     */
    @PostMapping(value = "/logo", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseVO<Map<String, String>> uploadLogo(@RequestParam("file") MultipartFile file) {
        if (file == null || file.isEmpty()) {
            throw new BusinessException("请选择图片文件");
        }
        String contentType = file.getContentType();
        if (contentType == null || !contentType.startsWith("image/")) {
            throw new BusinessException("仅支持图片格式");
        }
        String url = storageService.isObjectStorageConfigured()
                ? storageService.uploadFile(file, "portal/site/")
                : portalLocalAssetService.saveUploadedFile(file, PortalLocalAssetService.CATEGORY_SITE);
        return ResponseVO.success(Map.of("url", url));
    }

    /**
     * 上传赞助/鸣谢 HTML：对象存储可用时同上；否则保存本地并由 {@code /api/portal/public-asset/sponsor/…} 提供访问。
     */
    @PostMapping(value = "/sponsor-html", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseVO<Map<String, String>> uploadSponsorHtml(@RequestParam("file") MultipartFile file) {
        if (file == null || file.isEmpty()) {
            throw new BusinessException("请选择 HTML 文件");
        }
        if (!isHtmlLike(file)) {
            throw new BusinessException("仅支持 .html / .htm 或 text/html");
        }
        String url = storageService.isObjectStorageConfigured()
                ? storageService.uploadFile(file, "portal/sponsor/")
                : portalLocalAssetService.saveUploadedFile(file, PortalLocalAssetService.CATEGORY_SPONSOR);
        return ResponseVO.success(Map.of("url", url));
    }

    private static boolean isHtmlLike(MultipartFile file) {
        String ct = file.getContentType();
        if (StringUtils.hasText(ct)) {
            String lower = ct.toLowerCase();
            if (lower.contains("html")) {
                return true;
            }
        }
        String name = file.getOriginalFilename();
        if (!StringUtils.hasText(name)) {
            return false;
        }
        String lower = name.toLowerCase();
        return lower.endsWith(".html") || lower.endsWith(".htm");
    }
}
