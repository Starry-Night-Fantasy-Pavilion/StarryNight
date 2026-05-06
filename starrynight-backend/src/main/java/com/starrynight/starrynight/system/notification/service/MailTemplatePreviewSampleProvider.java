package com.starrynight.starrynight.system.notification.service;

import com.starrynight.starrynight.system.notification.MailTemplateKind;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;
import org.springframework.util.StringUtils;

import java.time.Year;
import java.util.LinkedHashMap;
import java.util.Map;

/**
 * 管理端预览用的示例占位符：在枚举默认值之上合并站点品牌名、公网根 URL 等运行时配置。
 */
@Component
public class MailTemplatePreviewSampleProvider {

    private static final String KEY_SITE_BRAND = "site.brand.name";
    private static final String KEY_PUBLIC_BASE = "auth.oauth.public-base-url";

    @Autowired
    private RuntimeConfigService runtimeConfigService;

    public Map<String, String> previewSamples(MailTemplateKind kind) {
        Map<String, String> m = new LinkedHashMap<>(kind.previewSampleVariables());
        String site = runtimeConfigService.getString(KEY_SITE_BRAND, "星夜阁");
        m.put("siteName", site);
        m.put("currentYear", String.valueOf(Year.now().getValue()));
        switch (kind) {
            case RESET_PASSWORD, VERIFICATION_CODE -> m.putIfAbsent("username", "书友");
            default -> {
            }
        }
        String base = trimToNull(runtimeConfigService.getProperty(KEY_PUBLIC_BASE));
        if (base != null) {
            String root = base.endsWith("/") ? base.substring(0, base.length() - 1) : base;
            switch (kind) {
                case ACTIVITY -> m.put("link", root + "/activity/preview");
                case MARKETING -> {
                    m.put("link", root + "/promo/preview");
                    m.put("unsubscribe", root + "/settings/notifications");
                }
                default -> {
                }
            }
        }
        return m;
    }

    private static String trimToNull(String raw) {
        if (!StringUtils.hasText(raw)) {
            return null;
        }
        String t = raw.trim();
        return t.isEmpty() ? null : t;
    }
}
