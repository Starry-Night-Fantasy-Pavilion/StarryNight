package com.starrynight.starrynight.system.monitor.aspect;

import com.alibaba.fastjson2.JSON;
import com.starrynight.starrynight.system.monitor.entity.OperationLog;
import com.starrynight.starrynight.system.monitor.repository.OperationLogRepository;
import jakarta.servlet.http.HttpServletRequest;
import org.aspectj.lang.ProceedingJoinPoint;
import org.aspectj.lang.annotation.Around;
import org.aspectj.lang.annotation.Aspect;
import org.aspectj.lang.annotation.Pointcut;
import org.aspectj.lang.reflect.MethodSignature;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;
import org.springframework.web.context.request.RequestContextHolder;
import org.springframework.web.context.request.ServletRequestAttributes;

import java.lang.reflect.Method;
import java.time.LocalDateTime;

@Aspect
@Component
public class OperationLogAspect {

    private static final Logger log = LoggerFactory.getLogger(OperationLogAspect.class);

    @Autowired
    private OperationLogRepository operationLogRepository;

    @Pointcut("@annotation(com.starrynight.starrynight.system.monitor.annotation.OperationLog)")
    public void operationLogPointcut() {}

    @Around("operationLogPointcut()")
    public Object around(ProceedingJoinPoint joinPoint) throws Throwable {
        long startTime = System.currentTimeMillis();
        OperationLog operationLog = new OperationLog();
        operationLog.setCreateTime(LocalDateTime.now());

        try {
            ServletRequestAttributes attributes = (ServletRequestAttributes) RequestContextHolder.getRequestAttributes();
            if (attributes != null) {
                HttpServletRequest request = attributes.getRequest();
                operationLog.setRequestUrl(request.getRequestURI());
                operationLog.setRequestMethod(request.getMethod());
                operationLog.setIpAddress(getClientIp(request));
                operationLog.setUserAgent(request.getHeader("User-Agent"));
            }

            MethodSignature signature = (MethodSignature) joinPoint.getSignature();
            Method method = signature.getMethod();
            operationLog.setMethod(method.getDeclaringClass().getName() + "." + method.getName());

            com.starrynight.starrynight.system.monitor.annotation.OperationLog annotation =
                    method.getAnnotation(com.starrynight.starrynight.system.monitor.annotation.OperationLog.class);
            if (annotation != null) {
                operationLog.setOperation(annotation.value());
                operationLog.setModule(annotation.module());
            }

            Object[] args = joinPoint.getArgs();
            if (args != null && args.length > 0) {
                operationLog.setRequestParams(JSON.toJSONString(args));
            }

            Object result = joinPoint.proceed();

            operationLog.setStatus(1);
            operationLog.setResponseData(result != null ? JSON.toJSONString(result).substring(0, Math.min(1000, JSON.toJSONString(result).length())) : null);

            return result;
        } catch (Exception e) {
            operationLog.setStatus(0);
            operationLog.setErrorMessage(e.getMessage());
            throw e;
        } finally {
            long executionTime = System.currentTimeMillis() - startTime;
            operationLog.setExecutionTime((int) executionTime);

            try {
                operationLogRepository.insert(operationLog);
            } catch (Exception e) {
                log.error("Failed to save operation log", e);
            }
        }
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
            ip = request.getRemoteAddr();
        }
        return ip;
    }
}

