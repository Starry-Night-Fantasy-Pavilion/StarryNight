package com.starrynight.starrynight.system.auth.oauth;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.util.ValidationUtil;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.service.AuthService;
import com.starrynight.starrynight.system.auth.vo.AuthVO;
import com.starrynight.starrynight.system.auth.vo.OauthLoginOptionsVO;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import jakarta.servlet.http.Cookie;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;
import org.springframework.web.reactive.function.BodyInserters;
import org.springframework.web.reactive.function.client.WebClient;
import java.io.IOException;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.time.Instant;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

@Service
public class PortalOAuthService {

    private static final Logger log = LoggerFactory.getLogger(PortalOAuthService.class);
    private static final Duration HTTP_TIMEOUT = Duration.ofSeconds(25);
    private static final String UA = "StarryNight-OAuth/1.0";
    private static final Set<String> SLUGS = Set.of("linuxdo", "github", "google", "wechat", "qq");

    /** 知我云聚合登录方式，与 https://u.zevost.com/doc.php 文档一致 */
    private static final List<String> ZEVOST_TYPES = List.of(
            "qq", "wx", "alipay", "sina", "baidu", "douyin", "huawei", "xiaomi",
            "google", "microsoft", "twitter", "dingtalk", "gitee", "github");

    private final RuntimeConfigService runtimeConfigService;
    private final WebClient.Builder webClientBuilder;
    private final ObjectMapper objectMapper;
    private final AuthService authService;
    private final OAuthLinkedUserService oauthLinkedUserService;
    private final OAuthStateSidStore oauthStateSidStore;

    @Autowired
    public PortalOAuthService(
            RuntimeConfigService runtimeConfigService,
            WebClient.Builder webClientBuilder,
            ObjectMapper objectMapper,
            AuthService authService,
            OAuthLinkedUserService oauthLinkedUserService,
            OAuthStateSidStore oauthStateSidStore) {
        this.runtimeConfigService = runtimeConfigService;
        this.webClientBuilder = webClientBuilder;
        this.objectMapper = objectMapper;
        this.authService = authService;
        this.oauthLinkedUserService = oauthLinkedUserService;
        this.oauthStateSidStore = oauthStateSidStore;
    }

    public OauthLoginOptionsVO loginOptions() {
        OauthLoginOptionsVO vo = new OauthLoginOptionsVO();
        vo.setLinuxdoEnabled(runtimeConfigService.getBoolean("auth.oauth.linuxdo.enabled", false));
        vo.setGithubEnabled(runtimeConfigService.getBoolean("auth.oauth.github.enabled", false));
        vo.setGoogleEnabled(runtimeConfigService.getBoolean("auth.oauth.google.enabled", false));
        vo.setWechatEnabled(runtimeConfigService.getBoolean("auth.oauth.wechat.enabled", false));
        vo.setQqEnabled(runtimeConfigService.getBoolean("auth.oauth.qq.enabled", false));

        vo.setZevostEnabled(runtimeConfigService.getBoolean("auth.oauth.zevost.enabled", false));
        String zAppId = runtimeConfigService.getString("auth.oauth.zevost.app-id", "").trim();
        String zKey = runtimeConfigService.getString("auth.oauth.zevost.app-key", "").trim();
        boolean zCred = StringUtils.hasText(zAppId) && StringUtils.hasText(zKey);
        Map<String, Boolean> zmap = new LinkedHashMap<>();
        for (String t : ZEVOST_TYPES) {
            boolean typeOn = vo.isZevostEnabled() && zCred
                    && runtimeConfigService.getBoolean("auth.oauth.zevost.type." + t + ".enabled", false);
            zmap.put(t, typeOn);
        }
        vo.setZevostTypes(zmap);
        return vo;
    }

