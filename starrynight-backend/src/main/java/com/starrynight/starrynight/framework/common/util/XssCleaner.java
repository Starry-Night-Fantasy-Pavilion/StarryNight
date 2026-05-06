package com.starrynight.starrynight.framework.common.util;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.node.TextNode;
import com.fasterxml.jackson.databind.node.ArrayNode;
import com.fasterxml.jackson.databind.node.ObjectNode;
import org.springframework.stereotype.Component;

import java.util.Iterator;
import java.util.Locale;
import java.util.Map;
import java.util.Set;
import java.util.regex.Pattern;

@Component
public class XssCleaner {

    private static final Pattern[] XSS_PATTERNS = {
            Pattern.compile("<script>(.*?)</script>", Pattern.CASE_INSENSITIVE),
            Pattern.compile("src[\r\n]*=[\r\n]*\\\'(.*?)\\\'", Pattern.CASE_INSENSITIVE | Pattern.MULTILINE | Pattern.DOTALL),
            Pattern.compile("src[\r\n]*=[\r\n]*\\\"(.*?)\\\"", Pattern.CASE_INSENSITIVE | Pattern.MULTILINE | Pattern.DOTALL),
            Pattern.compile("</script>", Pattern.CASE_INSENSITIVE),
            Pattern.compile("<script(.*?)>", Pattern.CASE_INSENSITIVE | Pattern.MULTILINE | Pattern.DOTALL),
            Pattern.compile("eval\\((.*?)\\)", Pattern.CASE_INSENSITIVE | Pattern.MULTILINE | Pattern.DOTALL),
            Pattern.compile("expression\\((.*?)\\)", Pattern.CASE_INSENSITIVE | Pattern.MULTILINE | Pattern.DOTALL),
            Pattern.compile("javascript:", Pattern.CASE_INSENSITIVE),
            Pattern.compile("vbscript:", Pattern.CASE_INSENSITIVE),
            Pattern.compile("onload(.*?)=", Pattern.CASE_INSENSITIVE | Pattern.MULTILINE | Pattern.DOTALL),
            Pattern.compile("<iframe(.*?)>(.*?)</iframe>", Pattern.CASE_INSENSITIVE | Pattern.MULTILINE | Pattern.DOTALL),
            Pattern.compile("onerror(.*?)=", Pattern.CASE_INSENSITIVE | Pattern.MULTILINE | Pattern.DOTALL),
            Pattern.compile("onclick(.*?)=", Pattern.CASE_INSENSITIVE | Pattern.MULTILINE | Pattern.DOTALL),
            Pattern.compile("onmouseover(.*?)=", Pattern.CASE_INSENSITIVE | Pattern.MULTILINE | Pattern.DOTALL)
    };

    private static final ObjectMapper objectMapper = new ObjectMapper();

    /**
     * Request headers whose values are used by the servlet container / Spring as raw HTTP
     * (URLs, MIME types, tokens, forwarding chain). HTML-encoding slashes breaks CORS,
     * {@code Content-Type} parsing, and proxies.
     */
    private static final Set<String> PASSTHROUGH_REQUEST_HEADER_NAMES = Set.of(
            "origin",
            "referer",
            "host",
            "content-type",
            "accept",
            "accept-language",
            "accept-encoding",
            "accept-charset",
            "authorization",
            "proxy-authorization",
            "cookie",
            "forwarded",
            "x-forwarded-for",
            "x-forwarded-host",
            "x-forwarded-proto",
            "x-forwarded-port",
            "x-forwarded-prefix",
            "x-forwarded-uri",
            "x-real-ip",
            "true-client-ip"
    );

    public static boolean isPassthroughRequestHeader(String headerName) {
        if (headerName == null) {
            return false;
        }
        return PASSTHROUGH_REQUEST_HEADER_NAMES.contains(headerName.trim().toLowerCase(Locale.ROOT));
    }

    public static String sanitizeRequestHeaderValue(String headerName, String value) {
        if (value == null || value.isEmpty()) {
            return value;
        }
        if (isPassthroughRequestHeader(headerName)) {
            return value;
        }
        return cleanString(value);
    }

    public static String cleanJson(String json) {
        if (json == null || json.isEmpty()) {
            return json;
        }

        try {
            ObjectNode rootNode = (ObjectNode) objectMapper.readTree(json);
            cleanNode(rootNode);
            return objectMapper.writeValueAsString(rootNode);
        } catch (Exception e) {
            return cleanString(json);
        }
    }

    private static void cleanNode(ObjectNode node) {
        Iterator<Map.Entry<String, com.fasterxml.jackson.databind.JsonNode>> fields = node.fields();
        while (fields.hasNext()) {
            Map.Entry<String, com.fasterxml.jackson.databind.JsonNode> field = fields.next();
            com.fasterxml.jackson.databind.JsonNode value = field.getValue();

            if (value instanceof TextNode) {
                String cleaned = cleanString(value.asText());
                node.put(field.getKey(), cleaned);
            } else if (value instanceof ObjectNode) {
                cleanNode((ObjectNode) value);
            } else if (value instanceof ArrayNode) {
                cleanArray((ArrayNode) value);
            }
        }
    }

    private static void cleanArray(ArrayNode array) {
        for (int i = 0; i < array.size(); i++) {
            com.fasterxml.jackson.databind.JsonNode element = array.get(i);
            if (element instanceof TextNode) {
                array.set(i, new TextNode(cleanString(element.asText())));
            } else if (element instanceof ObjectNode) {
                cleanNode((ObjectNode) element);
            } else if (element instanceof ArrayNode) {
                cleanArray((ArrayNode) element);
            }
        }
    }

    public static String cleanString(String value) {
        if (value == null || value.isEmpty()) {
            return value;
        }

        String cleaned = value;
        for (Pattern pattern : XSS_PATTERNS) {
            cleaned = pattern.matcher(cleaned).replaceAll("");
        }

        cleaned = cleaned.replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll("\"", "&quot;")
                .replaceAll("'", "&#x27;")
                .replaceAll("/", "&#x2F;");

        return cleaned;
    }
}