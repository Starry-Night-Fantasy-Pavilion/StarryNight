package com.starrynight.starrynight.system.notification;

import java.util.Arrays;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Optional;

/**
 * 内置邮件模板类型（磁盘文件名 = {@code {key}.html}，标题配置键 = {@code mail.template.{key}.subject}）。
 */
public enum MailTemplateKind {

    /** 找回密码（已实现发信） */
    RESET_PASSWORD(
            "reset-password",
            "找回密码验证码",
            "VERIFY",
            "{code} {minutes} {username}（siteName、currentYear 系统自动填充）",
            "忘记密码时发送到用户邮箱的验证码邮件。"
    ),
    /** 通用验证码（可供注册、绑定邮箱等后续业务复用） */
    VERIFICATION_CODE(
            "verification-code",
            "通用验证码",
            "VERIFY",
            "{code} {minutes} {purpose} {username}（siteName、currentYear 系统自动填充）",
            "通用验证码邮件模板；业务调用时可传入 purpose 说明场景。"
    ),
    /** 活动 / 站内通知 */
    ACTIVITY(
            "activity",
            "活动通知",
            "CAMPAIGN",
            "{title} {summary} {link}（siteName、currentYear 系统自动填充）",
            "活动、公告类邮件；由运营活动或站内通知发送时使用。"
    ),
    /** 营销推广 */
    MARKETING(
            "marketing",
            "营销推广",
            "CAMPAIGN",
            "{title} {content} {link} {unsubscribe}（siteName、currentYear 系统自动填充）",
            "营销类邮件；发送须遵守法规与用户订阅偏好。"
    );

    private final String key;
    private final String title;
    /** VERIFY=验证码/账号；CAMPAIGN=活动与营销 */
    private final String category;
    private final String placeholderHint;
    private final String description;

    MailTemplateKind(String key, String title, String category, String placeholderHint, String description) {
        this.key = key;
        this.title = title;
        this.category = category;
        this.placeholderHint = placeholderHint;
        this.description = description;
    }

    public String getKey() {
        return key;
    }

    public String getTitle() {
        return title;
    }

    public String getCategory() {
        return category;
    }

    public String getPlaceholderHint() {
        return placeholderHint;
    }

    public String getDescription() {
        return description;
    }

    /**
     * 管理端预览时用于替换占位符的示例值（可与实际发信时的变量合并/覆盖）。
     */
    public Map<String, String> previewSampleVariables() {
        Map<String, String> m = new LinkedHashMap<>();
        switch (this) {
            case RESET_PASSWORD -> {
                m.put("code", "123456");
                m.put("minutes", "5");
                m.put("username", "书友");
            }
            case VERIFICATION_CODE -> {
                m.put("code", "123456");
                m.put("minutes", "5");
                m.put("purpose", "邮箱验证");
                m.put("username", "书友");
            }
            case ACTIVITY -> {
                m.put("title", "春季创作大赛");
                m.put("summary", "参与即有机会获得会员时长与周边礼品。");
                m.put("link", "https://example.com/activity/spring");
                m.put("siteName", "星夜阁");
            }
            case MARKETING -> {
                m.put("title", "本周畅销书单");
                m.put("content", "精选好书限时折扣，点击了解更多。");
                m.put("link", "https://example.com/promo");
                m.put("siteName", "星夜阁");
                m.put("unsubscribe", "https://example.com/unsubscribe");
            }
        }
        return m;
    }

    public static Optional<MailTemplateKind> fromKey(String raw) {
        if (raw == null || raw.isBlank()) {
            return Optional.empty();
        }
        String k = raw.trim();
        return Arrays.stream(values()).filter(v -> v.key.equals(k)).findFirst();
    }

    public static MailTemplateKind require(String raw) {
        return fromKey(raw).orElseThrow(() -> new IllegalArgumentException("未知邮件模板: " + raw));
    }
}