    /** 跳转知我云聚合登录页（文档 Step1→Step2） */
    public void startZevost(String loginTypeRaw, HttpServletRequest request, HttpServletResponse response)
            throws IOException {
        String frontendBase = frontendBaseUrl();
        String lt = loginTypeRaw == null ? "" : loginTypeRaw.toLowerCase(Locale.ROOT).trim();
        if (!isZevostLoginType(lt)) {
            response.sendError(HttpServletResponse.SC_BAD_REQUEST, "不支持的聚合登录方式");
            return;
        }
        try {
            assertZevostTypeEnabled(lt);
            String ticket = oauthStateSidStore.newZevostTicket(lt);
            addZevostTicketCookie(response, request, ticket);
            String redirectUri = zevostCallbackUrl();
            String jump = fetchZevostLoginJumpUrl(lt, redirectUri);
            response.sendRedirect(jump);
        } catch (BusinessException e) {
            clearZevostCookie(response, request);
            response.sendError(HttpServletResponse.SC_BAD_REQUEST, e.getMessage());
        } catch (Exception e) {
            log.warn("zevost start {}", lt, e);
            clearZevostCookie(response, request);
            response.sendRedirect(frontendBase + "/auth/oauth-callback?oauth_error=" + enc("聚合登录发起失败，请稍后重试"));
        }
    }

    /** 知我云回调：{@code type}、{@code code} 由平台追加（文档 Step3→Step4） */
    public void handleZevostCallback(
            String typeParam,
            String code,
            HttpServletRequest request,
            HttpServletResponse response) throws IOException {
        String frontendBase = frontendBaseUrl();
        String ticket = readZevostTicketCookie(request);
        String expected = ticket != null ? oauthStateSidStore.consumeZevostTicket(ticket) : null;
        clearZevostCookie(response, request);
        String lt = typeParam == null ? "" : typeParam.toLowerCase(Locale.ROOT).trim();
        if (expected == null || !expected.equals(lt)) {
            redirectError(response, frontendBase, "登录会话无效或已过期，请重试");
            return;
        }
        if (!StringUtils.hasText(code)) {
            redirectError(response, frontendBase, "授权未完成");
            return;
        }
        try {
            assertZevostTypeEnabled(lt);
            JsonNode userRoot = fetchZevostUserJson(lt, code.trim());
            if (userRoot.path("code").asInt(-1) != 0) {
                redirectError(response, frontendBase, firstNonBlank(text(userRoot, "msg"), "聚合登录失败"));
                return;
            }
            String socialUid = text(userRoot, "social_uid");
            if (!StringUtils.hasText(socialUid)) {
                redirectError(response, frontendBase, "未能识别第三方用户");
                return;
            }
            String nickname = text(userRoot, "nickname");
            String face = text(userRoot, "faceimg");
            String provider = zevostProviderKey(lt);
            OAuthUserProfile profile = new OAuthUserProfile(socialUid, nickname, "", face);
            AuthUser user = oauthLinkedUserService.findOrCreate(provider, profile);
            user = authService.getAuthUserByIdForOAuth(user.getId());
            if (user == null) {
                throw new BusinessException("用户创建失败");
            }
            String sid = oauthStateSidStore.newSidForUser(user.getId());
            response.sendRedirect(frontendBase + "/auth/oauth-callback?sid=" + sid);
        } catch (BusinessException e) {
            log.warn("zevost callback {}: {}", lt, e.getMessage());
            redirectError(response, frontendBase, e.getMessage());
        } catch (Exception e) {
            log.warn("zevost callback {} failed", lt, e);
            redirectError(response, frontendBase, "第三方登录失败，请稍后重试");
        }
    }

    public String buildAuthorizeUrl(String slug) {
        String p = providerFromSlug(slug);
        assertProviderReadyForStart(p);
        String state = oauthStateSidStore.newState();
        String redirectUri = backendCallbackUrl(slug);
        return switch (p) {
            case "LINUXDO" -> buildLinuxDoAuthorizeUrl(state, redirectUri);
            case "GITHUB" -> buildGithubAuthorizeUrl(state, redirectUri);
            case "GOOGLE" -> buildGoogleAuthorizeUrl(state, redirectUri);
            case "WECHAT" -> buildWechatQrConnectUrl(state, redirectUri);
            case "QQ" -> buildQqAuthorizeUrl(state, redirectUri);
            default -> throw new BusinessException("不支持的 OAuth 渠道");
        };
    }

