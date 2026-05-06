package com.starrynight.starrynight.system.notification.service;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;

import java.io.IOException;
import java.util.Map;

/**
 * 按模板键发送 HTML 邮件（仅使用已上传的 {@code {key}.html}）。活动通知、营销推送等业务可注入本服务调用。
 */
@Service
public class MailTemplateDispatchService {

    private static final Logger log = LoggerFactory.getLogger(MailTemplateDispatchService.class);

    @Autowired
    private MailTemplateComposer mailTemplateComposer;
    @Autowired
    private MailSendService mailSendService;

    /**
     * @param templateKey {@link com.starrynight.starrynight.system.notification.MailTemplateKind} 中的 key
     * @param variables   占位符替换，如 code、minutes、title、link 等
     */
    public void send(String to, String templateKey, Map<String, String> variables) {
        if (!mailSendService.canSend()) {
            throw new BusinessException("邮件发送未启用或未配置 SMTP");
        }
        if (!StringUtils.hasText(to)) {
            throw new BusinessException("收件人为空");
        }
        String recipient = to.trim();
        if (!mailTemplateComposer.htmlFileExists(templateKey)) {
            throw new BusinessException("未上传邮件 HTML 模板（请在后台上传 " + templateKey + ".html）");
        }
        try {
            String html = mailTemplateComposer.renderHtml(templateKey, variables);
            mailSendService.sendHtmlMail(recipient, mailTemplateComposer.renderSubject(templateKey, variables), html);
            log.debug("Templated HTML mail sent key={} to={}", templateKey, recipient);
        } catch (IOException e) {
            log.warn("Read HTML template {} failed: {}", templateKey, e.getMessage());
            throw new BusinessException("读取 HTML 模板失败: " + e.getMessage());
        }
    }
}
