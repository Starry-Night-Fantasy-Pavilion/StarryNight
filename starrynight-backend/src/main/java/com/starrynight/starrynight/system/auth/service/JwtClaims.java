package com.starrynight.starrynight.system.auth.service;

/**
 * @param principalId      JWT 主体 ID（USER=auth_user.id，OPS=ops_account.id）
 * @param portalClaim      JWT 中 portal 声明（USER/OPS）
 * @param subjectTypeClaim JWT 中 subject_type 声明（USER/OPS）
 */
public record JwtClaims(Long principalId, String portalClaim, String subjectTypeClaim) {
}