    public void handleCallback(String slug, String code, String state, String oauthError, HttpServletResponse response)
            throws IOException {
        String frontendBase = frontendBaseUrl();
        if (oauthError != null && !oauthError.isBlank()) {
            redirectError(response, frontendBase, "授权被拒绝或已取消");
            return;
        }
        if (!StringUtils.hasText(code) || !StringUtils.hasText(state)) {
            redirectError(response, frontendBase, "授权回调参数不完整");
            return;
        }
        if (!oauthStateSidStore.consumeState(state)) {
            redirectError(response, frontendBase, "登录状态已过期，请重试");
            return;
        }

        String provider = providerFromSlug(slug);
        try {
            OAuthUserProfile profile = switch (provider) {
                case "LINUXDO" -> fetchLinuxDoProfile(code, backendCallbackUrl(slug));
                case "GITHUB" -> fetchGithubProfile(code, backendCallbackUrl(slug));
                case "GOOGLE" -> fetchGoogleProfile(code, backendCallbackUrl(slug));
                case "WECHAT" -> fetchWechatProfile(code);
                case "QQ" -> fetchQqProfile(code, backendCallbackUrl(slug));
                default -> throw new BusinessException("不支持的 OAuth 渠道");
            };
            AuthUser user = oauthLinkedUserService.findOrCreate(provider, profile);
            user = authService.getAuthUserByIdForOAuth(user.getId());
            if (user == null) {
                throw new BusinessException("用户创建失败");
            }
            String sid = oauthStateSidStore.newSidForUser(user.getId());
            response.sendRedirect(frontendBase + "/auth/oauth-callback?sid=" + sid);
        } catch (BusinessException e) {
            log.warn("oauth callback {}: {}", slug, e.getMessage());
            redirectError(response, frontendBase, e.getMessage());
        } catch (Exception e) {
            log.warn("oauth callback {} failed", slug, e);
            redirectError(response, frontendBase, "第三方登录失败，请稍后重试");
        }
    }

    public AuthVO exchangeSid(String sid, String clientIp) {
        if (!StringUtils.hasText(sid)) {
            throw new BusinessException("无效的登录票据");
        }
        OAuthStateSidStore.PendingSid pending = oauthStateSidStore.consumeSid(sid.trim());
        if (pending == null || Instant.now().isAfter(pending.expiresAt())) {
            throw new BusinessException("登录票据已失效，请重新登录");
        }
        AuthUser user = authService.getAuthUserByIdForOAuth(pending.userId());
        return authService.issuePortalSession(user, clientIp);
    }

    public static String providerFromSlug(String slug) {
        if (slug == null || !slug.matches("[a-z]+")) {
            throw new BusinessException("无效的 OAuth 路径");
        }
        String s = slug.toLowerCase(Locale.ROOT);
        if (!SLUGS.contains(s)) {
            throw new BusinessException("不支持的 OAuth 渠道");
        }
        return switch (s) {
            case "linuxdo" -> "LINUXDO";
            case "github" -> "GITHUB";
            case "google" -> "GOOGLE";
            case "wechat" -> "WECHAT";
            case "qq" -> "QQ";
            default -> throw new BusinessException("不支持的 OAuth 渠道");
        };
    }

    private void assertProviderReadyForStart(String provider) {
        String enabledKey = "auth.oauth." + providerKey(provider) + ".enabled";
        if (!runtimeConfigService.getBoolean(enabledKey, false)) {
            throw new BusinessException(displayName(provider) + " 登录未启用");
        }
        String cid = readClientId(provider);
        String csec = readClientSecret(provider);
        String base = runtimeConfigService.getString("auth.oauth.public-base-url", "").trim();
        if (!StringUtils.hasText(cid) || !StringUtils.hasText(csec) || !StringUtils.hasText(base)) {
            throw new BusinessException("请在运营端配置 " + displayName(provider) + " 的 Client ID、Secret 与站点公网根 URL");
        }
    }

    private static String providerKey(String provider) {
        return switch (provider) {
            case "LINUXDO" -> "linuxdo";
            case "GITHUB" -> "github";
            case "GOOGLE" -> "google";
            case "WECHAT" -> "wechat";
            case "QQ" -> "qq";
            default -> "";
        };
    }

    private static String displayName(String provider) {
        return switch (provider) {
            case "LINUXDO" -> "LINUX DO";
            case "GITHUB" -> "GitHub";
            case "GOOGLE" -> "Google";
            case "WECHAT" -> "微信";
            case "QQ" -> "QQ";
            default -> "第三方";
        };
    }

    private String readClientId(String provider) {
        return runtimeConfigService.getString("auth.oauth." + providerKey(provider) + ".client-id", "").trim();
    }

    private String readClientSecret(String provider) {
        return runtimeConfigService.getString("auth.oauth." + providerKey(provider) + ".client-secret", "").trim();
    }

