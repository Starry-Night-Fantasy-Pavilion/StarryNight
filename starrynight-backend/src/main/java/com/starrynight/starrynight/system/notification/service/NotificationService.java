package com.starrynight.starrynight.system.notification.service;

import com.alibaba.fastjson2.JSON;
import com.alibaba.fastjson2.JSONObject;
import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.system.notification.entity.NotificationMessage;
import com.starrynight.starrynight.system.notification.entity.NotificationSetting;
import com.starrynight.starrynight.system.notification.entity.NotificationTemplate;
import com.starrynight.starrynight.system.notification.mapper.NotificationMessageMapper;
import com.starrynight.starrynight.system.notification.mapper.NotificationSettingMapper;
import com.starrynight.starrynight.system.notification.mapper.NotificationTemplateMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.scheduling.annotation.Async;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

@Slf4j
@Service
@RequiredArgsConstructor
public class NotificationService {

    private final NotificationMessageMapper messageMapper;
    private final NotificationTemplateMapper templateMapper;
    private final NotificationSettingMapper settingMapper;

    private static final Pattern VARIABLE_PATTERN = Pattern.compile("\\{\\{(\\w+)}}");

    public void sendToUser(Long userId, String type, String title, String content, String linkUrl, String linkParams, String priority) {
        NotificationMessage message = new NotificationMessage();
        message.setUserId(userId);
        message.setNotificationType(type);
        message.setTitle(title);
        message.setContent(content);
        message.setLinkUrl(linkUrl);
        message.setLinkParams(linkParams);
        message.setPriority(priority != null ? priority : "NORMAL");
        message.setIsRead(0);
        message.setCreateTime(LocalDateTime.now());

        messageMapper.insert(message);
        log.info("Notification sent to user {}: type={}, title={}", userId, type, title);
    }

    public void sendByTemplate(Long userId, String templateCode, Map<String, Object> variables) {
        NotificationTemplate template = templateMapper.selectOne(new LambdaQueryWrapper<NotificationTemplate>()
                .eq(NotificationTemplate::getTemplateCode, templateCode)
                .eq(NotificationTemplate::getEnabled, 1));

        if (template == null) {
            log.warn("Template not found or disabled: {}", templateCode);
            return;
        }

        String title = renderTemplate(template.getTitleTemplate(), variables);
        String content = renderTemplate(template.getContentTemplate(), variables);

        sendToUser(userId, template.getNotificationType(), title, content, null, null, "NORMAL");
    }

    @Async
    public void sendByTemplateAsync(Long userId, String templateCode, Map<String, Object> variables) {
        try {
            sendByTemplate(userId, templateCode, variables);
        } catch (Exception e) {
            log.error("Failed to send async notification: userId={}, template={}", userId, templateCode, e);
        }
    }

    public List<NotificationMessage> getUserNotifications(Long userId, int limit) {
        return messageMapper.selectRecentByUserId(userId, limit, LocalDateTime.now());
    }

    public List<NotificationMessage> getUserNotificationsByType(Long userId, String type) {
        return messageMapper.selectByType(userId, type);
    }

    public Integer getUnreadCount(Long userId) {
        return messageMapper.countUnread(userId, LocalDateTime.now());
    }

    @Transactional(rollbackFor = Exception.class)
    public void markAsRead(Long messageId) {
        messageMapper.markAsRead(messageId, LocalDateTime.now());
    }

    @Transactional(rollbackFor = Exception.class)
    public void markAllAsRead(Long userId) {
        messageMapper.markAllAsRead(userId, LocalDateTime.now());
    }

    @Transactional(rollbackFor = Exception.class)
    public void deleteNotification(Long messageId) {
        messageMapper.deleteById(messageId);
    }

    public NotificationSetting getUserSetting(Long userId, String type) {
        return settingMapper.selectOne(new LambdaQueryWrapper<NotificationSetting>()
                .eq(NotificationSetting::getUserId, userId)
                .eq(NotificationSetting::getNotificationType, type));
    }

    public Map<String, NotificationSetting> getUserAllSettings(Long userId) {
        List<NotificationSetting> settings = settingMapper.selectList(new LambdaQueryWrapper<NotificationSetting>()
                .eq(NotificationSetting::getUserId, userId));

        Map<String, NotificationSetting> result = new HashMap<>();
        for (NotificationSetting setting : settings) {
            result.put(setting.getNotificationType(), setting);
        }
        return result;
    }

    @Transactional(rollbackFor = Exception.class)
    public void updateUserSetting(Long userId, String type, Boolean pushEnabled, Boolean emailEnabled) {
        NotificationSetting setting = settingMapper.selectOne(new LambdaQueryWrapper<NotificationSetting>()
                .eq(NotificationSetting::getUserId, userId)
                .eq(NotificationSetting::getNotificationType, type));

        if (setting == null) {
            setting = new NotificationSetting();
            setting.setUserId(userId);
            setting.setNotificationType(type);
            setting.setPushEnabled(pushEnabled ? 1 : 0);
            setting.setEmailEnabled(emailEnabled ? 1 : 0);
            setting.setCreateTime(LocalDateTime.now());
            setting.setUpdateTime(LocalDateTime.now());
            settingMapper.insert(setting);
        } else {
            setting.setPushEnabled(pushEnabled ? 1 : 0);
            setting.setEmailEnabled(emailEnabled ? 1 : 0);
            setting.setUpdateTime(LocalDateTime.now());
            settingMapper.updateById(setting);
        }
    }

    public boolean isPushEnabled(Long userId, String type) {
        NotificationSetting setting = getUserSetting(userId, type);
        if (setting == null) {
            return true;
        }
        return setting.getPushEnabled() == 1;
    }

    public void sendSystemNotification(Long userId, String title, String content) {
        sendToUser(userId, "SYSTEM", title, content, null, null, "NORMAL");
    }

    public void sendUrgentNotification(Long userId, String title, String content) {
        sendToUser(userId, "SYSTEM", title, content, null, null, "URGENT");
    }

    public void sendAccountNotification(Long userId, String title, String content) {
        sendToUser(userId, "ACCOUNT", title, content, null, null, "NORMAL");
    }

    public void sendOrderNotification(Long userId, String title, String content) {
        sendToUser(userId, "ORDER", title, content, null, null, "NORMAL");
    }

    public void sendActivityNotification(Long userId, String title, String content) {
        sendToUser(userId, "ACTIVITY", title, content, null, null, "NORMAL");
    }

    private String renderTemplate(String template, Map<String, Object> variables) {
        if (template == null || template.isEmpty()) {
            return template;
        }

        String result = template;
        Matcher matcher = VARIABLE_PATTERN.matcher(template);

        while (matcher.find()) {
            String variableName = matcher.group(1);
            Object value = variables.get(variableName);
            if (value != null) {
                String replacement = value.toString();
                if (value instanceof LocalDateTime) {
                    replacement = ((LocalDateTime) value).toString();
                }
                result = result.replace("{{" + variableName + "}}", replacement);
            }
        }

        return result;
    }

    public void cleanupExpiredNotifications() {
        List<NotificationMessage> expired = messageMapper.selectList(
                new LambdaQueryWrapper<NotificationMessage>()
                        .isNotNull(NotificationMessage::getExpireTime)
                        .lt(NotificationMessage::getExpireTime, LocalDateTime.now())
        );

        for (NotificationMessage msg : expired) {
            messageMapper.deleteById(msg.getId());
        }

        log.info("Cleaned up {} expired notifications", expired.size());
    }
}
