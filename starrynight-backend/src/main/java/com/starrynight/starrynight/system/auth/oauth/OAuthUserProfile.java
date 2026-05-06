package com.starrynight.starrynight.system.auth.oauth;

/**
 * 各 OAuth 提供商归一化后的用户摘要，供绑定或创建 {@code auth_user} 使用。
 */
public record OAuthUserProfile(String externalId, String usernameHint, String email, String avatarUrl) {
}
