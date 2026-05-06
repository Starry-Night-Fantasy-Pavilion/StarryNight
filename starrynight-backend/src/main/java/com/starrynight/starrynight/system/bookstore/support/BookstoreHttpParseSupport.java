package com.starrynight.starrynight.system.bookstore.support;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import lombok.Getter;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreBook;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;
import org.jsoup.safety.Safelist;
import org.springframework.http.HttpHeaders;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Component;
import org.springframework.util.StringUtils;
import org.springframework.web.reactive.function.client.WebClient;
import org.springframework.web.util.HtmlUtils;

import java.net.URI;
import java.nio.charset.Charset;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.util.ArrayList;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Locale;

/**
 * 书源 HTTP 拉取与 HTML 解析（同步入库与实时 API 共用）。
 */
@Slf4j
@Component
@RequiredArgsConstructor
public class BookstoreHttpParseSupport {

    @Getter
    private final ObjectMapper objectMapper;

    private static final Duration HTTP_TIMEOUT = Duration.ofSeconds(45);
    private static final String DEFAULT_UA =
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";

    private final WebClient.Builder webClientBuilder;

    public byte[] httpGetBytes(String url, String userAgent) {
        BookstoreUrlGuards.assertSafeHttpUrl(url);
        String ua = StringUtils.hasText(userAgent) ? userAgent.trim() : DEFAULT_UA;
        WebClient wc = webClientBuilder.build();
        try {
            return wc.get()
                    .uri(URI.create(url))
                    .header(HttpHeaders.USER_AGENT, ua)
                    .header(HttpHeaders.ACCEPT, "text/html,application/xhtml+xml;q=0.9,*/*;q=0.8")
                    .header(HttpHeaders.ACCEPT_LANGUAGE, "zh-CN,zh;q=0.9,en;q=0.8")
                    .retrieve()
                    .bodyToMono(byte[].class)
                    .block(HTTP_TIMEOUT);
        } catch (Exception e) {
            log.warn("HTTP GET failed: {}", url, e);
            throw new BusinessException("拉取书源失败: " + e.getMessage());
        }
    }

    /** 二进制拉取（图片代理），带 Content-Type；URL 须已做安全校验 */
    public ResponseEntity<byte[]> httpGetBinaryEntity(String url, String userAgent) {
        String ua = StringUtils.hasText(userAgent) ? userAgent.trim() : DEFAULT_UA;
        WebClient wc = webClientBuilder.build();
        try {
            return wc.get()
                    .uri(URI.create(url))
                    .header(HttpHeaders.USER_AGENT, ua)
                    .header(HttpHeaders.ACCEPT, "image/*,*/*;q=0.8")
                    .retrieve()
                    .toEntity(byte[].class)
                    .block(HTTP_TIMEOUT);
        } catch (Exception e) {
            log.warn("HTTP GET failed: {}", url, e);
            throw new BusinessException("拉取资源失败: " + e.getMessage());
        }
    }

    public String decodeHtml(byte[] bytes, String encodingHint, String urlForMeta) {
        if (bytes == null || bytes.length == 0) {
            return "";
        }
        if (StringUtils.hasText(encodingHint)) {
            try {
                return new String(bytes, Charset.forName(encodingHint.trim()));
            } catch (Exception e) {
                log.warn("encoding {} invalid, fallback UTF-8", encodingHint);
            }
        }
        String utf8 = new String(bytes, StandardCharsets.UTF_8);
        if (!utf8.contains("\uFFFD")) {
            return utf8;
        }
        try {
            return new String(bytes, Charset.forName("GBK"));
        } catch (Exception e) {
            return utf8;
        }
    }

    /** 拉取解析用的基准 URL：规则 JSON 中的 tocUrl 等优先，否则 {@link BookstoreBook#getSourceUrl()} */
    public String resolveTocUrl(BookstoreBook book) {
        String json = book.getSourceJson();
        if (StringUtils.hasText(json)) {
            try {
                JsonNode n = objectMapper.readTree(json.trim());
                String fromJson = extractTocUrlFromRulesJson(n);
                if (StringUtils.hasText(fromJson)) {
                    return fromJson.trim();
                }
            } catch (Exception ignored) {
                /* fall through */
            }
        }
        if (StringUtils.hasText(book.getSourceUrl())) {
            return book.getSourceUrl().trim();
        }
        throw new BusinessException("该书未配置书源 URL（或规则 JSON 中的目录地址字段，如 tocUrl、bookUrl）");
    }

    private static String extractTocUrlFromRulesJson(JsonNode n) {
        String[] keys = {"tocUrl", "catalogUrl", "chapterListUrl", "bookUrl", "detailUrl"};
        for (String key : keys) {
            if (n.hasNonNull(key) && n.get(key).isTextual()) {
                String v = n.get(key).asText();
                if (StringUtils.hasText(v)) {
                    return v.trim();
                }
            }
        }
        if (n.has("ruleBookInfo") && n.get("ruleBookInfo").isObject()) {
            JsonNode rbi = n.get("ruleBookInfo");
            if (rbi.hasNonNull("tocUrl") && rbi.get("tocUrl").isTextual()) {
                String v = rbi.get("tocUrl").asText().trim();
                if (StringUtils.hasText(v)) {
                    int nl = v.indexOf('\n');
                    return nl > 0 ? v.substring(0, nl).trim() : v;
                }
            }
        }
        return null;
    }

    public static boolean isHttpUrl(String s) {
        if (!StringUtils.hasText(s)) {
            return false;
        }
        String t = s.trim();
        return t.startsWith("http://") || t.startsWith("https://");
    }

