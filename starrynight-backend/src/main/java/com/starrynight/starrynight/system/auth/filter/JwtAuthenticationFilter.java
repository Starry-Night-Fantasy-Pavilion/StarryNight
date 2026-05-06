package com.starrynight.starrynight.system.auth.filter;

import com.starrynight.starrynight.system.auth.service.JwtClaims;
import com.starrynight.starrynight.system.auth.service.JwtService;
import com.starrynight.starrynight.system.auth.AuthPortal;
import com.starrynight.starrynight.system.auth.AuthSubjectType;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.entity.OpsAccount;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.auth.repository.OpsAccountRepository;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.core.authority.SimpleGrantedAuthority;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.lang.NonNull;
import org.springframework.stereotype.Component;
import org.springframework.util.StringUtils;
import org.springframework.web.filter.OncePerRequestFilter;

import java.io.IOException;
import java.util.Collections;
import java.util.List;

@Component
public class JwtAuthenticationFilter extends OncePerRequestFilter {

    @Autowired
    private JwtService jwtService;
    @Autowired
    private AuthUserRepository authUserRepository;
    @Autowired
    private OpsAccountRepository opsAccountRepository;

    /**
     * 仅对「无需解析 Bearer」的公开接口跳过本过滤器。
     * /api/auth/me、/api/auth/logout 必须走 JWT，否则 ThreadLocal 无用户，且安全上下文无认证信息。
     */
    @Override
    protected boolean shouldNotFilter(@NonNull HttpServletRequest request) {
        return isAuthPublicJwtSkipPath(resolveRequestPath(request));
    }

    private static String resolveRequestPath(HttpServletRequest request) {
        String servletPath = request.getServletPath();
        String pathInfo = request.getPathInfo();
        String path = (servletPath != null ? servletPath : "") + (pathInfo != null ? pathInfo : "");
        if (!StringUtils.hasText(path)) {
            path = request.getRequestURI();
            String ctx = request.getContextPath();
            if (StringUtils.hasText(ctx) && path.startsWith(ctx)) {
                path = path.substring(ctx.length());
            }
        }
        return path;
    }

    /**
     * 与 SecurityConfig 中 permitAll 的认证接口一致：不携带 Access Token 或仅用 Refresh-Token。
     */
    private static boolean isAuthPublicJwtSkipPath(String path) {
        if (!path.startsWith("/api/auth/")) {
            return false;
        }
        String rest = path.substring("/api/auth/".length());
        int q = rest.indexOf('?');
        if (q >= 0) {
            rest = rest.substring(0, q);
        }
        return rest.equals("login")
                || rest.startsWith("login/")
                || rest.equals("register")
                || rest.startsWith("register/")
                || rest.equals("refresh")
                || rest.startsWith("refresh")
                || rest.equals("send-code")
                || rest.startsWith("send-code/")
                || rest.equals("reset-password")
                || rest.startsWith("reset-password/");
    }

    @Override
    protected void doFilterInternal(HttpServletRequest request, HttpServletResponse response, FilterChain filterChain)
            throws ServletException, IOException {

        try {
            String token = getTokenFromRequest(request);

            if (StringUtils.hasText(token)) {
                try {
                    JwtClaims claims = jwtService.parseAccessTokenClaims(token);
                    AuthPortal portal = resolvePortal(claims);
                    AuthSubjectType subjectType = AuthSubjectType.fromClaim(claims.subjectTypeClaim(), portal);
                    if (subjectType == AuthSubjectType.OPS) {
                        OpsAccount opsAccount = opsAccountRepository.selectById(claims.principalId());
                        if (opsAccount != null && opsAccount.getDeleted() == 0 && opsAccount.getStatus() == 1) {
                            ThreadLocalUtil.setUserId(opsAccount.getId());
                            List<SimpleGrantedAuthority> authorities =
                                    List.of(new SimpleGrantedAuthority("ROLE_ADMIN"));
                            UsernamePasswordAuthenticationToken authentication =
                                    new UsernamePasswordAuthenticationToken(opsAccount.getId(), null, authorities);
                            authentication.setDetails(claims);
                            SecurityContextHolder.getContext().setAuthentication(authentication);
                        } else {
                            SecurityContextHolder.clearContext();
                        }
                    } else {
                        AuthUser user = authUserRepository.selectById(claims.principalId());
                        if (user != null && user.getDeleted() == 0 && user.getStatus() == 1) {
                            ThreadLocalUtil.setUserId(user.getId());
                            UsernamePasswordAuthenticationToken authentication =
                                    new UsernamePasswordAuthenticationToken(user.getId(), null, Collections.emptyList());
                            authentication.setDetails(claims);
                            SecurityContextHolder.getContext().setAuthentication(authentication);
                        } else {
                            SecurityContextHolder.clearContext();
                        }
                    }
                } catch (Exception e) {
                    SecurityContextHolder.clearContext();
                }
            }

            filterChain.doFilter(request, response);
        } finally {
            ThreadLocalUtil.clear();
        }
    }

    private AuthPortal resolvePortal(JwtClaims claims) {
        return AuthPortal.fromJwtClaim(claims.portalClaim()).orElse(AuthPortal.USER);
    }

    private String getTokenFromRequest(HttpServletRequest request) {
        String bearerToken = request.getHeader("Authorization");
        if (!StringUtils.hasText(bearerToken)) {
            return null;
        }
        String prefix = "Bearer ";
        if (bearerToken.regionMatches(true, 0, prefix, 0, prefix.length())) {
            return bearerToken.substring(prefix.length()).trim();
        }
        return null;
    }
}

