package com.starrynight.starrynight.services.ai.config;

import com.starrynight.starrynight.services.ai.impl.OpenAiApiClient;
import com.starrynight.starrynight.services.ai.impl.StreamingOpenAiApiClient;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.context.annotation.Primary;
import org.springframework.web.reactive.function.client.WebClient;

/**
 * 通过 {@link Bean} 注册 AI 客户端，避免 {@code @Service} 实现类在 Spring 配置解析阶段被当作
 * configuration class 处理时强依赖接口字节码（DevTools 重启 / 增量编译下曾出现 AiApiClient.class 找不到）。
 */
@Configuration
public class AiClientsConfiguration {

    @Bean
    @Primary
    public OpenAiApiClient openAiApiClient(WebClient.Builder webClientBuilder,
                                           RuntimeConfigService runtimeConfigService) {
        return new OpenAiApiClient(webClientBuilder, runtimeConfigService);
    }

    @Bean
    public StreamingOpenAiApiClient streamingOpenAiApiClient(WebClient.Builder webClientBuilder,
                                                             RuntimeConfigService runtimeConfigService) {
        return new StreamingOpenAiApiClient(webClientBuilder, runtimeConfigService);
    }
}