    private String backendCallbackUrl(String slug) {
        return normalizeBase(runtimeConfigService.getString("auth.oauth.public-base-url", ""))
                + "/api/auth/oauth/" + slug.toLowerCase(Locale.ROOT) + "/callback";
    }

    /** User-facing redirects after OAuth use the same site origin as {@code auth.oauth.public-base-url}. */
    private String frontendBaseUrl() {
        return normalizeBase(runtimeConfigService.getString("auth.oauth.public-base-url", ""));
    }

    private static String normalizeBase(String raw) {
        if (!StringUtils.hasText(raw)) {
            return "";
        }
        String s = raw.trim();
        while (s.endsWith("/")) {
            s = s.substring(0, s.length() - 1);
        }
        return s;
    }

    /** 运营端可选：第三方开放平台 https 根（无尾斜杠）；空则用官方默认。 */
    private String oauthPlatformBase(String configKey, String officialOrigin) {
        String v = runtimeConfigService.getString(configKey, "").trim();
        if (!StringUtils.hasText(v)) {
            return normalizeBase(officialOrigin);
        }
        return normalizeBase(v);
    }

    private String linuxDoPlatformRoot() {
        return oauthPlatformBase("auth.oauth.linuxdo.platform-base-url", "https://connect.linux.do");
    }

    private String githubOAuthWebRoot() {
        return oauthPlatformBase("auth.oauth.github.oauth-web-base-url", "https://github.com");
    }

    private String githubRestApiRoot() {
        return oauthPlatformBase("auth.oauth.github.rest-api-base-url", "https://api.github.com");
    }

    private String googleAccountsRoot() {
        return oauthPlatformBase("auth.oauth.google.accounts-base-url", "https://accounts.google.com");
    }

    private String googleTokenRoot() {
        return oauthPlatformBase("auth.oauth.google.token-base-url", "https://oauth2.googleapis.com");
    }

    private String googleUserinfoRoot() {
        return oauthPlatformBase("auth.oauth.google.userinfo-base-url", "https://openidconnect.googleapis.com");
    }

    private String wechatOpenPlatformRoot() {
        return oauthPlatformBase("auth.oauth.wechat.open-platform-base-url", "https://open.weixin.qq.com");
    }

    private String wechatSnsApiRoot() {
        return oauthPlatformBase("auth.oauth.wechat.sns-api-base-url", "https://api.weixin.qq.com");
    }

    private String qqOpenApiRoot() {
        return oauthPlatformBase("auth.oauth.qq.open-api-base-url", "https://graph.qq.com");
    }

    private String zevostPlatformRoot() {
        return oauthPlatformBase("auth.oauth.zevost.platform-base-url", "https://u.zevost.com");
    }

    private String zevostConnectEndpoint() {
        return zevostPlatformRoot() + "/connect.php";
    }

    private static String enc(String v) {
        return URLEncoder.encode(v == null ? "" : v, StandardCharsets.UTF_8);
    }

    private void redirectError(HttpServletResponse response, String frontendBase, String message) throws IOException {
        String q = enc(message == null ? "未知错误" : message);
        response.sendRedirect(frontendBase + "/auth/oauth-callback?oauth_error=" + q);
    }

    private WebClient wc() {
        return webClientBuilder.build();
    }

    // ——— LINUX DO ———

    private String buildLinuxDoAuthorizeUrl(String state, String redirectUri) {
        String cid = readClientId("LINUXDO");
        return linuxDoPlatformRoot() + "/oauth2/authorize"
                + "?client_id=" + enc(cid)
                + "&redirect_uri=" + enc(redirectUri)
                + "&response_type=code"
                + "&scope=" + enc("openid profile email")
                + "&state=" + enc(state);
    }

