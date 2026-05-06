package com.starrynight.starrynight.system.bookstore.support;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.util.StringUtils;

import java.util.ArrayList;
import java.util.List;

/**
 * 书源 HTML 解析规则（与 {@code bookstore_book.source_json} 及 Legado 纯 CSS 子集一致）。
 */
public final class BookstoreParseRules {

    public static final List<String> DEFAULT_TOC_SELECTORS = List.of(
            "#list dd > a",
            "#list a",
            ".listmain dd > a",
            ".listmain a",
            "dl dd > a",
            "#chapterlist a",
            ".chapterlist a",
            "table.dccss a");

    public static final List<String> DEFAULT_CONTENT_SELECTORS = List.of(
            "#content",
            "#chaptercontent",
            "#nr1",
            ".showtxt",
            ".content_txt",
            "#BookText",
            "#htmlContent",
            "article");

    private List<String> tocSelectors = DEFAULT_TOC_SELECTORS;
    private List<String> contentSelectors = DEFAULT_CONTENT_SELECTORS;
    private String encoding;
    private String userAgent;

    public List<String> getTocSelectors() {
        return tocSelectors;
    }

    public List<String> getContentSelectors() {
        return contentSelectors;
    }

    public String getEncoding() {
        return encoding;
    }

    public String getUserAgent() {
        return userAgent;
    }

    public static BookstoreParseRules from(String sourceJson, ObjectMapper om) {
        BookstoreParseRules r = new BookstoreParseRules();
        if (!StringUtils.hasText(sourceJson)) {
            return r;
        }
        try {
            JsonNode n = om.readTree(sourceJson.trim());
            boolean explicitTocList =
                    n.has("tocSelectors") && n.get("tocSelectors").isArray() && n.get("tocSelectors").size() > 0;
            boolean explicitContentList = n.has("contentSelectors")
                    && n.get("contentSelectors").isArray()
                    && n.get("contentSelectors").size() > 0;

            if (n.has("tocSelectors") && n.get("tocSelectors").isArray()) {
                List<String> list = new ArrayList<>();
                for (JsonNode x : n.get("tocSelectors")) {
                    if (x.isTextual()) {
                        list.add(x.asText());
                    }
                }
                if (!list.isEmpty()) {
                    r.tocSelectors = list;
                }
            } else if (n.has("tocSelector") && n.get("tocSelector").isTextual()) {
                String one = n.get("tocSelector").asText().trim();
                if (StringUtils.hasText(one)) {
                    r.tocSelectors = List.of(one);
                }
            }

            if (n.has("contentSelectors") && n.get("contentSelectors").isArray()) {
                List<String> list = new ArrayList<>();
                for (JsonNode x : n.get("contentSelectors")) {
                    if (x.isTextual()) {
                        list.add(x.asText());
                    }
                }
                if (!list.isEmpty()) {
                    r.contentSelectors = list;
                }
            } else if (n.has("contentSelector") && n.get("contentSelector").isTextual()) {
                String one = n.get("contentSelector").asText().trim();
                if (StringUtils.hasText(one)) {
                    r.contentSelectors = List.of(one);
                }
            }

            if (n.has("encoding") && n.get("encoding").isTextual()) {
                r.encoding = n.get("encoding").asText();
            }
            if (n.has("userAgent") && n.get("userAgent").isTextual()) {
                r.userAgent = n.get("userAgent").asText();
            }

            if (!explicitTocList && !n.has("tocSelector")) {
                String legadoToc = extractLegadoTocCss(n);
                if (StringUtils.hasText(legadoToc)) {
                    r.tocSelectors = List.of(legadoToc);
                }
            }
            if (!explicitContentList && !n.has("contentSelector")) {
                String legadoContent = extractLegadoContentCss(n);
                if (StringUtils.hasText(legadoContent)) {
                    r.contentSelectors = List.of(legadoContent);
                }
            }
        } catch (Exception ignored) {
            /* 保持默认 */
        }
        return r;
    }

    private static String extractLegadoTocCss(JsonNode root) {
        JsonNode ruleToc = root.get("ruleToc");
        if (ruleToc == null || !ruleToc.isObject()) {
            return null;
        }
        JsonNode chapterList = ruleToc.get("chapterList");
        if (chapterList != null && chapterList.isTextual()) {
            return legadoRuleStringToPlainCss(chapterList.asText());
        }
        return null;
    }

    private static String extractLegadoContentCss(JsonNode root) {
        JsonNode ruleContent = root.get("ruleContent");
        if (ruleContent == null || !ruleContent.isObject()) {
            return null;
        }
        JsonNode content = ruleContent.get("content");
        if (content != null && content.isTextual()) {
            return legadoRuleStringToPlainCss(content.asText());
        }
        return null;
    }

    private static String legadoRuleStringToPlainCss(String rule) {
        if (!StringUtils.hasText(rule)) {
            return null;
        }
        String t = rule.trim();
        if (t.contains("@")) {
            return null;
        }
        return t;
    }
}
