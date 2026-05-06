package com.starrynight.starrynight.framework.common.config;

import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import jakarta.servlet.MultipartConfigElement;
import org.springframework.boot.web.servlet.MultipartConfigFactory;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.context.annotation.DependsOn;
import org.springframework.context.annotation.Profile;
import org.springframework.util.unit.DataSize;

/**
 * 上传大小从运营端 {@code system_config} 读取；未落库时与 {@code db/seed.sql} 一致，默认 100MB。
 */
@Configuration
@Profile("!test")
@DependsOn("runtimeConfigService")
public class OpsServletMultipartConfiguration {

    private static final String DEFAULT_MULTIPART_SIZE = "100MB";

    @Bean
    public MultipartConfigElement multipartConfigElement(RuntimeConfigService runtime) {
        String maxFile = runtime.getString("server.servlet.multipart.max-file-size", DEFAULT_MULTIPART_SIZE);
        String maxRequest = runtime.getString("server.servlet.multipart.max-request-size", DEFAULT_MULTIPART_SIZE);
        MultipartConfigFactory factory = new MultipartConfigFactory();
        factory.setMaxFileSize(DataSize.parse(maxFile));
        factory.setMaxRequestSize(DataSize.parse(maxRequest));
        return factory.createMultipartConfig();
    }
}