    private OAuthUserProfile fetchLinuxDoProfile(String code, String redirectUri) throws Exception {
        String cid = readClientId("LINUXDO");
        String csec = readClientSecret("LINUXDO");
        String tokenJson = wc().post()
                .uri(linuxDoPlatformRoot() + "/oauth2/token")
                .contentType(MediaType.APPLICATION_FORM_URLENCODED)
                .body(BodyInserters.fromFormData("grant_type", "authorization_code")
                        .with("code", code)
                        .with("redirect_uri", redirectUri)
                        .with("client_id", cid)
                        .with("client_secret", csec))
                .retrieve()
                .bodyToMono(String.class)
                .block(HTTP_TIMEOUT);
        JsonNode tokenRoot = objectMapper.readTree(tokenJson);
        String accessToken = text(tokenRoot, "access_token");
        if (!StringUtils.hasText(accessToken)) {
            throw new BusinessException("未能获取访问令牌");
        }
        String userJson = wc().get()
                .uri(linuxDoPlatformRoot() + "/api/user")
                .headers(h -> h.setBearerAuth(accessToken))
                .retrieve()
                .bodyToMono(String.class)
                .block(HTTP_TIMEOUT);
        JsonNode profile = objectMapper.readTree(userJson);
        String externalId = externalIdFromIdField(profile);
        String hint = firstNonBlank(text(profile, "login"), text(profile, "username"));
        String email = text(profile, "email");
        String avatar = text(profile, "avatar_url");
        return new OAuthUserProfile(externalId, hint, email, avatar);
    }

    // ——— GitHub ———

    private String buildGithubAuthorizeUrl(String state, String redirectUri) {
        String cid = readClientId("GITHUB");
        return githubOAuthWebRoot() + "/login/oauth/authorize"
                + "?client_id=" + enc(cid)
                + "&redirect_uri=" + enc(redirectUri)
                + "&scope=" + enc("read:user user:email")
                + "&state=" + enc(state);
    }

    private OAuthUserProfile fetchGithubProfile(String code, String redirectUri) throws Exception {
        String cid = readClientId("GITHUB");
        String csec = readClientSecret("GITHUB");
        String body = wc().post()
                .uri(githubOAuthWebRoot() + "/login/oauth/access_token")
                .accept(MediaType.APPLICATION_JSON)
                .contentType(MediaType.APPLICATION_FORM_URLENCODED)
                .header(HttpHeaders.USER_AGENT, UA)
                .body(BodyInserters.fromFormData("client_id", cid)
                        .with("client_secret", csec)
                        .with("code", code)
                        .with("redirect_uri", redirectUri))
                .retrieve()
                .bodyToMono(String.class)
                .block(HTTP_TIMEOUT);
        JsonNode tokenRoot = objectMapper.readTree(body);
        String accessToken = text(tokenRoot, "access_token");
        if (!StringUtils.hasText(accessToken)) {
            throw new BusinessException("GitHub 未能返回访问令牌");
        }
        String userJson = wc().get()
                .uri(githubRestApiRoot() + "/user")
                .header(HttpHeaders.USER_AGENT, UA)
                .headers(h -> h.setBearerAuth(accessToken))
                .header("Accept", "application/vnd.github+json")
                .retrieve()
                .bodyToMono(String.class)
                .block(HTTP_TIMEOUT);
        JsonNode user = objectMapper.readTree(userJson);
        String externalId = externalIdFromIdField(user);
        String login = text(user, "login");
        String email = text(user, "email");
        if (!ValidationUtil.isValidEmail(email)) {
            email = fetchGithubPrimaryEmail(accessToken);
        }
        String avatar = text(user, "avatar_url");
        return new OAuthUserProfile(externalId, login, email, avatar);
    }

    private String fetchGithubPrimaryEmail(String accessToken) {
        try {
            String json = wc().get()
                    .uri(githubRestApiRoot() + "/user/emails")
                    .header(HttpHeaders.USER_AGENT, UA)
                    .headers(h -> h.setBearerAuth(accessToken))
                    .header("Accept", "application/vnd.github+json")
                    .retrieve()
                    .bodyToMono(String.class)
                    .block(HTTP_TIMEOUT);
            JsonNode arr = objectMapper.readTree(json);
            if (arr != null && arr.isArray()) {
                for (JsonNode n : arr) {
                    if (n.path("primary").asBoolean(false) && n.path("verified").asBoolean(false)) {
                        String e = text(n, "email");
                        if (ValidationUtil.isValidEmail(e)) {
                            return e;
                        }
                    }
                }
                for (JsonNode n : arr) {
                    String e = text(n, "email");
                    if (ValidationUtil.isValidEmail(e)) {
                        return e;
                    }
                }
            }
        } catch (Exception e) {
            log.debug("github emails skip: {}", e.toString());
        }
        return "";
    }

    // ——— Google ———