    /**
     * 文档：请求 {@code url} 可为绝对地址，或相对书源内的 http 基准拼接。
     * 无 {@code requestUrlParam} 时尝试从 Legado JSON / bookSourceUrl 得到 http 目录或详情页。
     */
    public String resolveCatalogUrlForLive(String sourceJson, String legadoBookSourceUrl, String requestUrlParam) {
        if (StringUtils.hasText(requestUrlParam)) {
            String t = requestUrlParam.trim();
            if (isHttpUrl(t)) {
                return t;
            }
            String base = firstHttpBaseFromLegado(sourceJson, legadoBookSourceUrl);
            if (base != null) {
                return absolutizeRelativeToBase(base, t);
            }
            throw new BusinessException("相对路径缺少书源内的 http(s) 基准，请传入绝对 url 或换用书源");
        }
        String fromLegado = firstHttpBaseFromLegado(sourceJson, legadoBookSourceUrl);
        if (fromLegado != null) {
            return fromLegado;
        }
        throw new BusinessException("请传入 url 参数（作品详情/目录页完整地址），该书源 bookSourceUrl 非 http 且无可用规则 URL");
    }

    public String firstHttpBaseFromLegado(String sourceJson, String legadoBookSourceUrl) {
        if (isHttpUrl(legadoBookSourceUrl)) {
            return legadoBookSourceUrl.trim();
        }
        if (!StringUtils.hasText(sourceJson)) {
            return null;
        }
        try {
            JsonNode n = objectMapper.readTree(sourceJson.trim());
            String u = extractTocUrlFromRulesJson(n);
            if (isHttpUrl(u)) {
                return u.trim();
            }
        } catch (Exception ignored) {
            /* ignore */
        }
        return null;
    }

    public String resolveChapterPageUrlForLive(String sourceJson, String siteBaseUrl, String chapterHref) {
        if (!StringUtils.hasText(chapterHref)) {
            throw new BusinessException("缺少章节 url");
        }
        String t = chapterHref.trim();
        if (isHttpUrl(t)) {
            return t;
        }
        String base = firstHttpBaseFromLegado(sourceJson, siteBaseUrl);
        if (!isHttpUrl(base) && isHttpUrl(siteBaseUrl)) {
            base = siteBaseUrl.trim();
        }
        if (!isHttpUrl(base)) {
            throw new BusinessException("章节相对地址缺少 http(s) 书源基准");
        }
        return absolutizeRelativeToBase(base, t);
    }

    private static String absolutizeRelativeToBase(String base, String pathOrRelative) {
        String t = pathOrRelative.trim();
        if (t.startsWith("http://") || t.startsWith("https://")) {
            return t;
        }
        String path = t.startsWith("/") ? t : "/" + t;
        return absolutize(base.trim(), path);
    }

    public List<BookstoreTocLink> extractTocLinks(Document doc, List<String> selectors) {
        LinkedHashSet<BookstoreTocLink> out = new LinkedHashSet<>();
        List<String> use = selectors != null && !selectors.isEmpty() ? selectors : BookstoreParseRules.DEFAULT_TOC_SELECTORS;
        for (String sel : use) {
            Elements els = doc.select(sel);
            for (Element a : els) {
                String href = a.attr("href");
                if (!StringUtils.hasText(href)
                        || href.startsWith("#")
                        || href.toLowerCase(Locale.ROOT).startsWith("javascript:")) {
                    continue;
                }
                String text = a.text();
                out.add(new BookstoreTocLink(href.trim(), text));
            }
            if (!out.isEmpty()) {
                break;
            }
        }
        return new ArrayList<>(out);
    }

    public String extractContentHtml(Document doc, List<String> selectors) {
        List<String> use = selectors != null && !selectors.isEmpty() ? selectors : BookstoreParseRules.DEFAULT_CONTENT_SELECTORS;
        for (String sel : use) {
            Element el = doc.selectFirst(sel);
            if (el != null) {
                el.select("script, style, iframe").remove();
                return el.html();
            }
        }
        Element body = doc.body();
        return body != null ? body.html() : "";
    }

    public String sanitizeChapterHtml(String raw) {
        if (!StringUtils.hasText(raw)) {
            return "<p></p>";
        }
        String cleaned = Jsoup.clean(raw, "", Safelist.relaxed());
        if (!StringUtils.hasText(cleaned)) {
            return "<p></p>";
        }
        if (!cleaned.contains("<p") && !cleaned.contains("<div")) {
            String plain = Jsoup.parse(raw).text();
            String[] paras = plain.split("\\s*\\n\\s*");
            StringBuilder sb = new StringBuilder();
            for (String p : paras) {
                if (!StringUtils.hasText(p)) {
                    continue;
                }
                sb.append("<p style=\"text-indent:2em;margin-bottom:1em;\">")
                        .append(HtmlUtils.htmlEscape(p.trim()).replace("\n", "<br/>"))
                        .append("</p>");
            }
            return sb.length() > 0 ? sb.toString() : "<p>" + HtmlUtils.htmlEscape(plain.trim()) + "</p>";
        }
        return cleaned;
    }

    public static int countPlainChars(String html) {
        if (!StringUtils.hasText(html)) {
            return 0;
        }
        String plain = Jsoup.parse(html).text();
        return plain.replaceAll("\\s+", "").length();
    }

    public static String absolutize(String base, String href) {
        try {
            URI b = URI.create(base);
            URI r = b.resolve(href);
            return r.toASCIIString();
        } catch (Exception e) {
            return href;
        }
    }
}
