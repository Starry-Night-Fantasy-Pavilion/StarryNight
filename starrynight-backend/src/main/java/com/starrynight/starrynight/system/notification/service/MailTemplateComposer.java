package com.starrynight.starrynight.system.notification.service;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.notification.MailTemplateKind;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.time.Year;
import java.util.LinkedHashMap;
import java.util.Map;

/**
 * 按模板键读取磁盘 HTML 文件，并对占位符做替换；邮件标题来自 {@code mail.template.{key}.subject}。
 */
@Service
public class MailTemplateComposer {

    @Autowired
    private MailTemplateFileService mailTemplateFileService;
    @Autowired
    private RuntimeConfigService runtimeConfigService;

    public boolean htmlFileExists(String templateKey) {
        return mailTemplateFileService.htmlExists(templateKey);
    }

    public String renderHtml(String templateKey, Map<String, String> variables) throws IOException {
        String raw = Files.readString(mailTemplateFileService.resolveHtmlPath(templateKey), StandardCharsets.UTF_8);
        return substitute(raw, mergeVariables(variables));
    }

    public String subject(String templateKey) {
        MailTemplateKind kind = MailTemplateKind.fromKey(templateKey)
                .orElseThrow(() -> new BusinessException("未知邮件模板"));
        String def = defaultSubject(kind);
        String s = runtimeConfigService.getString("mail.template." + templateKey + ".subject", def);
        return s != null ? s.trim() : def;
    }

    /** 标题中的占位符一并替换（若有）。 */
    public String renderSubject(String templateKey, Map<String, String> variables) {
        return substitute(subject(templateKey), mergeVariables(variables));
    }

    /**
     * 将任意标题模板字符串做与正文相同的占位符合并/替换（用于管理端「未保存」标题预览）。
     */
    public String renderSubjectLine(String subjectTemplate, Map<String, String> variables) {
        return substitute(subjectTemplate != null ? subjectTemplate : "", mergeVariables(variables));
    }

    /**
     * 合并通用占位符：调用方传入的变量优先覆盖。
     * {@code siteName} 默认 {@code site.brand.name}；{@code currentYear} 为当前公历年。
     */
    private Map<String, String> mergeVariables(Map<String, String> variables) {
        Map<String, String> m = new LinkedHashMap<>();
        m.put("currentYear", String.valueOf(Year.now().getValue()));
        m.put("siteName", runtimeConfigService.getString("site.brand.name", "星夜阁"));
        if (variables != null) {
            m.putAll(variables);
        }
        return m;
    }

    private static String defaultSubject(MailTemplateKind kind) {
        return switch (kind) {
            case RESET_PASSWORD -> "密码重置验证码";
            case VERIFICATION_CODE -> "验证码";
            case ACTIVITY -> "活动通知";
            case MARKETING -> "邮件通知";
        };
    }

    private static String substitute(String template, Map<String, String> variables) {
        if (template == null) {
            return "";
        }
        String s = template;
        if (variables != null) {
            for (Map.Entry<String, String> e : variables.entrySet()) {
                String k = e.getKey();
                String v = e.getValue() != null ? e.getValue() : "";
                s = s.replace("{" + k + "}", v);
            }
        }
        return s;
    }
}
