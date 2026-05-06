package com.starrynight.starrynight.framework.common.config;

import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import jakarta.annotation.PostConstruct;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.context.annotation.Profile;
import org.springframework.data.redis.connection.RedisPassword;
import org.springframework.data.redis.connection.RedisStandaloneConfiguration;
import org.springframework.data.redis.connection.lettuce.LettuceConnectionFactory;
import org.springframework.stereotype.Component;
import org.springframework.util.StringUtils;

import java.util.Objects;

/**
 * 在 {@link RuntimeConfigService#reloadFromDatabase()} 之后比对 Redis 相关 {@code system_config}，
 * 若有变化则替换底层 Lettuce 连接，无需重启应用。
 */
@Component
@Profile("!test")
public class RedisConnectionHotSwitch {

    private static final Logger log = LoggerFactory.getLogger(RedisConnectionHotSwitch.class);

    private final RuntimeConfigService runtimeConfigService;
    private final HotSwapRedisConnectionFactory hotSwapRedisConnectionFactory;

    private volatile RedisParams applied;

    public RedisConnectionHotSwitch(
            RuntimeConfigService runtimeConfigService,
            HotSwapRedisConnectionFactory hotSwapRedisConnectionFactory) {
        this.runtimeConfigService = runtimeConfigService;
        this.hotSwapRedisConnectionFactory = hotSwapRedisConnectionFactory;
    }

    @PostConstruct
    void registerHook() {
        applied = readParams();
        runtimeConfigService.registerAfterReloadHook(this::onRuntimeConfigReloaded);
    }

    private void onRuntimeConfigReloaded() {
        RedisParams now = readParams();
        RedisParams prev = applied;
        if (Objects.equals(now, prev)) {
            return;
        }
        synchronized (this) {
            RedisParams again = readParams();
            if (Objects.equals(again, applied)) {
                return;
            }
            LettuceConnectionFactory next = buildLettuce(again);
            hotSwapRedisConnectionFactory.swapTo(next);
            applied = again;
            log.info("Redis 连接已热切换: {}:{} db={}", again.host(), again.port(), again.database());
        }
    }

    private RedisParams readParams() {
        return readParamsFrom(runtimeConfigService);
    }

    public static LettuceConnectionFactory buildLettuceFromRuntime(RuntimeConfigService runtime) {
        return buildLettuce(readParamsFrom(runtime));
    }

    private static RedisParams readParamsFrom(RuntimeConfigService runtime) {
        String host = runtime.getString("spring.data.redis.host", "localhost");
        int port = runtime.getInt("spring.data.redis.port", 6379);
        int database = runtime.getInt("spring.data.redis.database", 0);
        String pwd = runtime.getProperty("spring.data.redis.password");
        String pwdNorm = StringUtils.hasText(pwd) ? pwd.trim() : "";
        return new RedisParams(host, port, database, pwdNorm);
    }

    private static LettuceConnectionFactory buildLettuce(RedisParams p) {
        RedisStandaloneConfiguration standalone = new RedisStandaloneConfiguration();
        standalone.setHostName(p.host());
        standalone.setPort(p.port());
        standalone.setDatabase(p.database());
        if (StringUtils.hasText(p.passwordNorm())) {
            standalone.setPassword(RedisPassword.of(p.passwordNorm()));
        }
        return new LettuceConnectionFactory(standalone);
    }

    private record RedisParams(String host, int port, int database, String passwordNorm) {
    }
}
