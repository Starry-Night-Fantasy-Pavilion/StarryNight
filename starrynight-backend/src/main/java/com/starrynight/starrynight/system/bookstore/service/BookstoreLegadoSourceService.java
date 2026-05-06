package com.starrynight.starrynight.system.bookstore.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLegadoImportResultDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLegadoSourceAdminDTO;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreBookSource;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreBookSourceMapper;
import com.starrynight.starrynight.system.bookstore.support.BookstoreUrlGuards;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.http.HttpHeaders;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;
import org.springframework.web.reactive.function.client.WebClient;

import java.net.URI;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;

@Slf4j
@Service
@RequiredArgsConstructor
public class BookstoreLegadoSourceService {

    private static final int MAX_IMPORT_BYTES = 25 * 1024 * 1024;
    private static final Duration HTTP_TIMEOUT = Duration.ofSeconds(120);

    private final BookstoreBookSourceMapper bookstoreBookSourceMapper;
    private final ObjectMapper objectMapper;
    private final WebClient.Builder webClientBuilder;

    @Transactional(rollbackFor = Exception.class)
    public BookstoreLegadoImportResultDTO importFromUrl(String url) {
        if (!StringUtils.hasText(url)) {
            throw new BusinessException("缺少 url");
        }
        String u = url.trim();
        BookstoreUrlGuards.assertSafeHttpUrl(u);
        byte[] body = httpGetBytes(u);
        if (body == null || body.length == 0) {
            throw new BusinessException("下载内容为空");
        }
        if (body.length > MAX_IMPORT_BYTES) {
            throw new BusinessException("书源文件过大（上限 25MB）");
        }
        String json = new String(body, StandardCharsets.UTF_8);
        return importJsonArray(json);
    }

    @Transactional(rollbackFor = Exception.class)
    public BookstoreLegadoImportResultDTO importJsonArray(String rawJson) {
        if (!StringUtils.hasText(rawJson)) {
            throw new BusinessException("JSON 为空");
        }
        JsonNode root;
        try {
            root = objectMapper.readTree(rawJson.trim());
        } catch (Exception e) {
            throw new BusinessException("JSON 解析失败: " + e.getMessage());
        }
        if (!root.isArray()) {
            throw new BusinessException("须为 Legado 书源数组（JSON Array）");
        }

        int inserted = 0;
        int updated = 0;
        int skipped = 0;
        List<String> errors = new ArrayList<>();
        int order = 0;

        Iterator<JsonNode> it = root.elements();
        while (it.hasNext()) {
            JsonNode item = it.next();
            order++;
            if (!item.isObject()) {
                skipped++;
                continue;
            }
            JsonNode nameNode = item.get("bookSourceName");
            if (nameNode == null || !nameNode.isTextual() || !StringUtils.hasText(nameNode.asText())) {
                skipped++;
                if (errors.size() < 30) {
                    errors.add("第" + order + "条缺少 bookSourceName，已跳过");
                }
                continue;
            }
            String name = nameNode.asText().trim();
            String srcUrl = "";
            JsonNode urlNode = item.get("bookSourceUrl");
            if (urlNode != null && urlNode.isTextual()) {
                srcUrl = urlNode.asText().trim();
            }
            String group = "";
            JsonNode gNode = item.get("bookSourceGroup");
            if (gNode != null && gNode.isTextual()) {
                group = gNode.asText().trim();
            }
            int enabled = 1;
            JsonNode en = item.get("enabled");
            if (en != null && en.isBoolean() && !en.asBoolean()) {
                enabled = 0;
            }

            String jsonOne;
            try {
                jsonOne = objectMapper.writeValueAsString(item);
            } catch (Exception e) {
                skipped++;
                continue;
            }

            BookstoreBookSource existing =
                    bookstoreBookSourceMapper.selectOne(
                            new LambdaQueryWrapper<BookstoreBookSource>()
                                    .eq(BookstoreBookSource::getBookSourceName, name)
                                    .eq(BookstoreBookSource::getBookSourceUrl, srcUrl)
                                    .last("LIMIT 1"));

            if (existing != null) {
                existing.setBookSourceGroup(group);
                existing.setSourceJson(jsonOne);
                existing.setEnabled(enabled);
                bookstoreBookSourceMapper.updateById(existing);
                updated++;
            } else {
                BookstoreBookSource row = new BookstoreBookSource();
                row.setBookSourceName(name);
                row.setBookSourceUrl(srcUrl);
                row.setBookSourceGroup(group);
                row.setSourceJson(jsonOne);
                row.setEnabled(enabled);
                row.setSortOrder(0);
                bookstoreBookSourceMapper.insert(row);
                inserted++;
            }
        }

        return BookstoreLegadoImportResultDTO.builder()
                .inserted(inserted)
                .updated(updated)
                .skipped(skipped)
                .errors(errors)
                .build();
    }

