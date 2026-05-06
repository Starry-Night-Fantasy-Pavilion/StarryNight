package com.starrynight.starrynight.framework.common.config;

import com.starrynight.starrynight.framework.common.config.condition.RabbitMqIntegrationEnabledCondition;
import org.springframework.amqp.core.*;
import org.springframework.amqp.rabbit.connection.ConnectionFactory;
import org.springframework.amqp.rabbit.core.RabbitTemplate;
import org.springframework.amqp.support.converter.Jackson2JsonMessageConverter;
import org.springframework.amqp.support.converter.MessageConverter;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Conditional;
import org.springframework.context.annotation.Configuration;

@Configuration
@Conditional(RabbitMqIntegrationEnabledCondition.class)
public class RabbitMQConfig {

    public static final String KNOWLEDGE_CHUNKING_QUEUE = "knowledge.chunking";
    public static final String KNOWLEDGE_VECTORIZING_QUEUE = "knowledge.vectorizing";
    public static final String AI_GENERATION_QUEUE = "ai.generation";
    public static final String AI_CONSISTENCY_CHECK_QUEUE = "ai.consistency.check";
    public static final String NOTIFICATION_QUEUE = "notification";

    public static final String KNOWLEDGE_EXCHANGE = "knowledge.exchange";
    public static final String AI_EXCHANGE = "ai.exchange";
    public static final String NOTIFICATION_EXCHANGE = "notification.exchange";

    public static final String KNOWLEDGE_CHUNKING_ROUTING_KEY = "knowledge.chunk";
    public static final String KNOWLEDGE_VECTORIZING_ROUTING_KEY = "knowledge.vectorize";
    public static final String AI_GENERATION_ROUTING_KEY = "ai.generate";
    public static final String AI_CONSISTENCY_ROUTING_KEY = "ai.check";
    public static final String NOTIFICATION_ROUTING_KEY = "notify";

    @Bean
    public MessageConverter jsonMessageConverter() {
        return new Jackson2JsonMessageConverter();
    }

    @Bean
    public RabbitTemplate rabbitTemplate(ConnectionFactory connectionFactory) {
        RabbitTemplate template = new RabbitTemplate(connectionFactory);
        template.setMessageConverter(jsonMessageConverter());
        return template;
    }

    @Bean
    public Queue knowledgeChunkingQueue() {
        return QueueBuilder.durable(KNOWLEDGE_CHUNKING_QUEUE)
                .deadLetterExchange("")
                .deadLetterRoutingKey(KNOWLEDGE_CHUNKING_QUEUE + ".dlq")
                .build();
    }

    @Bean
    public Queue knowledgeVectorizingQueue() {
        return QueueBuilder.durable(KNOWLEDGE_VECTORIZING_QUEUE)
                .deadLetterExchange("")
                .deadLetterRoutingKey(KNOWLEDGE_VECTORIZING_QUEUE + ".dlq")
                .build();
    }

    @Bean
    public Queue aiGenerationQueue() {
        return QueueBuilder.durable(AI_GENERATION_QUEUE)
                .deadLetterExchange("")
                .deadLetterRoutingKey(AI_GENERATION_QUEUE + ".dlq")
                .build();
    }

    @Bean
    public Queue aiConsistencyCheckQueue() {
        return QueueBuilder.durable(AI_CONSISTENCY_CHECK_QUEUE)
                .deadLetterExchange("")
                .deadLetterRoutingKey(AI_CONSISTENCY_CHECK_QUEUE + ".dlq")
                .build();
    }

    @Bean
    public Queue notificationQueue() {
        return QueueBuilder.durable(NOTIFICATION_QUEUE).build();
    }

    @Bean
    public DirectExchange knowledgeExchange() {
        return new DirectExchange(KNOWLEDGE_EXCHANGE);
    }

    @Bean
    public DirectExchange aiExchange() {
        return new DirectExchange(AI_EXCHANGE);
    }

    @Bean
    public DirectExchange notificationExchange() {
        return new DirectExchange(NOTIFICATION_EXCHANGE);
    }

    @Bean
    public Binding knowledgeChunkingBinding(Queue knowledgeChunkingQueue, DirectExchange knowledgeExchange) {
        return BindingBuilder.bind(knowledgeChunkingQueue)
                .to(knowledgeExchange)
                .with(KNOWLEDGE_CHUNKING_ROUTING_KEY);
    }

    @Bean
    public Binding knowledgeVectorizingBinding(Queue knowledgeVectorizingQueue, DirectExchange knowledgeExchange) {
        return BindingBuilder.bind(knowledgeVectorizingQueue)
                .to(knowledgeExchange)
                .with(KNOWLEDGE_VECTORIZING_ROUTING_KEY);
    }

    @Bean
    public Binding aiGenerationBinding(Queue aiGenerationQueue, DirectExchange aiExchange) {
        return BindingBuilder.bind(aiGenerationQueue)
                .to(aiExchange)
                .with(AI_GENERATION_ROUTING_KEY);
    }

    @Bean
    public Binding aiConsistencyBinding(Queue aiConsistencyCheckQueue, DirectExchange aiExchange) {
        return BindingBuilder.bind(aiConsistencyCheckQueue)
                .to(aiExchange)
                .with(AI_CONSISTENCY_ROUTING_KEY);
    }

    @Bean
    public Binding notificationBinding(Queue notificationQueue, DirectExchange notificationExchange) {
        return BindingBuilder.bind(notificationQueue)
                .to(notificationExchange)
                .with(NOTIFICATION_ROUTING_KEY);
    }
}