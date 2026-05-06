package com.starrynight.starrynight.framework.common.filter;

import jakarta.servlet.*;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.core.Ordered;
import org.springframework.core.annotation.Order;
import org.springframework.data.redis.core.StringRedisTemplate;
import org.springframework.data.redis.core.script.DefaultRedisScript;
import org.springframework.stereotype.Component;

import java.io.IOException;
import java.util.Collections;

@Component
@Order(Ordered.HIGHEST_PRECEDENCE + 1)
public class RateLimitFilter implements Filter {

    @Autowired
    private StringRedisTemplate redisTemplate;

    private static final String RATE_LIMIT_PREFIX = "rate_limit:";

    private static final int DEFAULT_MAX_REQUESTS = 100;
    private static final int DEFAULT_WINDOW_SECONDS = 60;

    private static final String[] IGNORE_PATHS = {
            "/api-docs", "/swagger-ui", "/v3/api-docs", "/error"
    };

    private static final DefaultRedisScript<Long> RATE_LIMIT_SCRIPT = buildScript();

    private static DefaultRedisScript<Long> buildScript() {
        DefaultRedisScript<Long> script = new DefaultRedisScript<>();
        script.setScriptText(
                "local key = KEYS[1]\n" +
                "local limit = tonumber(ARGV[1])\n" +
                "local window = tonumber(ARGV[2])\n" +
                "local current = tonumber(redis.call('get', key) or '0')\n" +
                "if current + 1 > limit then\n" +
                "    return -1\n" +
                "end\n" +
                "redis.call('incr', key)\n" +
                "if current == 0 then\n" +
                "    redis.call('expire', key, window)\n" +
                "end\n" +
                "return current + 1"
        );
        script.setResultType(Long.class);
        return script;
    }

    @Override
    public void doFilter(ServletRequest request, ServletResponse response, FilterChain chain)
            throws IOException, ServletException {
        HttpServletRequest httpRequest = (HttpServletRequest) request;
        HttpServletResponse httpResponse = (HttpServletResponse) response;

        String path = httpRequest.getRequestURI();

        for (String ignorePath : IGNORE_PATHS) {
            if (path.startsWith(ignorePath)) {
                chain.doFilter(request, response);
                return;
            }
        }

        String clientId = getClientId(httpRequest);
        String key = RATE_LIMIT_PREFIX + clientId + ":" + path;

        int maxRequests = getMaxRequests(path);
        int windowSeconds = getWindowSeconds(path);

        try {
            Long result = redisTemplate.execute(
                    RATE_LIMIT_SCRIPT,
                    Collections.singletonList(key),
                    String.valueOf(maxRequests),
                    String.valueOf(windowSeconds)
            );

            if (result == null || result < 0) {
                httpResponse.setStatus(429);
                httpResponse.setContentType("application/json;charset=UTF-8");
                httpResponse.getWriter().write("{\"code\":429,\"message\":\"请求过于频繁，请稍后再试\"}");
                return;
            }

            httpResponse.setHeader("X-RateLimit-Limit", String.valueOf(maxRequests));
            httpResponse.setHeader("X-RateLimit-Remaining", String.valueOf(Math.max(0, maxRequests - result)));
            httpResponse.setHeader("X-RateLimit-Reset", String.valueOf(System.currentTimeMillis() / 1000 + windowSeconds));

            chain.doFilter(request, response);
        } catch (Exception e) {
            chain.doFilter(request, response);
        }
    }

    private String getClientId(HttpServletRequest request) {
        String clientId = request.getHeader("X-Client-Id");
        if (clientId == null || clientId.isEmpty()) {
            clientId = request.getRemoteAddr();
        }
        return clientId.hashCode() > 0 ? String.valueOf(clientId.hashCode()) : String.valueOf(-clientId.hashCode());
    }

    private int getMaxRequests(String path) {
        if (path.startsWith("/api/novels") || path.startsWith("/api/chapters")) {
            return 30;
        }
        if (path.startsWith("/api/ai/")) {
            return 10;
        }
        if (path.startsWith("/api/auth/")) {
            return 5;
        }
        return DEFAULT_MAX_REQUESTS;
    }

    private int getWindowSeconds(String path) {
        if (path.startsWith("/api/ai/")) {
            return 60;
        }
        return DEFAULT_WINDOW_SECONDS;
    }
}
