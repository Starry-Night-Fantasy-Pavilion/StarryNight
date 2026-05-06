package com.starrynight.starrynight.framework.common.config.condition;

import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.context.annotation.Condition;
import org.springframework.context.annotation.ConditionContext;
import org.springframework.core.type.AnnotatedTypeMetadata;
import org.springframework.lang.NonNull;

/**
 * 是否启用 RabbitMQ 集成（队列、交换机、监听器及运营端自定义连接）。
 * 读取 {@code system_config} 键 {@value #CONFIG_KEY}，缺省为 {@code true}。
 * 仅<strong>启动期</strong>判定是否注册 Rabbit 相关 Bean；若启动时为 {@code false}，之后改为 {@code true} 需重启进程。
 * 启动时已集成时，可在运营端热关开（停/启监听器）或热切换 {@code spring.rabbitmq.*} 连接。
 */
public class RabbitMqIntegrationEnabledCondition implements Condition {

    public static final String CONFIG_KEY = "rabbitmq.integration.enabled";

    @Override
    public boolean matches(@NonNull ConditionContext context, @NonNull AnnotatedTypeMetadata metadata) {
        try {
            RuntimeConfigService runtime = context.getBeanFactory().getBean(RuntimeConfigService.class);
            return runtime.getBoolean(CONFIG_KEY, true);
        } catch (Exception e) {
            return true;
        }
    }
}