    private String buildGoogleAuthorizeUrl(String state, String redirectUri) {
        String cid = readClientId("GOOGLE");
        return googleAccountsRoot() + "/o/oauth2/v2/auth"
                + "?client_id=" + enc(cid)
                + "&redirect_uri=" + enc(redirectUri)
                + "&response_type=code"
                + "&scope=" + enc("openid email profile")
                + "&state=" + enc(state)
                + "&access_type=offline"
                + "&prompt=select_account";
    }

    private OAuthUserProfile fetchGoogleProfile(String code, String redirectUri) throws Exception {
        String cid = readClientId("GOOGLE");
        String csec = readClientSecret("GOOGLE");
        String tokenJson = wc().post()
                .uri(googleTokenRoot() + "/token")
                .contentType(MediaType.APPLICATION_FORM_URLENCODED)
                .body(BodyInserters.fromFormData("grant_type", "authorization_code")
                        .with("code", code)
                        .with("redirect_uri", redirectUri)
                        .with("client_id", cid)
                        .with("client_secret", csec))
                .retrieve()
                .bodyToMono(String.class)
                .block(HTTP_TIMEOUT);
        JsonNode tokenRoot = objectMapper.readTree(tokenJson);
        String accessToken = text(tokenRoot, "access_token");
        if (!StringUtils.hasText(accessToken)) {
            throw new BusinessException("Google 未能返回访问令牌");
        }
        String userJson = wc().get()
                .uri(googleUserinfoRoot() + "/v1/userinfo")
                .headers(h -> h.setBearerAuth(accessToken))
                .retrieve()
                .bodyToMono(String.class)
                .block(HTTP_TIMEOUT);
        JsonNode u = objectMapper.readTree(userJson);
        String sub = text(u, "sub");
        if (!StringUtils.hasText(sub)) {
            throw new BusinessException("Google 用户信息缺少 sub");
        }
        String name = firstNonBlank(text(u, "name"), text(u, "given_name"));
        String email = text(u, "email");
        String picture = text(u, "picture");
        return new OAuthUserProfile(sub, name, email, picture);
    }

    // ——— WeChat 网站应用扫码 ———

    private String buildWechatQrConnectUrl(String state, String redirectUri) {
        String appId = readClientId("WECHAT");
        return wechatOpenPlatformRoot() + "/connect/qrconnect?appid=" + enc(appId)
                + "&redirect_uri=" + enc(redirectUri)
                + "&response_type=code"
                + "&scope=snsapi_login"
                + "&state=" + enc(state)
                + "#wechat_redirect";
    }

    private OAuthUserProfile fetchWechatProfile(String code) throws Exception {
        String appId = readClientId("WECHAT");
        String secret = readClientSecret("WECHAT");
        String url = wechatSnsApiRoot() + "/sns/oauth2/access_token?appid=" + enc(appId)
                + "&secret=" + enc(secret)
                + "&code=" + enc(code)
                + "&grant_type=authorization_code";
        String tokenJson = wc().get().uri(url).retrieve().bodyToMono(String.class).block(HTTP_TIMEOUT);
        JsonNode tr = objectMapper.readTree(tokenJson);
        if (tr.has("errcode") && tr.path("errcode").asInt(0) != 0) {
            throw new BusinessException("微信授权失败：" + text(tr, "errmsg"));
        }
        String accessToken = text(tr, "access_token");
        String openid = text(tr, "openid");
        String unionid = text(tr, "unionid");
        if (!StringUtils.hasText(accessToken) || !StringUtils.hasText(openid)) {
            throw new BusinessException("微信未能返回令牌");
        }
        String externalId = StringUtils.hasText(unionid) ? unionid : openid;
        String infoUrl = wechatSnsApiRoot() + "/sns/userinfo?access_token=" + enc(accessToken)
                + "&openid=" + enc(openid)
                + "&lang=zh_CN";
        String infoJson = wc().get().uri(infoUrl).retrieve().bodyToMono(String.class).block(HTTP_TIMEOUT);
        JsonNode info = objectMapper.readTree(infoJson);
        if (info.has("errcode") && info.path("errcode").asInt(0) != 0) {
            throw new BusinessException("获取微信用户信息失败");
        }
        String nick = text(info, "nickname");
        String head = text(info, "headimgurl");
        return new OAuthUserProfile(externalId, nick, "", head);
    }

    // ——— QQ ———

    private String buildQqAuthorizeUrl(String state, String redirectUri) {
        String appId = readClientId("QQ");
        return qqOpenApiRoot() + "/oauth2.0/authorize?response_type=code"
                + "&client_id=" + enc(appId)
                + "&redirect_uri=" + enc(redirectUri)
                + "&state=" + enc(state)
                + "&scope=get_user_info";
    }

