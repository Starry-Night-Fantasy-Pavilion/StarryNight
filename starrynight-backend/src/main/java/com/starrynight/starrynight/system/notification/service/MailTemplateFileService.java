package com.starrynight.starrynight.system.notification.service;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.notification.MailTemplateKind;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.attribute.FileTime;

/**
 * 邮件 HTML 模板文件：目录由 {@code mail.template.storage-dir} 配置，单模板文件名为 {@code {key}.html}。
 */
@Service
public class MailTemplateFileService {

    @Autowired
    private RuntimeConfigService runtimeConfigService;

    public void assertValidTemplateKey(String templateKey) {
        if (MailTemplateKind.fromKey(templateKey).isEmpty()) {
            throw new BusinessException("未知邮件模板类型: " + templateKey);
        }
    }

    public Path resolveHtmlPath(String templateKey) {
        assertValidTemplateKey(templateKey);
        String dir = runtimeConfigService.getString("mail.template.storage-dir", "data/mail-templates");
        if (!StringUtils.hasText(dir)) {
            dir = "data/mail-templates";
        }
        Path root = Paths.get(dir.trim()).toAbsolutePath().normalize();
        String fileName = templateKey.trim() + ".html";
        if (fileName.contains("..") || fileName.contains("/") || fileName.contains("\\")) {
            throw new BusinessException("非法模板键");
        }
        return root.resolve(fileName);
    }

    public boolean htmlExists(String templateKey) {
        Path p = resolveHtmlPath(templateKey);
        try {
            return Files.isRegularFile(p) && Files.size(p) > 0;
        } catch (IOException e) {
            return false;
        }
    }

    public long htmlSizeBytes(String templateKey) {
        Path p = resolveHtmlPath(templateKey);
        try {
            return Files.isRegularFile(p) ? Files.size(p) : 0L;
        } catch (IOException e) {
            return 0L;
        }
    }

    public Long htmlLastModifiedMillis(String templateKey) {
        Path p = resolveHtmlPath(templateKey);
        try {
            if (!Files.isRegularFile(p)) {
                return null;
            }
            FileTime t = Files.getLastModifiedTime(p);
            return t != null ? t.toMillis() : null;
        } catch (IOException e) {
            return null;
        }
    }

    public void saveHtml(String templateKey, MultipartFile file) throws IOException {
        assertValidTemplateKey(templateKey);
        if (file == null || file.isEmpty()) {
            throw new BusinessException("请上传 HTML 文件");
        }
        String orig = file.getOriginalFilename() != null ? file.getOriginalFilename() : "";
        if (!orig.toLowerCase().endsWith(".html") && !orig.toLowerCase().endsWith(".htm")) {
            throw new BusinessException("仅支持 .html / .htm 文件");
        }
        if (file.getSize() > 512_000) {
            throw new BusinessException("文件过大（单文件不超过 500KB）");
        }
        byte[] bytes = file.getBytes();
        if (bytes.length == 0) {
            throw new BusinessException("文件为空");
        }
        String text = new String(bytes, StandardCharsets.UTF_8);
        if (!StringUtils.hasText(text)) {
            throw new BusinessException("文件内容为空");
        }
        Path target = resolveHtmlPath(templateKey);
        Files.createDirectories(target.getParent());
        Files.writeString(target, text, StandardCharsets.UTF_8);
    }

    public void deleteHtml(String templateKey) {
        assertValidTemplateKey(templateKey);
        try {
            Files.deleteIfExists(resolveHtmlPath(templateKey));
        } catch (IOException e) {
            throw new BusinessException("删除模板文件失败: " + e.getMessage());
        }
    }
}
