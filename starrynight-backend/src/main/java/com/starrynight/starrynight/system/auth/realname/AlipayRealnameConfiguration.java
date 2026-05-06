package com.starrynight.starrynight.system.auth.realname;

import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.boot.autoconfigure.condition.ConditionalOnClass;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;

/**
 * 仅在 classpath 存在 {@code com.alipay.api.AlipayClient} 时注册真实网关，避免运行环境未带 {@code alipay-sdk-java} 时启动失败。
 */
@Configuration(proxyBeanMethods = false)
@ConditionalOnClass(name = "com.alipay.api.AlipayClient")
public class AlipayRealnameConfiguration {

    @Bean
    public AlipayRealnameGateway alipayRealnameGateway(
            RuntimeConfigService runtimeConfigService,
            RealnameCertifyPendingStore pendingStore) {
        return new AlipaySdkRealnameGateway(runtimeConfigService, pendingStore);
    }
}
