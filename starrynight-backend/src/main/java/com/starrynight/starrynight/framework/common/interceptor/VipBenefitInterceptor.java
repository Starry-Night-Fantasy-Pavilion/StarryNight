package com.starrynight.starrynight.framework.common.interceptor;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.vip.service.VipMembershipService;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Component;
import org.springframework.web.method.HandlerMethod;
import org.springframework.web.servlet.HandlerInterceptor;

@Slf4j
@Component
@RequiredArgsConstructor
public class VipBenefitInterceptor implements HandlerInterceptor {

    private final VipMembershipService membershipService;

    @Override
    public boolean preHandle(HttpServletRequest request, HttpServletResponse response, Object handler) throws Exception {
        if (!(handler instanceof HandlerMethod)) {
            return true;
        }

        HandlerMethod method = (HandlerMethod) handler;
        RequireVipBenefit annotation = method.getMethodAnnotation(RequireVipBenefit.class);

        if (annotation == null) {
            return true;
        }

        Long userId = getUserIdFromRequest(request);
        if (userId == null) {
            throw new BusinessException(401, "请先登录");
        }

        String benefitKey = annotation.value();
        boolean hasBenefit = membershipService.hasBenefit(userId, benefitKey);

        if (!hasBenefit) {
            String message = annotation.message();
            if (message.isEmpty()) {
                message = "您没有此功能的使用权限，请升级会员";
            }
            throw new BusinessException(403, message);
        }

        return true;
    }

    private Long getUserIdFromRequest(HttpServletRequest request) {
        String userIdStr = request.getHeader("X-User-Id");
        if (userIdStr != null && !userIdStr.isEmpty()) {
            try {
                return Long.parseLong(userIdStr);
            } catch (NumberFormatException ignored) {
            }
        }

        Object userId = request.getAttribute("userId");
        if (userId instanceof Long) {
            return (Long) userId;
        }

        return null;
    }
}
