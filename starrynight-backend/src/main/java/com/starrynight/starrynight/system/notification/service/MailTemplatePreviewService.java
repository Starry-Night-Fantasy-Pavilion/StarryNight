package com.starrynight.starrynight.system.notification.service;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.notification.MailTemplateKind;
import com.starrynight.starrynight.system.notification.dto.MailTemplatePreviewDTO;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.util.LinkedHashMap;
import java.util.Map;

@Service
public class MailTemplatePreviewService {

    @Autowired
    private MailTemplateComposer mailTemplateComposer;
    @Autowired
    private MailTemplateFileService mailTemplateFileService;
    @Autowired
    private MailTemplatePreviewSampleProvider mailTemplatePreviewSampleProvider;

    public MailTemplatePreviewDTO preview(String templateKey, Map<String, String> variableOverrides) {
        MailTemplateKind kind = MailTemplateKind.fromKey(templateKey)
                .orElseThrow(() -> new BusinessException("未知邮件模板类型"));
        Map<String, String> vars = new LinkedHashMap<>(mailTemplatePreviewSampleProvider.previewSamples(kind));
        Map<String, String> overrides = variableOverrides != null ? new LinkedHashMap<>(variableOverrides) : new LinkedHashMap<>();
        boolean useInlineSubject = overrides.containsKey("_subjectPreview");
        String inlineSubjectTemplate = overrides.remove("_subjectPreview");
        vars.putAll(overrides);
        String subject = useInlineSubject
                ? mailTemplateComposer.renderSubjectLine(
                        inlineSubjectTemplate != null ? inlineSubjectTemplate : "", vars)
                : mailTemplateComposer.renderSubject(templateKey, vars);
        if (!mailTemplateFileService.htmlExists(templateKey)) {
            throw new BusinessException("请先上传 HTML 模板后再预览");
        }
        String html;
        try {
            html = mailTemplateComposer.renderHtml(templateKey, vars);
        } catch (IOException e) {
            throw new BusinessException("读取 HTML 模板失败: " + e.getMessage());
        }
        html = ensurePreviewDocument(html);
        return new MailTemplatePreviewDTO(subject, html, "HTML_FILE");
    }

    private static String ensurePreviewDocument(String html) {
        if (html == null) {
            return wrapFragment("");
        }
        String t = html.stripLeading();
        String lower = t.toLowerCase();
        if (lower.startsWith("<!doctype") || lower.startsWith("<html")) {
            return html;
        }
        return wrapFragment(html);
    }

    private static String wrapFragment(String fragment) {
        return "<!DOCTYPE html><html lang=\"zh-CN\"><head><meta charset=\"UTF-8\">"
                + "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>预览</title>"
                + "<style>"
                + "body{margin:0;background:#f4f4f5;font-family:system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;}"
                + ".sn-mail-preview__shell{max-width:640px;margin:0 auto;padding:28px 16px;}"
                + ".sn-mail-preview__card{background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(15,23,42,.08);"
                + "overflow:hidden;padding:28px;color:#18181b;font-size:15px;line-height:1.65;}"
                + "</style></head><body>"
                + "<div class=\"sn-mail-preview__shell\"><div class=\"sn-mail-preview__card\">"
                + fragment
                + "</div></div></body></html>";
    }

}
