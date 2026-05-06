package com.starrynight.starrynight.framework.common.config;

import com.starrynight.starrynight.framework.common.config.condition.RabbitMqIntegrationEnabledCondition;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.amqp.rabbit.connection.CachingConnectionFactory;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Conditional;
import org.springframework.context.annotation.Configuration;
import org.springframework.context.annotation.DependsOn;
import org.springframework.context.annotation.Primary;
import org.springframework.context.annotation.Profile;

/**
 * RabbitMQ 连接从运营端 {@code system_config}（{@code spring.rabbitmq.*}）读取；
 * 使用 {@link HotSwapRabbitConnectionFactory} 支持热切换，由 {@link RabbitConnectionHotSwitch} 在配置刷新后替换连接并重启监听器。
 * <p>
 * 当 {@code rabbitmq.integration.enabled=false} 时不注册本配置（由 {@link RabbitMqIntegrationEnabledCondition} 控制，仅启动期判定）。
 */
@Configuration
@Profile("!test")
@DependsOn("runtimeConfigService")
@Conditional(RabbitMqIntegrationEnabledCondition.class)
public class OpsRabbitMQConnectionConfiguration {

    @Bean
    @Primary
    public HotSwapRabbitConnectionFactory rabbitConnectionFactory(RuntimeConfigService runtime) {
        CachingConnectionFactory initial = RabbitConnectionHotSwitch.buildCachingFromRuntime(runtime);
        initial.afterPropertiesSet();
        return new HotSwapRabbitConnectionFactory(initial);
    }
}
