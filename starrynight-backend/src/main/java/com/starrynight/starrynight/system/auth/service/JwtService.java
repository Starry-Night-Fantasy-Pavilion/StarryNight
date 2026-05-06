package com.starrynight.starrynight.system.auth.service;

import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import io.jsonwebtoken.Claims;
import io.jsonwebtoken.Jwts;
import io.jsonwebtoken.security.Keys;
import org.springframework.stereotype.Service;

import javax.crypto.SecretKey;
import java.nio.charset.StandardCharsets;
import java.util.Date;

@Service
public class JwtService {

    /** 与 {@code db/seed.sql} 一致；未落库或为空时使用，生产环境请在运营端改为强随机串。 */
    private static final String DEFAULT_JWT_SECRET = "starrynight-secret-key-change-in-production-environment";

    public static final String PORTAL_CLAIM = "portal";
    public static final String SUBJECT_TYPE_CLAIM = "subject_type";

    private final RuntimeConfigService runtimeConfigService;

    public JwtService(RuntimeConfigService runtimeConfigService) {
        this.runtimeConfigService = runtimeConfigService;
    }

    public String generateAccessToken(Long principalId, String portal, String subjectType) {
        return generateToken(principalId, accessExpirationMs(), portal, subjectType);
    }

    public String generateRefreshToken(Long principalId, String portal, String subjectType) {
        return generateToken(principalId, refreshExpirationMs(), portal, subjectType);
    }

    private long accessExpirationMs() {
        return runtimeConfigService.getLong("jwt.expiration", 86400000L);
    }

    private long refreshExpirationMs() {
        return runtimeConfigService.getLong("jwt.refresh-expiration", 604800000L);
    }

    private String jwtSecret() {
        return runtimeConfigService.getString("jwt.secret", DEFAULT_JWT_SECRET);
    }

    private SecretKey getSigningKey() {
        return Keys.hmacShaKeyFor(jwtSecret().getBytes(StandardCharsets.UTF_8));
    }

    private String generateToken(Long principalId, Long expiration, String portal, String subjectType) {
        Date now = new Date();
        Date expiryDate = new Date(now.getTime() + expiration);

        var builder = Jwts.builder()
                .subject(String.valueOf(principalId))
                .issuedAt(now)
                .expiration(expiryDate);
        if (portal != null && !portal.isBlank()) {
            builder.claim(PORTAL_CLAIM, portal);
        }
        if (subjectType != null && !subjectType.isBlank()) {
            builder.claim(SUBJECT_TYPE_CLAIM, subjectType);
        }
        return builder.signWith(getSigningKey()).compact();
    }

    public JwtClaims parseAccessTokenClaims(String token) {
        return parseClaims(token);
    }

    public JwtClaims parseRefreshTokenClaims(String token) {
        return parseClaims(token);
    }

    /**
     * @deprecated 仅兼容旧调用方；新代码请使用 {@link #parseAccessTokenClaims(String)}。
     */
    @Deprecated
    public Long validateAccessToken(String token) {
        return parseAccessTokenClaims(token).principalId();
    }

    /**
     * @deprecated 仅兼容旧调用方；新代码请使用 {@link #parseRefreshTokenClaims(String)}。
     */
    @Deprecated
    public Long validateRefreshToken(String token) {
        return parseRefreshTokenClaims(token).principalId();
    }

    private JwtClaims parseClaims(String token) {
        Claims claims = Jwts.parser()
                .verifyWith(getSigningKey())
                .build()
                .parseSignedClaims(token)
                .getPayload();
        Long principalId = Long.parseLong(claims.getSubject());
        String portal = claims.get(PORTAL_CLAIM, String.class);
        String subjectType = claims.get(SUBJECT_TYPE_CLAIM, String.class);
        return new JwtClaims(principalId, portal, subjectType);
    }
}
