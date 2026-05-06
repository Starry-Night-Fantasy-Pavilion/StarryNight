package com.starrynight.starrynight.framework.common.interceptor;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.util.XssCleaner;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;
import org.springframework.web.servlet.HandlerInterceptor;
import org.springframework.web.util.ContentCachingRequestWrapper;

import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.util.Map;

@Component
public class XssJsonInterceptor implements HandlerInterceptor {

    @Autowired
    private ObjectMapper objectMapper;

    @Override
    public boolean preHandle(HttpServletRequest request, HttpServletResponse response, Object handler) throws Exception {
        String contentType = request.getContentType();

        if (contentType != null && contentType.contains("application/json")) {
            if (request instanceof ContentCachingRequestWrapper wrapper) {
                byte[] buf = wrapper.getContentAsByteArray();
                if (buf.length > 0) {
                    String jsonBody = new String(buf, StandardCharsets.UTF_8);
                    String cleanedJson = XssCleaner.cleanJson(jsonBody);
                    if (!cleanedJson.equals(jsonBody)) {
                        byte[] cleanedBytes = cleanedJson.getBytes(StandardCharsets.UTF_8);
                        wrapper.setAttribute("cachedBody", cleanedBytes);
                    }
                }
            }
        }

        return true;
    }
}