    private OAuthUserProfile fetchQqProfile(String code, String redirectUri) throws Exception {
        String appId = readClientId("QQ");
        String appKey = readClientSecret("QQ");
        String tokenUrl = qqOpenApiRoot() + "/oauth2.0/token?grant_type=authorization_code"
                + "&client_id=" + enc(appId)
                + "&client_secret=" + enc(appKey)
                + "&code=" + enc(code)
                + "&redirect_uri=" + enc(redirectUri)
                + "&fmt=json";
        String tokenJson = wc().get().uri(tokenUrl).retrieve().bodyToMono(String.class).block(HTTP_TIMEOUT);
        JsonNode tr = objectMapper.readTree(tokenJson);
        if (tr.has("error")) {
            throw new BusinessException("QQ 授权失败");
        }
        String accessToken = text(tr, "access_token");
        if (!StringUtils.hasText(accessToken)) {
            throw new BusinessException("QQ 未能返回访问令牌");
        }
        String meUrl = qqOpenApiRoot() + "/oauth2.0/me?access_token=" + enc(accessToken) + "&fmt=json";
        String meBody = wc().get().uri(meUrl).retrieve().bodyToMono(String.class).block(HTTP_TIMEOUT);
        String openid = parseQqOpenid(meBody);
        if (!StringUtils.hasText(openid)) {
            throw new BusinessException("QQ 未能解析 openid");
        }
        String userUrl = qqOpenApiRoot() + "/user/get_user_info?access_token=" + enc(accessToken)
                + "&oauth_consumer_key=" + enc(appId)
                + "&openid=" + enc(openid)
                + "&fmt=json";
        String userJson = wc().get().uri(userUrl).retrieve().bodyToMono(String.class).block(HTTP_TIMEOUT);
        JsonNode u = objectMapper.readTree(userJson);
        if (u.path("ret").asInt(0) != 0) {
            throw new BusinessException("QQ 用户信息：" + text(u, "msg"));
        }
        String nick = text(u, "nickname");
        String figure = firstNonBlank(text(u, "figureurl_qq_2"), text(u, "figureurl_qq_1"), text(u, "figureurl"));
        return new OAuthUserProfile(openid, nick, "", figure);
    }

    private static final Pattern QQ_OPENID = Pattern.compile("\"openid\"\\s*:\\s*\"([^\"]+)\"");

    private static boolean isZevostLoginType(String lt) {
        return lt != null && ZEVOST_TYPES.contains(lt);
    }

    private static String zevostProviderKey(String loginTypeLower) {
        return "ZEVOST_" + loginTypeLower.toUpperCase(Locale.ROOT);
    }

    private String zevostCallbackUrl() {
        return normalizeBase(runtimeConfigService.getString("auth.oauth.public-base-url", ""))
                + "/api/auth/oauth/zevost/callback";
    }

    private void assertZevostTypeEnabled(String loginTypeLower) {
        if (!runtimeConfigService.getBoolean("auth.oauth.zevost.enabled", false)) {
            throw new BusinessException("知我云聚合登录未启用");
        }
        if (!runtimeConfigService.getBoolean("auth.oauth.zevost.type." + loginTypeLower + ".enabled", false)) {
            throw new BusinessException("该聚合登录方式未启用");
        }
        String appId = runtimeConfigService.getString("auth.oauth.zevost.app-id", "").trim();
        String appKey = runtimeConfigService.getString("auth.oauth.zevost.app-key", "").trim();
        String base = runtimeConfigService.getString("auth.oauth.public-base-url", "").trim();
        if (!StringUtils.hasText(appId) || !StringUtils.hasText(appKey) || !StringUtils.hasText(base)) {
            throw new BusinessException("请在运营端配置知我云 AppID、AppKey 与站点公网根 URL");
        }
    }

