package com.starrynight.starrynight.services.message;

import com.starrynight.starrynight.framework.common.config.RabbitMQConfig;
import com.starrynight.starrynight.framework.common.config.condition.RabbitMqIntegrationEnabledCondition;
import com.starrynight.starrynight.system.notification.service.NotificationService;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.amqp.rabbit.annotation.RabbitListener;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.context.annotation.Conditional;
import org.springframework.stereotype.Service;

import java.util.Map;

@Service
@Conditional(RabbitMqIntegrationEnabledCondition.class)
public class MessageConsumer {

    private static final Logger log = LoggerFactory.getLogger(MessageConsumer.class);

    @Autowired
    private NotificationService notificationService;

    @RabbitListener(queues = RabbitMQConfig.NOTIFICATION_QUEUE, autoStartup = "${spring.rabbitmq.listener.simple.auto-startup:false}")
    public void handleNotification(Map<String, Object> message) {
        try {
            Long userId = ((Number) message.get("userId")).longValue();
            String type = (String) message.get("type");
            String title = (String) message.get("title");
            String msg = (String) message.get("message");

            log.info("Processing notification for user {}: {}", userId, title);
            notificationService.sendToUser(userId, type, title, msg, null, null, "NORMAL");
        } catch (Exception e) {
            log.error("Failed to process notification message", e);
        }
    }

    @RabbitListener(queues = RabbitMQConfig.KNOWLEDGE_CHUNKING_QUEUE, autoStartup = "${spring.rabbitmq.listener.simple.auto-startup:false}")
    public void handleKnowledgeChunking(Map<String, Object> message) {
        try {
            Long knowledgeId = ((Number) message.get("knowledgeId")).longValue();
            String fileUrl = (String) message.get("fileUrl");

            log.info("Processing knowledge chunking: knowledgeId={}", knowledgeId);
        } catch (Exception e) {
            log.error("Failed to process knowledge chunking task", e);
        }
    }

    @RabbitListener(queues = RabbitMQConfig.KNOWLEDGE_VECTORIZING_QUEUE, autoStartup = "${spring.rabbitmq.listener.simple.auto-startup:false}")
    public void handleKnowledgeVectorizing(Map<String, Object> message) {
        try {
            Long knowledgeId = ((Number) message.get("knowledgeId")).longValue();
            String fileUrl = (String) message.get("fileUrl");

            log.info("Processing knowledge vectorizing: knowledgeId={}", knowledgeId);
        } catch (Exception e) {
            log.error("Failed to process knowledge vectorizing task", e);
        }
    }

    @RabbitListener(queues = RabbitMQConfig.AI_CONSISTENCY_CHECK_QUEUE, autoStartup = "${spring.rabbitmq.listener.simple.auto-startup:false}")
    public void handleConsistencyCheck(Map<String, Object> message) {
        try {
            Long novelId = ((Number) message.get("novelId")).longValue();
            Long chapterId = ((Number) message.get("chapterId")).longValue();
            String content = (String) message.get("content");

            log.info("Processing consistency check: novelId={}, chapterId={}", novelId, chapterId);
        } catch (Exception e) {
            log.error("Failed to process consistency check task", e);
        }
    }
}