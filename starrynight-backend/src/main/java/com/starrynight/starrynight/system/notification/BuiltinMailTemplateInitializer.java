package com.starrynight.starrynight.system.notification;

import com.starrynight.starrynight.system.notification.service.MailTemplateFileService;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.ApplicationArguments;
import org.springframework.boot.ApplicationRunner;
import org.springframework.core.io.ClassPathResource;
import org.springframework.stereotype.Component;

import java.io.IOException;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;

/**
 * 启动时将 classpath 内置 HTML 复制到 {@code mail.template.storage-dir}。
 * <ul>
 *   <li>磁盘修订号（{@code .builtin-template-revision}）小于 {@link #BUILTIN_TEMPLATE_REVISION} 时：覆盖写入全部内置模板（项目升级后默认拿到新版）。</li>
 *   <li>否则：仅在对应 {@code {key}.html} 不存在或为空时写入（保留运营已上传的自定义 HTML）。</li>
 * </ul>
 * 升级内置默认样式时请将 {@link #BUILTIN_TEMPLATE_REVISION} 递增；若站点已深度自定义模板，可先备份 {@code data/mail-templates} 再发布。
 */
@Component
public class BuiltinMailTemplateInitializer implements ApplicationRunner {

    private static final Logger log = LoggerFactory.getLogger(BuiltinMailTemplateInitializer.class);

    private static final String CLASSPATH_DIR = "builtin-mail-templates";

    /**
     * 与仓库 {@code builtin-mail-templates/*.html} 绑定的修订号；递增后下次启动会覆盖磁盘上的内置副本。
     */
    private static final int BUILTIN_TEMPLATE_REVISION = 3;

    private static final String REVISION_FILENAME = ".builtin-template-revision";

    @Autowired
    private MailTemplateFileService mailTemplateFileService;

    @Override
    public void run(ApplicationArguments args) {
        Path storageRoot;
        try {
            storageRoot = mailTemplateFileService.resolveHtmlPath(MailTemplateKind.RESET_PASSWORD.getKey()).getParent();
        } catch (Exception e) {
            log.warn("无法解析邮件模板目录，跳过内置模板初始化: {}", e.getMessage());
            return;
        }

        int diskRev = readDiskRevision(storageRoot);
        boolean upgradeBuiltin = diskRev < BUILTIN_TEMPLATE_REVISION;

        for (MailTemplateKind kind : MailTemplateKind.values()) {
            try {
                if (upgradeBuiltin) {
                    copyFromClasspath(kind.getKey(), true);
                } else {
                    seedIfMissing(kind.getKey());
                }
            } catch (IOException e) {
                log.warn("内置邮件模板初始化失败 key={}: {}", kind.getKey(), e.getMessage());
            }
        }

        if (upgradeBuiltin) {
            writeDiskRevision(storageRoot, BUILTIN_TEMPLATE_REVISION);
            log.info(
                    "内置邮件 HTML 已同步为修订 {}（目录 {}）",
                    BUILTIN_TEMPLATE_REVISION,
                    storageRoot.toAbsolutePath());
        }

        for (MailTemplateKind kind : MailTemplateKind.values()) {
            if (!mailTemplateFileService.htmlExists(kind.getKey())) {
                try {
                    copyFromClasspath(kind.getKey(), true);
                    log.info("已补全缺失的内置邮件模板: {}", kind.getKey());
                } catch (IOException e) {
                    log.warn("补全内置邮件模板失败 key={}: {}", kind.getKey(), e.getMessage());
                }
            }
        }
    }

    private static int readDiskRevision(Path storageRoot) {
        Path marker = storageRoot.resolve(REVISION_FILENAME);
        try {
            if (!Files.isRegularFile(marker)) {
                return 0;
            }
            String line = Files.readString(marker, StandardCharsets.UTF_8).trim();
            return Integer.parseInt(line.split("\\R")[0].trim());
        } catch (Exception e) {
            log.debug("读取内置模板修订号失败，按 0 处理: {}", e.toString());
            return 0;
        }
    }

    private static void writeDiskRevision(Path storageRoot, int revision) {
        try {
            Files.createDirectories(storageRoot);
            Path marker = storageRoot.resolve(REVISION_FILENAME);
            Files.writeString(marker, String.valueOf(revision), StandardCharsets.UTF_8);
        } catch (IOException e) {
            log.warn("写入内置模板修订号失败: {}", e.getMessage());
        }
    }

    private void seedIfMissing(String templateKey) throws IOException {
        Path target = mailTemplateFileService.resolveHtmlPath(templateKey);
        if (Files.isRegularFile(target)) {
            try {
                if (Files.size(target) > 0) {
                    return;
                }
            } catch (IOException e) {
                log.warn("检查模板文件失败 {}: {}", target, e.getMessage());
            }
        }
        copyFromClasspath(templateKey, true);
    }

    private void copyFromClasspath(String templateKey, boolean replaceExisting) throws IOException {
        ClassPathResource resource = new ClassPathResource(CLASSPATH_DIR + "/" + templateKey + ".html");
        if (!resource.exists()) {
            log.warn("classpath 未找到内置模板: {} / {}.html", CLASSPATH_DIR, templateKey);
            return;
        }
        Path target = mailTemplateFileService.resolveHtmlPath(templateKey);
        Files.createDirectories(target.getParent());
        try (InputStream in = resource.getInputStream()) {
            if (replaceExisting) {
                Files.copy(in, target, StandardCopyOption.REPLACE_EXISTING);
            } else {
                if (!Files.exists(target)) {
                    Files.copy(in, target);
                }
            }
        }
        log.debug("已写入内置邮件 HTML 模板: {}", target.toAbsolutePath());
    }
}
