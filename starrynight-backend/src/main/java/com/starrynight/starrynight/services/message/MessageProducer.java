package com.starrynight.starrynight.services.message;

import com.starrynight.starrynight.framework.common.config.RabbitMQConfig;
import com.starrynight.starrynight.framework.common.config.condition.RabbitMqIntegrationEnabledCondition;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.amqp.rabbit.core.RabbitTemplate;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.util.Map;

@Service
public class MessageProducer {

    private static final Logger log = LoggerFactory.getLogger(MessageProducer.class);

    @Autowired(required = false)
    private RabbitTemplate rabbitTemplate;
    @Autowired
    private RuntimeConfigService runtimeConfigService;

    private boolean rabbitIntegrationOn() {
        return runtimeConfigService.getBoolean(RabbitMqIntegrationEnabledCondition.CONFIG_KEY, true);
    }

    private boolean canSend() {
        if (!rabbitIntegrationOn()) {
            log.debug("RabbitMQ 集成已关闭（{}），跳过投递", RabbitMqIntegrationEnabledCondition.CONFIG_KEY);
            return false;
        }
        if (rabbitTemplate == null) {
            log.warn("RabbitTemplate 未注入，跳过投递");
            return false;
        }
        return true;
    }

    public void sendKnowledgeChunkingTask(Long knowledgeId, String fileUrl) {
        if (!canSend()) {
            return;
        }
        log.info("Sending knowledge chunking task: knowledgeId={}", knowledgeId);
        rabbitTemplate.convertAndSend(
                RabbitMQConfig.KNOWLEDGE_EXCHANGE,
                RabbitMQConfig.KNOWLEDGE_CHUNKING_ROUTING_KEY,
                Map.of("knowledgeId", knowledgeId, "fileUrl", fileUrl)
        );
    }

    public void sendKnowledgeVectorizingTask(Long knowledgeId, String fileUrl) {
        if (!canSend()) {
            return;
        }
        log.info("Sending knowledge vectorizing task: knowledgeId={}", knowledgeId);
        rabbitTemplate.convertAndSend(
                RabbitMQConfig.KNOWLEDGE_EXCHANGE,
                RabbitMQConfig.KNOWLEDGE_VECTORIZING_ROUTING_KEY,
                Map.of("knowledgeId", knowledgeId, "fileUrl", fileUrl)
        );
    }

    public void sendAiGenerationTask(String taskId, Map<String, Object> params) {
        if (!canSend()) {
            return;
        }
        log.info("Sending AI generation task: taskId={}", taskId);
        rabbitTemplate.convertAndSend(
                RabbitMQConfig.AI_EXCHANGE,
                RabbitMQConfig.AI_GENERATION_ROUTING_KEY,
                Map.of("taskId", taskId, "params", params)
        );
    }

    public void sendConsistencyCheckTask(Long novelId, Long chapterId, String content) {
        if (!canSend()) {
            return;
        }
        log.info("Sending consistency check task: novelId={}, chapterId={}", novelId, chapterId);
        rabbitTemplate.convertAndSend(
                RabbitMQConfig.AI_EXCHANGE,
                RabbitMQConfig.AI_CONSISTENCY_ROUTING_KEY,
                Map.of("novelId", novelId, "chapterId", chapterId, "content", content)
        );
    }

    public void sendNotification(Long userId, String type, String title, String message) {
        if (!canSend()) {
            return;
        }
        log.info("Sending notification: userId={}, type={}", userId, type);
        rabbitTemplate.convertAndSend(
                RabbitMQConfig.NOTIFICATION_EXCHANGE,
                RabbitMQConfig.NOTIFICATION_ROUTING_KEY,
                Map.of("userId", userId, "type", type, "title", title, "message", message)
        );
    }
}