    public PageVO<BookstoreLegadoSourceAdminDTO> pageAdmin(String keyword, int page, int size) {
        int p = Math.max(1, page);
        int s = Math.min(100, Math.max(1, size));
        LambdaQueryWrapper<BookstoreBookSource> w = new LambdaQueryWrapper<>();
        if (StringUtils.hasText(keyword)) {
            String k = keyword.trim();
            w.and(q -> q.like(BookstoreBookSource::getBookSourceName, k).or().like(BookstoreBookSource::getBookSourceUrl, k));
        }
        w.orderByDesc(BookstoreBookSource::getId);
        com.baomidou.mybatisplus.extension.plugins.pagination.Page<BookstoreBookSource> pg =
                new com.baomidou.mybatisplus.extension.plugins.pagination.Page<>(p, s);
        com.baomidou.mybatisplus.extension.plugins.pagination.Page<BookstoreBookSource> res =
                bookstoreBookSourceMapper.selectPage(pg, w);
        List<BookstoreLegadoSourceAdminDTO> records = new ArrayList<>();
        for (BookstoreBookSource row : res.getRecords()) {
            records.add(toAdminPreview(row));
        }
        return PageVO.of(res.getTotal(), records, res.getCurrent(), res.getSize());
    }

    public void delete(Long id) {
        bookstoreBookSourceMapper.deleteById(id);
    }

    public void setEnabled(Long id, boolean enabled) {
        BookstoreBookSource row = bookstoreBookSourceMapper.selectById(id);
        if (row == null) {
            throw new com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException("书源不存在");
        }
        row.setEnabled(enabled ? 1 : 0);
        bookstoreBookSourceMapper.updateById(row);
    }

    private BookstoreLegadoSourceAdminDTO toAdminPreview(BookstoreBookSource row) {
        boolean hasSearch = false;
        boolean hasToc = false;
        boolean hasContent = false;
        String snippet = "";
        if (StringUtils.hasText(row.getSourceJson())) {
            try {
                JsonNode n = objectMapper.readTree(row.getSourceJson());
                hasSearch = n.has("ruleSearch") && n.get("ruleSearch").isObject() && n.get("ruleSearch").size() > 0;
                hasToc = n.has("ruleToc") && n.get("ruleToc").isObject() && n.get("ruleToc").size() > 0;
                hasContent = n.has("ruleContent") && n.get("ruleContent").isObject() && n.get("ruleContent").size() > 0;
                JsonNode c = n.get("bookSourceComment");
                if (c != null && c.isTextual()) {
                    String t = c.asText().replaceAll("\\s+", " ").trim();
                    snippet = t.length() > 120 ? t.substring(0, 117) + "…" : t;
                }
            } catch (Exception ignored) {
                /* ignore */
            }
        }
        return BookstoreLegadoSourceAdminDTO.builder()
                .id(row.getId())
                .bookSourceName(row.getBookSourceName())
                .bookSourceUrl(row.getBookSourceUrl())
                .bookSourceGroup(row.getBookSourceGroup())
                .enabled(row.getEnabled())
                .hasRuleSearch(hasSearch)
                .hasRuleToc(hasToc)
                .hasRuleContent(hasContent)
                .commentSnippet(snippet)
                .build();
    }

    private byte[] httpGetBytes(String url) {
        WebClient wc = webClientBuilder.build();
        try {
            return wc.get()
                    .uri(URI.create(url))
                    .header(HttpHeaders.USER_AGENT, "Mozilla/5.0 (compatible; StarryNight/1.0)")
                    .header(HttpHeaders.ACCEPT, "application/json, */*;q=0.8")
                    .retrieve()
                    .bodyToMono(byte[].class)
                    .block(HTTP_TIMEOUT);
        } catch (Exception e) {
            log.warn("import legado fetch failed: {}", url, e);
            throw new BusinessException("拉取书源集合失败: " + e.getMessage());
        }
    }
}
