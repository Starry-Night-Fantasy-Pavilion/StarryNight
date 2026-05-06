package com.starrynight.starrynight.system;

import com.starrynight.engine.foreshadowing.ForeshadowingDetector;
import com.starrynight.engine.foreshadowing.ForeshadowingPayoffChecker;
import com.starrynight.engine.rhythm.RhythmAnalyzer;
import org.mybatis.spring.annotation.MapperScan;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.cache.annotation.EnableCaching;
import org.springframework.context.annotation.Import;
import org.springframework.scheduling.annotation.EnableScheduling;

import java.util.HashMap;
import java.util.Map;

@EnableCaching
@EnableScheduling
@SpringBootApplication(scanBasePackages = "com.starrynight.starrynight")
@Import({ForeshadowingDetector.class, ForeshadowingPayoffChecker.class, RhythmAnalyzer.class})
@MapperScan({"com.starrynight.**.mapper", "com.starrynight.**.repository"})
public class StarryNightApplication {

    public static void main(String[] args) {
        SpringApplication app = new SpringApplication(StarryNightApplication.class);
        Map<String, Object> defaults = new HashMap<>();
        defaults.put("spring.application.name", "starrynight-backend");
        defaults.put("management.health.redis.enabled", false);
        defaults.put("springdoc.api-docs.path", "/api-docs");
        defaults.put("springdoc.swagger-ui.path", "/swagger-ui.html");
        defaults.put("springdoc.swagger-ui.enabled", true);
        defaults.put("logging.pattern.console",
                "%d{yyyy-MM-dd HH:mm:ss} [%thread] %-5level %logger{36} - %msg%n");
        app.setDefaultProperties(defaults);
        app.run(args);
    }
}

