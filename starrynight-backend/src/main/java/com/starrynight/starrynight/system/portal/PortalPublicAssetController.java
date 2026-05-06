package com.starrynight.starrynight.system.portal;

import jakarta.servlet.http.HttpServletResponse;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.MediaType;
import org.springframework.util.StringUtils;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.io.IOException;
import java.io.InputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.Locale;

/**
 * 本地站点素材匿名读取（未配置 OSS 时上传产物）。
 */
@RestController
@RequestMapping("/api/portal")
public class PortalPublicAssetController {

    @Autowired
    private PortalLocalAssetService portalLocalAssetService;

    @GetMapping("/public-asset/{category}/{filename}")
    public void readAsset(
            @PathVariable String category,
            @PathVariable String filename,
            HttpServletResponse response) throws IOException {
        Path path = portalLocalAssetService.resolveExistingFile(category, filename);
        String ct = probeContentType(path, filename);
        response.setContentType(ct);
        response.setHeader("Cache-Control", "public, max-age=3600");
        try (InputStream in = Files.newInputStream(path)) {
            in.transferTo(response.getOutputStream());
        }
    }

    private static String probeContentType(Path path, String filename) {
        try {
            String probed = Files.probeContentType(path);
            if (StringUtils.hasText(probed)) {
                return probed;
            }
        } catch (IOException ignored) {
            /* fallback */
        }
        String lower = filename.toLowerCase(Locale.ROOT);
        if (lower.endsWith(".html") || lower.endsWith(".htm")) {
            return MediaType.TEXT_HTML_VALUE + ";charset=UTF-8";
        }
        if (lower.endsWith(".svg")) {
            return "image/svg+xml";
        }
        if (lower.endsWith(".png")) {
            return "image/png";
        }
        if (lower.endsWith(".jpg") || lower.endsWith(".jpeg")) {
            return "image/jpeg";
        }
        if (lower.endsWith(".gif")) {
            return "image/gif";
        }
        if (lower.endsWith(".webp")) {
            return "image/webp";
        }
        if (lower.endsWith(".ico")) {
            return "image/x-icon";
        }
        return MediaType.APPLICATION_OCTET_STREAM_VALUE;
    }
}
