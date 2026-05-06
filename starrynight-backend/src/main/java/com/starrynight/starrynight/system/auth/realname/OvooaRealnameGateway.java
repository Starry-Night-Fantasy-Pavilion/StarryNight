package com.starrynight.starrynight.system.auth.realname;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;
import org.springframework.web.reactive.function.client.WebClient;

import java.time.Duration;
import java.util.regex.Pattern;

/**
 * 喵雨欣开发平台 HTTP 网关（芝麻人脸等可调接口，
 * 文档入口：<a href="https://www.ovooa.cc/apidata?id=4">apidata?id=4</a>）。
 * 配置项命名沿用 {@code auth.realname.ovooa.*}。实际 URL、鉴权与返回字段以控制台为准。
 */
@Service
public class OvooaRealnameGateway {

    private static final Logger log = LoggerFactory.getLogger(OvooaRealnameGateway.class);
    private static final Duration HTTP_TIMEOUT = Duration.ofSeconds(25);

    @Autowired
    private RuntimeConfigService runtimeConfigService;
    @Autowired
    private WebClient.Builder webClientBuilder;
    @Autowired
    private ObjectMapper objectMapper;

    public String invokeForRedirectUrl(long userId, String realName, String idCardNo, String notifyUrl) throws Exception {
        String url = runtimeConfigService.getString("auth.realname.ovooa.invoke-url", "").trim();
        if (!StringUtils.hasText(url)) {
            throw new BusinessException("未配置喵雨欣调用 URL（auth.realname.ovooa.invoke-url）");
        }
        String template = runtimeConfigService
                .getString(
                        "auth.realname.ovooa.invoke-json-template",
                        "{\"real_name\":\"{realName}\",\"id_card\":\"{idCard}\",\"notify_url\":\"{notifyUrl}\",\"user_id\":\"{userId}\"}")
                .trim();
        String body = template
                .replace("{realName}", jsonEscape(realName))
                .replace("{idCard}", jsonEscape(idCardNo))
                .replace("{notifyUrl}", jsonEscape(notifyUrl))
                .replace("{userId}", Long.toString(userId));

        WebClient wc = webClientBuilder.build();
        WebClient.RequestHeadersSpec<?> req =
                wc.post().uri(url).contentType(MediaType.APPLICATION_JSON).bodyValue(body);
        String token = runtimeConfigService.getString("auth.realname.ovooa.api-token", "").trim();
        if (StringUtils.hasText(token)) {
            String authz = token.regionMatches(true, 0, "Bearer ", 0, 7) ? token : "Bearer " + token;
            req = req.header(HttpHeaders.AUTHORIZATION, authz);
        }
        String json = req.retrieve().bodyToMono(String.class).block(HTTP_TIMEOUT);
        JsonNode root = objectMapper.readTree(json == null ? "{}" : json);
        String jump = firstUrlLike(root);
        if (!StringUtils.hasText(jump)) {
            log.warn("ovooa invoke unexpected response: {}", json);
            throw new BusinessException("第三方接口未返回可用的核验跳转地址，请核对控制台文档与 JSON 模板");
        }
        return jump.trim();
    }

    private static String jsonEscape(String s) {
        if (s == null) {
            return "";
        }
        return s.replace("\\", "\\\\").replace("\"", "\\\"");
    }

    private static String firstUrlLike(JsonNode root) {
        if (root == null || root.isNull()) {
            return "";
        }
        String[] keys = {"url", "h5_url", "certify_url", "jump_url", "redirect_url", "link"};
        for (String k : keys) {
            JsonNode n = root.get(k);
            if (n != null && n.isTextual() && StringUtils.hasText(n.asText())) {
                return n.asText();
            }
        }
        JsonNode data = root.get("data");
        if (data != null && data.isObject()) {
            for (String k : keys) {
                JsonNode n = data.get(k);
                if (n != null && n.isTextual() && StringUtils.hasText(n.asText())) {
                    return n.asText();
                }
            }
        }
        JsonNode result = root.get("result");
        if (result != null && result.isObject()) {
            for (String k : keys) {
                JsonNode n = result.get(k);
                if (n != null && n.isTextual() && StringUtils.hasText(n.asText())) {
                    return n.asText();
                }
            }
        }
        return "";
    }

    private static final Pattern HTTP_URL = Pattern.compile("^https?://\\S+$", Pattern.CASE_INSENSITIVE);

    public static boolean looksLikeHttpUrl(String s) {
        return s != null && HTTP_URL.matcher(s.trim()).matches();
    }
}
