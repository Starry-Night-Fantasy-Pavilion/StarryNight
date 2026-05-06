package com.starrynight.starrynight.framework.common.config;



import com.starrynight.starrynight.system.system.service.RuntimeConfigService;

import org.springframework.context.annotation.Bean;

import org.springframework.context.annotation.Configuration;

import org.springframework.context.annotation.DependsOn;

import org.springframework.context.annotation.Profile;

import org.springframework.data.redis.connection.lettuce.LettuceConnectionFactory;



/**

 * 对应 {@code spring.data.redis.*}：由 {@link HotSwapRedisConnectionFactory} 包装 Lettuce 单机连接，

 * 参数来自 {@code system_config}；运营端保存后由 {@link RedisConnectionHotSwitch} 自动热切换，无需重启。

 * <p>

 * 未落库时与 {@code db/seed.sql} 一致：localhost、6379、库 0、无密码。

 */

@Configuration

@Profile("!test")

@DependsOn("runtimeConfigService")

public class OpsRedisConnectionConfiguration {



    @Bean

    public HotSwapRedisConnectionFactory redisConnectionFactory(RuntimeConfigService runtime) {

        LettuceConnectionFactory initial = RedisConnectionHotSwitch.buildLettuceFromRuntime(runtime);

        initial.afterPropertiesSet();

        return new HotSwapRedisConnectionFactory(initial);

    }

}

