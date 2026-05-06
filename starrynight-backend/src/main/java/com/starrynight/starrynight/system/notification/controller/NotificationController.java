package com.starrynight.starrynight.system.notification.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.notification.entity.NotificationMessage;
import com.starrynight.starrynight.system.notification.entity.NotificationSetting;
import com.starrynight.starrynight.system.notification.service.NotificationService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@Slf4j
@RestController
@RequestMapping("/api/notifications")
@RequiredArgsConstructor
public class NotificationController {

    private final NotificationService notificationService;

    @GetMapping
    public ResponseVO<List<NotificationMessage>> getNotifications(
            @RequestParam Long userId,
            @RequestParam(defaultValue = "20") int limit) {
        List<NotificationMessage> notifications = notificationService.getUserNotifications(userId, limit);
        return ResponseVO.success(notifications);
    }

    @GetMapping("/type/{type}")
    public ResponseVO<List<NotificationMessage>> getNotificationsByType(
            @RequestParam Long userId,
            @PathVariable String type) {
        List<NotificationMessage> notifications = notificationService.getUserNotificationsByType(userId, type);
        return ResponseVO.success(notifications);
    }

    @GetMapping("/unread-count")
    public ResponseVO<Integer> getUnreadCount(@RequestParam Long userId) {
        Integer count = notificationService.getUnreadCount(userId);
        return ResponseVO.success(count);
    }

    @PostMapping("/{id}/read")
    public ResponseVO<Void> markAsRead(@PathVariable Long id) {
        notificationService.markAsRead(id);
        return ResponseVO.success(null);
    }

    @PostMapping("/read-all")
    public ResponseVO<Void> markAllAsRead(@RequestParam Long userId) {
        notificationService.markAllAsRead(userId);
        return ResponseVO.success(null);
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> deleteNotification(@PathVariable Long id) {
        notificationService.deleteNotification(id);
        return ResponseVO.success(null);
    }

    @GetMapping("/settings")
    public ResponseVO<Map<String, NotificationSetting>> getUserSettings(@RequestParam Long userId) {
        Map<String, NotificationSetting> settings = notificationService.getUserAllSettings(userId);
        return ResponseVO.success(settings);
    }

    @PutMapping("/settings")
    public ResponseVO<Void> updateSetting(
            @RequestParam Long userId,
            @RequestParam String type,
            @RequestParam(required = false) Boolean pushEnabled,
            @RequestParam(required = false) Boolean emailEnabled) {
        notificationService.updateUserSetting(userId, type, pushEnabled, emailEnabled);
        return ResponseVO.success(null);
    }
}
