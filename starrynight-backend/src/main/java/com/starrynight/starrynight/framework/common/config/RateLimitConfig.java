package com.starrynight.starrynight.framework.common.config;

import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.data.redis.core.RedisTemplate;
import org.springframework.web.servlet.HandlerInterceptor;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.util.concurrent.TimeUnit;

@Configuration
public class RateLimitConfig {

    @Autowired
    private RedisTemplate<String, Object> redisTemplate;

    @Bean
    public HandlerInterceptor rateLimitInterceptor() {
        return new HandlerInterceptor() {
            @Override
            public boolean preHandle(HttpServletRequest request, HttpServletResponse response, Object handler) throws Exception {
                String ip = getClientIp(request);
                String uri = request.getRequestURI();
                String key = "rate_limit:" + ip + ":" + uri;

                Long currentCount = redisTemplate.opsForValue().increment(key);

                if (currentCount != null && currentCount == 1) {
                    redisTemplate.expire(key, 1, TimeUnit.MINUTES);
                }

                if (currentCount != null && currentCount > 60) {
                    response.setContentType("application/json;charset=UTF-8");
                    response.getWriter().write("{\"code\":429,\"message\":\"请求过于频繁，请稍后再试\"}");
                    response.setStatus(429);
                    return false;
                }

                response.setHeader("X-RateLimit-Remaining", currentCount != null ? String.valueOf(Math.max(0, 60 - currentCount)) : "60");

                return true;
            }

            private String getClientIp(HttpServletRequest request) {
                String ip = request.getHeader("X-Forwarded-For");
                if (ip == null || ip.isEmpty() || "unknown".equalsIgnoreCase(ip)) {
                    ip = request.getHeader("Proxy-Client-IP");
                }
                if (ip == null || ip.isEmpty() || "unknown".equalsIgnoreCase(ip)) {
                    ip = request.getHeader("WL-Proxy-Client-IP");
                }
                if (ip == null || ip.isEmpty() || "unknown".equalsIgnoreCase(ip)) {
                    ip = request.getHeader("X-Real-IP");
                }
                if (ip == null || ip.isEmpty() || "unknown".equalsIgnoreCase(ip)) {
                    ip = request.getRemoteAddr();
                }
                if (ip != null && ip.contains(",")) {
                    ip = ip.split(",")[0].trim();
                }
                return ip;
            }
        };
    }
}