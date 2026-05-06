package com.starrynight.starrynight.system.notification.service;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import jakarta.mail.MessagingException;
import jakarta.mail.internet.InternetAddress;
import jakarta.mail.internet.MimeMessage;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.mail.javamail.JavaMailSenderImpl;
import org.springframework.mail.javamail.MimeMessageHelper;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;

import java.io.UnsupportedEncodingException;
import java.nio.charset.StandardCharsets;
import java.util.Properties;

/**
 * 基于 {@code system_config} 中的 {@code mail.enabled}、{@code spring.mail.*} 发送邮件。
 * 显示名见 {@code mail.from.personal}（可选，UTF-8）。
 */
@Service
public class MailSendService {

    private static final Logger log = LoggerFactory.getLogger(MailSendService.class);

    private final RuntimeConfigService runtime;

    public MailSendService(RuntimeConfigService runtime) {
        this.runtime = runtime;
    }

    public boolean canSend() {
        if (!runtime.getBoolean("mail.enabled", false)) {
            return false;
        }
        String host = runtime.getString("spring.mail.host", "");
        return StringUtils.hasText(host);
    }

    /**
     * 发送纯文本邮件。
     */
    public void sendTextMail(String to, String subject, String text) {
        if (!canSend()) {
            throw new BusinessException("邮件发送未启用或未配置 SMTP 主机");
        }
        if (!StringUtils.hasText(to)) {
            throw new BusinessException("收件人为空");
        }
        resolveFromEmailOrThrow();

        JavaMailSenderImpl sender = buildSender();
        try {
            MimeMessage message = sender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, false, StandardCharsets.UTF_8.name());
            applyFrom(helper);
            helper.setTo(to.trim());
            helper.setSubject(subject != null ? subject : "");
            helper.setText(text != null ? text : "", false);
            sender.send(message);
            log.debug("Mail sent to {}", to);
        } catch (Exception e) {
            log.warn("Mail send failed: {}", e.toString());
            throw new BusinessException("邮件发送失败: " + e.getMessage());
        }
    }

    /**
     * 发送 HTML 邮件。
     */
    public void sendHtmlMail(String to, String subject, String html) {
        if (!canSend()) {
            throw new BusinessException("邮件发送未启用或未配置 SMTP 主机");
        }
        if (!StringUtils.hasText(to)) {
            throw new BusinessException("收件人为空");
        }
        resolveFromEmailOrThrow();

        JavaMailSenderImpl sender = buildSender();
        try {
            MimeMessage message = sender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, false, StandardCharsets.UTF_8.name());
            applyFrom(helper);
            helper.setTo(to.trim());
            helper.setSubject(subject != null ? subject : "");
            helper.setText(html != null ? html : "", true);
            sender.send(message);
            log.debug("HTML mail sent to {}", to);
        } catch (Exception e) {
            log.warn("HTML mail send failed: {}", e.toString());
            throw new BusinessException("邮件发送失败: " + e.getMessage());
        }
    }

    private JavaMailSenderImpl buildSender() {
        JavaMailSenderImpl mailSender = new JavaMailSenderImpl();
        mailSender.setHost(runtime.getString("spring.mail.host", "localhost").trim());
        mailSender.setPort(runtime.getInt("spring.mail.port", 587));
        String user = runtime.getString("spring.mail.username", "");
        if (StringUtils.hasText(user)) {
            mailSender.setUsername(user.trim());
        }
        String password = runtime.getProperty("spring.mail.password");
        if (password != null) {
            mailSender.setPassword(password);
        }

        Properties props = mailSender.getJavaMailProperties();
        props.put("mail.transport.protocol", "smtp");
        props.put("mail.smtp.auth", "true");

        int port = mailSender.getPort();
        boolean sslExplicit = runtime.getBoolean("mail.smtp.ssl", false);
        if (sslExplicit || port == 465) {
            props.put("mail.smtp.ssl.enable", "true");
            props.put("mail.smtp.socketFactory.port", String.valueOf(port));
            props.put("mail.smtp.socketFactory.class", "javax.net.ssl.SSLSocketFactory");
            props.put("mail.smtp.starttls.enable", "false");
        } else {
            props.put("mail.smtp.starttls.enable", runtime.getBoolean("mail.smtp.starttls", true) ? "true" : "false");
            props.put("mail.smtp.ssl.enable", "false");
        }
        props.put("mail.debug", "false");
        return mailSender;
    }

    private void resolveFromEmailOrThrow() {
        String from = runtime.getString("mail.from", "");
        if (!StringUtils.hasText(from)) {
            from = runtime.getString("spring.mail.username", "");
        }
        if (!StringUtils.hasText(from)) {
            throw new BusinessException("未配置发件人（mail.from 或 spring.mail.username）");
        }
    }

    private void applyFrom(MimeMessageHelper helper) throws MessagingException {
        String from = runtime.getString("mail.from", "");
        if (!StringUtils.hasText(from)) {
            from = runtime.getString("spring.mail.username", "");
        }
        try {
            InternetAddress addr;
            String personal = runtime.getString("mail.from.personal", "");
            if (StringUtils.hasText(personal)) {
                addr = new InternetAddress(from.trim(), personal.trim(), StandardCharsets.UTF_8.name());
            } else {
                addr = new InternetAddress(from.trim());
            }
            helper.setFrom(addr);
        } catch (UnsupportedEncodingException e) {
            throw new MessagingException("发件人显示名编码失败", e);
        }
    }
}
