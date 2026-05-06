package com.starrynight.starrynight.framework.common.config;

import com.starrynight.starrynight.framework.common.config.condition.RabbitMqIntegrationEnabledCondition;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import jakarta.annotation.PostConstruct;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.amqp.rabbit.connection.CachingConnectionFactory;
import org.springframework.amqp.rabbit.listener.MessageListenerContainer;
import org.springframework.amqp.rabbit.listener.RabbitListenerEndpointRegistry;
import org.springframework.beans.factory.ObjectProvider;
import org.springframework.context.annotation.Conditional;
import org.springframework.context.annotation.Profile;
import org.springframework.stereotype.Component;
import org.springframework.util.StringUtils;

import java.util.Objects;

/**
 * 运营端修改 {@code spring.rabbitmq.*} 或 {@code rabbitmq.integration.enabled} 后，热切换连接并停启监听器（无需重启）。
 * <p>
 * 若进程启动时未启用 Rabbit 集成（{@link RabbitMqIntegrationEnabledCondition}），本组件与 {@link HotSwapRabbitConnectionFactory} 均不存在，需改配置后重启。
 */
@Component
@Profile("!test")
@Conditional(RabbitMqIntegrationEnabledCondition.class)
public class RabbitConnectionHotSwitch {

    private static final Logger log = LoggerFactory.getLogger(RabbitConnectionHotSwitch.class);

    private final RuntimeConfigService runtimeConfigService;
    private final HotSwapRabbitConnectionFactory hotSwapRabbitConnectionFactory;
    private final ObjectProvider<RabbitListenerEndpointRegistry> listenerRegistryProvider;

    private volatile RabbitParams applied;
    /** 上一轮 reload 时集成是否为开启（用于从关→开时强制换连接，避免关期间改过 host 仍用旧工厂）。 */
    private volatile boolean lastIntegrationOn;

    public RabbitConnectionHotSwitch(
            RuntimeConfigService runtimeConfigService,
            HotSwapRabbitConnectionFactory hotSwapRabbitConnectionFactory,
            ObjectProvider<RabbitListenerEndpointRegistry> listenerRegistryProvider) {
        this.runtimeConfigService = runtimeConfigService;
        this.hotSwapRabbitConnectionFactory = hotSwapRabbitConnectionFactory;
        this.listenerRegistryProvider = listenerRegistryProvider;
    }

    @PostConstruct
    void registerHook() {
        applied = readParams();
        lastIntegrationOn = runtimeConfigService.getBoolean(RabbitMqIntegrationEnabledCondition.CONFIG_KEY, true);
        runtimeConfigService.registerAfterReloadHook(this::onRuntimeConfigReloaded);
    }

    private void onRuntimeConfigReloaded() {
        boolean integration = runtimeConfigService.getBoolean(RabbitMqIntegrationEnabledCondition.CONFIG_KEY, true);
        RabbitParams now = readParams();

        if (!integration) {
            stopListenerContainers();
            applied = now;
            lastIntegrationOn = false;
            log.debug("RabbitMQ 集成已关闭（{}），已停止监听器容器", RabbitMqIntegrationEnabledCondition.CONFIG_KEY);
            return;
        }

        synchronized (this) {
            RabbitParams again = readParams();
            boolean integrationJustEnabled = !lastIntegrationOn;
            lastIntegrationOn = true;

            boolean paramsChanged = !Objects.equals(again, applied) || integrationJustEnabled;
            if (paramsChanged) {
                stopListenerContainers();
                CachingConnectionFactory next = buildCaching(again);
                hotSwapRabbitConnectionFactory.swapTo(next);
                applied = again;
                log.info("RabbitMQ 连接已热切换: {}:{} vhost={} user={}",
                        again.host(), again.port(), again.vhostNorm(), again.username());
            }
            startListenerContainers();
        }
    }

    private void stopListenerContainers() {
        RabbitListenerEndpointRegistry registry = listenerRegistryProvider.getIfAvailable();
        if (registry == null) {
            return;
        }
        for (MessageListenerContainer c : registry.getListenerContainers()) {
            try {
                if (c.isRunning()) {
                    c.stop();
                }
            } catch (Exception e) {
                log.warn("停止 Rabbit 监听器容器失败: {}", e.toString());
            }
        }
    }

    private void startListenerContainers() {
        RabbitListenerEndpointRegistry registry = listenerRegistryProvider.getIfAvailable();
        if (registry == null) {
            return;
        }
        for (MessageListenerContainer c : registry.getListenerContainers()) {
            try {
                if (!c.isRunning()) {
                    c.start();
                }
            } catch (Exception e) {
                log.warn("启动 Rabbit 监听器容器失败: {}", e.toString());
            }
        }
    }

    private RabbitParams readParams() {
        return readParamsFrom(runtimeConfigService);
    }

    public static CachingConnectionFactory buildCachingFromRuntime(RuntimeConfigService runtime) {
        return buildCaching(readParamsFrom(runtime));
    }

    private static RabbitParams readParamsFrom(RuntimeConfigService runtime) {
        String host = runtime.getString("spring.rabbitmq.host", "localhost");
        int port = runtime.getInt("spring.rabbitmq.port", 5672);
        String username = runtime.getString("spring.rabbitmq.username", "guest");
        String password = runtime.getString("spring.rabbitmq.password", "");
        String pwdNorm = StringUtils.hasText(password) ? password.trim() : "";
        String vhost = runtime.getString("spring.rabbitmq.virtual-host", "/");
        if (!StringUtils.hasText(vhost)) {
            vhost = "/";
        } else {
            vhost = vhost.trim();
        }
        return new RabbitParams(host, port, username, pwdNorm, vhost);
    }

    private static CachingConnectionFactory buildCaching(RabbitParams p) {
        CachingConnectionFactory factory = new CachingConnectionFactory();
        factory.setHost(p.host());
        factory.setPort(p.port());
        factory.setUsername(p.username());
        if (StringUtils.hasText(p.passwordNorm())) {
            factory.setPassword(p.passwordNorm());
        }
        factory.setVirtualHost(p.vhostNorm());
        return factory;
    }

    private record RabbitParams(String host, int port, String username, String passwordNorm, String vhostNorm) {
    }
}