    private String fetchZevostLoginJumpUrl(String loginTypeLower, String redirectUri) throws Exception {
        String appId = runtimeConfigService.getString("auth.oauth.zevost.app-id", "").trim();
        String appKey = runtimeConfigService.getString("auth.oauth.zevost.app-key", "").trim();
        String url = zevostConnectEndpoint() + "?act=login"
                + "&appid=" + enc(appId)
                + "&appkey=" + enc(appKey)
                + "&type=" + enc(loginTypeLower)
                + "&redirect_uri=" + enc(redirectUri);
        String json = wc().get()
                .uri(url)
                .header(HttpHeaders.USER_AGENT, UA)
                .retrieve()
                .bodyToMono(String.class)
                .block(HTTP_TIMEOUT);
        JsonNode root = objectMapper.readTree(json == null ? "{}" : json);
        if (root.path("code").asInt(-1) != 0) {
            throw new BusinessException(firstNonBlank(text(root, "msg"), "聚合接口返回失败"));
        }
        String jump = text(root, "url");
        if (!StringUtils.hasText(jump)) {
            throw new BusinessException("聚合接口未返回跳转地址");
        }
        return jump.trim();
    }

    private JsonNode fetchZevostUserJson(String loginTypeLower, String code) throws Exception {
        String appId = runtimeConfigService.getString("auth.oauth.zevost.app-id", "").trim();
        String appKey = runtimeConfigService.getString("auth.oauth.zevost.app-key", "").trim();
        String url = zevostConnectEndpoint() + "?act=callback"
                + "&appid=" + enc(appId)
                + "&appkey=" + enc(appKey)
                + "&type=" + enc(loginTypeLower)
                + "&code=" + enc(code);
        String json = wc().get()
                .uri(url)
                .header(HttpHeaders.USER_AGENT, UA)
                .retrieve()
                .bodyToMono(String.class)
                .block(HTTP_TIMEOUT);
        return objectMapper.readTree(json == null ? "{}" : json);
    }

    private static void addZevostTicketCookie(HttpServletResponse response, HttpServletRequest request, String ticket) {
        Cookie c = new Cookie(OAuthStateSidStore.ZEVOST_COOKIE_NAME, ticket);
        c.setPath("/");
        c.setHttpOnly(true);
        c.setMaxAge(600);
        if (request != null && request.isSecure()) {
            c.setSecure(true);
        }
        response.addCookie(c);
    }

    private static String readZevostTicketCookie(HttpServletRequest request) {
        if (request.getCookies() == null) {
            return null;
        }
        for (Cookie c : request.getCookies()) {
            if (OAuthStateSidStore.ZEVOST_COOKIE_NAME.equals(c.getName())
                    && StringUtils.hasText(c.getValue())) {
                return c.getValue().trim();
            }
        }
        return null;
    }

    private static void clearZevostCookie(HttpServletResponse response, HttpServletRequest request) {
        Cookie c = new Cookie(OAuthStateSidStore.ZEVOST_COOKIE_NAME, "");
        c.setPath("/");
        c.setMaxAge(0);
        if (request != null && request.isSecure()) {
            c.setSecure(true);
        }
        response.addCookie(c);
    }

    private String parseQqOpenid(String meBody) throws Exception {
        if (meBody == null) {
            return "";
        }
        String t = meBody.trim();
        if (t.startsWith("{")) {
            return objectMapper.readTree(t).path("openid").asText("");
        }
        Matcher m = QQ_OPENID.matcher(meBody);
        return m.find() ? m.group(1) : "";
    }

    private static String externalIdFromIdField(JsonNode profile) {
        if (profile == null) {
            throw new BusinessException("第三方用户信息为空");
        }
        JsonNode idNode = profile.get("id");
        if (idNode == null || idNode.isNull()) {
            throw new BusinessException("第三方用户缺少 id");
        }
        if (idNode.isNumber()) {
            return String.valueOf(idNode.asLong());
        }
        String s = idNode.asText("");
        if (StringUtils.hasText(s)) {
            return s.trim();
        }
        throw new BusinessException("第三方用户 id 无效");
    }

    private static String firstNonBlank(String a, String b) {
        if (StringUtils.hasText(a)) {
            return a.trim();
        }
        if (StringUtils.hasText(b)) {
            return b.trim();
        }
        return "";
    }

    private static String firstNonBlank(String a, String b, String c) {
        if (StringUtils.hasText(a)) {
            return a.trim();
        }
        if (StringUtils.hasText(b)) {
            return b.trim();
        }
        if (StringUtils.hasText(c)) {
            return c.trim();
        }
        return "";
    }

    private static String text(JsonNode n, String field) {
        if (n == null || !n.has(field) || n.get(field).isNull()) {
            return "";
        }
        return n.get(field).asText("").trim();
    }
}
