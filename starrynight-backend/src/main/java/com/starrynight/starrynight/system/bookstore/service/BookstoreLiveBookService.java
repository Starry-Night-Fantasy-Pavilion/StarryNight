package com.starrynight.starrynight.system.bookstore.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.fasterxml.jackson.databind.JsonNode;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLiveBookApiResponseDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLiveBookPayloadDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLiveChapterApiResponseDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLiveChapterLinkDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLiveChapterNavDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLiveChapterNavPointerDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLiveSourceItemDTO;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreBook;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreBookSource;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreBookMapper;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreBookSourceMapper;
import com.starrynight.starrynight.system.bookstore.support.BookstoreHttpParseSupport;
import com.starrynight.starrynight.system.bookstore.support.BookstoreParseRules;
import com.starrynight.starrynight.system.bookstore.support.BookstoreTocLink;
import com.starrynight.starrynight.system.bookstore.support.BookstoreUrlGuards;
import com.starrynight.starrynight.system.novel.entity.NovelCategory;
import com.starrynight.starrynight.system.novel.mapper.NovelCategoryMapper;
import lombok.RequiredArgsConstructor;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.springframework.http.HttpStatus;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;

import java.net.URI;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;

/**
 * 文档 {@code /api/bookstore/sources}、{@code /api/bookstore/book?sourceId=&url=}、{@code /api/bookstore/chapter}。
 * {@code sourceId} 优先对应导入的 Legado 书源集合表 {@link BookstoreBookSource}；否则回退本站 {@link BookstoreBook}（兼容旧数据）。
 */
@Service
@RequiredArgsConstructor
public class BookstoreLiveBookService {

    private static final int MAX_CHAPTER_LINKS = 3000;

    private final BookstoreBookMapper bookstoreBookMapper;
    private final BookstoreBookSourceMapper bookstoreBookSourceMapper;
    private final BookstoreHttpParseSupport httpParse;
    private final NovelCategoryMapper novelCategoryMapper;

    public List<BookstoreLiveSourceItemDTO> listSourcesForPublic() {
        List<BookstoreBookSource> legados =
                bookstoreBookSourceMapper.selectList(
                        new LambdaQueryWrapper<BookstoreBookSource>()
                                .eq(BookstoreBookSource::getEnabled, 1)
                                .orderByAsc(BookstoreBookSource::getSortOrder)
                                .orderByDesc(BookstoreBookSource::getId)
                                .last("LIMIT 500"));
        if (!legados.isEmpty()) {
            List<BookstoreLiveSourceItemDTO> out = new ArrayList<>();
            for (BookstoreBookSource s : legados) {
                String base = httpParse.firstHttpBaseFromLegado(s.getSourceJson(), s.getBookSourceUrl());
                if (!StringUtils.hasText(base)) {
                    base = s.getBookSourceUrl();
                }
                out.add(BookstoreLiveSourceItemDTO.builder()
                        .id(s.getId())
                        .name(s.getBookSourceName())
                        .baseUrl(base)
                        .build());
            }
            return out;
        }

        List<BookstoreBook> rows =
                bookstoreBookMapper.selectList(
                        new LambdaQueryWrapper<BookstoreBook>()
                                .eq(BookstoreBook::getStatus, 1)
                                .orderByDesc(BookstoreBook::getReadCount)
                                .last("LIMIT 500"));
        List<BookstoreLiveSourceItemDTO> out = new ArrayList<>();
        for (BookstoreBook b : rows) {
            if (!hasBookSourceConfig(b)) {
                continue;
            }
            out.add(BookstoreLiveSourceItemDTO.builder()
                    .id(b.getId())
                    .name(b.getTitle())
                    .baseUrl(b.getSourceUrl())
                    .build());
        }
        return out;
    }

    public BookstoreLiveBookApiResponseDTO fetchBook(Long sourceId, String urlParam) {
        BookstoreBookSource legado = loadLegadoIfEnabled(sourceId);
        if (legado != null) {
            return fetchBookFromLegado(legado, urlParam);
        }
        BookstoreBook book = loadPublicBookWithSource(sourceId);
        return fetchBookFromBookRow(book, urlParam);
    }

    public BookstoreLiveChapterApiResponseDTO fetchChapter(Long sourceId, String chapterUrl) {
        if (!StringUtils.hasText(chapterUrl)) {
            throw new BusinessException("缺少章节 url");
        }
        BookstoreBookSource legado = loadLegadoIfEnabled(sourceId);
        if (legado != null) {
            return fetchChapterFromLegado(legado, chapterUrl.trim());
        }
        BookstoreBook book = loadPublicBookWithSource(sourceId);
        return fetchChapterFromBookRow(book, chapterUrl.trim());
    }

    public ResponseEntity<byte[]> proxyImageEntity(String imageUrl) {
        BookstoreUrlGuards.assertSafeHttpUrl(imageUrl);
        ResponseEntity<byte[]> ent = httpParse.httpGetBinaryEntity(imageUrl.trim(), null);
        byte[] body = ent.getBody();
        if (body == null || body.length == 0) {
            throw new BusinessException("图片为空");
        }
        int max = 4 * 1024 * 1024;
        if (body.length > max) {
            throw new BusinessException("图片过大");
        }
        MediaType ct = ent.getHeaders().getContentType();
        if (ct == null || !"image".equalsIgnoreCase(ct.getType())) {
            ct = MediaType.IMAGE_JPEG;
        }
        return ResponseEntity.status(HttpStatus.OK)
                .contentType(ct)
                .header("Cache-Control", "public, max-age=86400")
                .body(body);
    }

    private BookstoreLiveBookApiResponseDTO fetchBookFromLegado(BookstoreBookSource legado, String urlParam) {
        String tocUrl = httpParse.resolveCatalogUrlForLive(legado.getSourceJson(), legado.getBookSourceUrl(), urlParam);
        BookstoreUrlGuards.assertSafeHttpUrl(tocUrl);

        BookstoreParseRules rules = BookstoreParseRules.from(legado.getSourceJson(), httpParse.getObjectMapper());
        byte[] tocBytes = httpParse.httpGetBytes(tocUrl, rules.getUserAgent());
        String tocHtml = httpParse.decodeHtml(tocBytes, rules.getEncoding(), tocUrl);
        Document tocDoc = Jsoup.parse(tocHtml, tocUrl);
        List<BookstoreTocLink> rawLinks = httpParse.extractTocLinks(tocDoc, rules.getTocSelectors());

        List<BookstoreLiveChapterLinkDTO> chapters = buildChapterLinks(tocUrl, rawLinks);

        String comment = "";
        try {
            JsonNode n = httpParse.getObjectMapper().readTree(legado.getSourceJson());
            JsonNode c = n.get("bookSourceComment");
            if (c != null && c.isTextual()) {
                comment = c.asText().trim();
            }
        } catch (Exception ignored) {
            /* ignore */
        }
        if (comment.length() > 2000) {
            comment = comment.substring(0, 1997) + "...";
        }

        Map<String, Object> extra = new HashMap<>();
        extra.put("bookSourceGroup", legado.getBookSourceGroup());
        extra.put("bookSourceUrl", legado.getBookSourceUrl());

        BookstoreLiveBookPayloadDTO payload = BookstoreLiveBookPayloadDTO.builder()
                .id(legado.getId())
                .title(legado.getBookSourceName())
                .author("")
                .cover("")
                .description(comment)
                .category(legado.getBookSourceGroup())
                .extraInfo(extra)
                .build();

        return BookstoreLiveBookApiResponseDTO.builder().book(payload).chapters(chapters).build();
    }

    private BookstoreLiveBookApiResponseDTO fetchBookFromBookRow(BookstoreBook book, String urlParam) {
        String tocUrl = httpParse.resolveCatalogUrlForLive(book.getSourceJson(), book.getSourceUrl(), urlParam);
        BookstoreUrlGuards.assertSafeHttpUrl(tocUrl);

        BookstoreParseRules rules = BookstoreParseRules.from(book.getSourceJson(), httpParse.getObjectMapper());
        byte[] tocBytes = httpParse.httpGetBytes(tocUrl, rules.getUserAgent());
        String tocHtml = httpParse.decodeHtml(tocBytes, rules.getEncoding(), tocUrl);
        Document tocDoc = Jsoup.parse(tocHtml, tocUrl);
        List<BookstoreTocLink> rawLinks = httpParse.extractTocLinks(tocDoc, rules.getTocSelectors());

        List<BookstoreLiveChapterLinkDTO> chapters = buildChapterLinks(tocUrl, rawLinks);

        String categoryName = null;
        if (book.getCategoryId() != null) {
            NovelCategory cat = novelCategoryMapper.selectById(book.getCategoryId());
            if (cat != null) {
                categoryName = cat.getName();
            }
        }

        Map<String, Object> extra = new HashMap<>();
        extra.put("rating", book.getRating());
        extra.put("wordCount", book.getWordCount());
        extra.put("readCount", book.getReadCount());
        extra.put("isVip", book.getIsVip() != null && book.getIsVip() == 1);
        if (StringUtils.hasText(book.getTags())) {
            extra.put(
                    "tags",
                    Arrays.stream(book.getTags().split(","))
                            .map(String::trim)
                            .filter(StringUtils::hasText)
                            .toList());
        }

        BookstoreLiveBookPayloadDTO payload = BookstoreLiveBookPayloadDTO.builder()
                .id(book.getId())
                .title(book.getTitle())
                .author(book.getAuthor())
                .cover(book.getCoverUrl())
                .description(book.getIntro())
                .category(categoryName)
                .rating(book.getRating())
                .wordCount(book.getWordCount())
                .extraInfo(extra)
                .build();

        return BookstoreLiveBookApiResponseDTO.builder().book(payload).chapters(chapters).build();
    }

    private BookstoreLiveChapterApiResponseDTO fetchChapterFromLegado(BookstoreBookSource legado, String chapterUrl) {
        String url = httpParse.resolveChapterPageUrlForLive(legado.getSourceJson(), legado.getBookSourceUrl(), chapterUrl);
        BookstoreUrlGuards.assertSafeHttpUrl(url);

        BookstoreParseRules rules = BookstoreParseRules.from(legado.getSourceJson(), httpParse.getObjectMapper());
        byte[] bytes = httpParse.httpGetBytes(url, rules.getUserAgent());
        String html = httpParse.decodeHtml(bytes, rules.getEncoding(), url);
        Document doc = Jsoup.parse(html, url);
        String body = httpParse.extractContentHtml(doc, rules.getContentSelectors());
        if (!StringUtils.hasText(body)) {
            throw new BusinessException("正文解析为空，该书源可能含 @js/JSONPath，需扩展引擎");
        }
        String safe = httpParse.sanitizeChapterHtml(body);
        String title = firstNonBlank(doc.title(), guessTitleFromDom(doc));

        String tocUrl = httpParse.resolveCatalogUrlForLive(legado.getSourceJson(), legado.getBookSourceUrl(), null);
        BookstoreUrlGuards.assertSafeHttpUrl(tocUrl);
        byte[] tocBytes = httpParse.httpGetBytes(tocUrl, rules.getUserAgent());
        String tocHtml = httpParse.decodeHtml(tocBytes, rules.getEncoding(), tocUrl);
        Document tocDoc = Jsoup.parse(tocHtml, tocUrl);
        List<String> orderedAbs = buildOrderedChapterUrls(tocUrl, httpParse.extractTocLinks(tocDoc, rules.getTocSelectors()));

        return BookstoreLiveChapterApiResponseDTO.builder()
                .title(title)
                .contentHtml(safe)
                .navigation(buildNav(orderedAbs, url))
                .build();
    }

    private BookstoreLiveChapterApiResponseDTO fetchChapterFromBookRow(BookstoreBook book, String chapterUrl) {
        String url = httpParse.resolveChapterPageUrlForLive(book.getSourceJson(), book.getSourceUrl(), chapterUrl);
        BookstoreUrlGuards.assertSafeHttpUrl(url);

        BookstoreParseRules rules = BookstoreParseRules.from(book.getSourceJson(), httpParse.getObjectMapper());
        byte[] bytes = httpParse.httpGetBytes(url, rules.getUserAgent());
        String html = httpParse.decodeHtml(bytes, rules.getEncoding(), url);
        Document doc = Jsoup.parse(html, url);
        String body = httpParse.extractContentHtml(doc, rules.getContentSelectors());
        if (!StringUtils.hasText(body)) {
            throw new BusinessException("正文解析为空，请检查 contentSelectors / 章节页结构");
        }
        String safe = httpParse.sanitizeChapterHtml(body);
        String title = firstNonBlank(doc.title(), guessTitleFromDom(doc));

        String tocUrl = httpParse.resolveCatalogUrlForLive(book.getSourceJson(), book.getSourceUrl(), null);
        BookstoreUrlGuards.assertSafeHttpUrl(tocUrl);
        byte[] tocBytes = httpParse.httpGetBytes(tocUrl, rules.getUserAgent());
        String tocHtml = httpParse.decodeHtml(tocBytes, rules.getEncoding(), tocUrl);
        Document tocDoc = Jsoup.parse(tocHtml, tocUrl);
        List<String> orderedAbs = buildOrderedChapterUrls(tocUrl, httpParse.extractTocLinks(tocDoc, rules.getTocSelectors()));

        return BookstoreLiveChapterApiResponseDTO.builder()
                .title(title)
                .contentHtml(safe)
                .navigation(buildNav(orderedAbs, url))
                .build();
    }

    private static BookstoreLiveChapterNavDTO buildNav(List<String> orderedAbs, String currentUrl) {
        BookstoreLiveChapterNavPointerDTO prev = null;
        BookstoreLiveChapterNavPointerDTO next = null;
        int idx = indexOfUrl(orderedAbs, currentUrl);
        if (idx >= 0) {
            if (idx > 0) {
                prev = BookstoreLiveChapterNavPointerDTO.builder().url(orderedAbs.get(idx - 1)).build();
            }
            if (idx < orderedAbs.size() - 1) {
                next = BookstoreLiveChapterNavPointerDTO.builder().url(orderedAbs.get(idx + 1)).build();
            }
        }
        return BookstoreLiveChapterNavDTO.builder().prevChapter(prev).nextChapter(next).build();
    }

    private List<BookstoreLiveChapterLinkDTO> buildChapterLinks(String tocUrl, List<BookstoreTocLink> rawLinks) {
        List<BookstoreLiveChapterLinkDTO> chapters = new ArrayList<>();
        Set<String> seen = new LinkedHashSet<>();
        for (BookstoreTocLink link : rawLinks) {
            String abs = BookstoreHttpParseSupport.absolutize(tocUrl, link.href());
            if (!seen.add(abs)) {
                continue;
            }
            String title = StringUtils.hasText(link.text()) ? link.text().trim() : ("第" + (chapters.size() + 1) + "章");
            if (title.length() > 200) {
                title = title.substring(0, 197) + "...";
            }
            chapters.add(BookstoreLiveChapterLinkDTO.builder()
                    .title(title)
                    .url(abs)
                    .intro("")
                    .build());
            if (chapters.size() >= MAX_CHAPTER_LINKS) {
                break;
            }
        }
        return chapters;
    }

    private static List<String> buildOrderedChapterUrls(String tocBase, List<BookstoreTocLink> links) {
        List<String> ordered = new ArrayList<>();
        Set<String> seen = new LinkedHashSet<>();
        for (BookstoreTocLink link : links) {
            String abs = BookstoreHttpParseSupport.absolutize(tocBase, link.href());
            if (!seen.add(abs)) {
                continue;
            }
            ordered.add(abs);
            if (ordered.size() >= MAX_CHAPTER_LINKS) {
                break;
            }
        }
        return ordered;
    }

    private static int indexOfUrl(List<String> orderedAbs, String current) {
        String norm = normalizeUrl(current);
        for (int i = 0; i < orderedAbs.size(); i++) {
            if (normalizeUrl(orderedAbs.get(i)).equals(norm)) {
                return i;
            }
        }
        return -1;
    }

    private static String normalizeUrl(String u) {
        try {
            URI uri = URI.create(u.trim());
            return uri.normalize().toASCIIString();
        } catch (Exception e) {
            return u.trim().toLowerCase(Locale.ROOT);
        }
    }

    private static String firstNonBlank(String a, String b) {
        if (StringUtils.hasText(a)) {
            return a.trim();
        }
        if (StringUtils.hasText(b)) {
            return b.trim();
        }
        return "章节";
    }

    private static String guessTitleFromDom(Document doc) {
        var h = doc.selectFirst("h1, .title, #title");
        return h != null ? h.text() : "";
    }

    private BookstoreBookSource loadLegadoIfEnabled(Long id) {
        BookstoreBookSource s = bookstoreBookSourceMapper.selectById(id);
        if (s == null) {
            return null;
        }
        if (s.getEnabled() == null || s.getEnabled() != 1) {
            return null;
        }
        return s;
    }

    private BookstoreBook loadPublicBookWithSource(Long id) {
        BookstoreBook book = bookstoreBookMapper.selectById(id);
        if (book == null || book.getStatus() == null || book.getStatus() != 1) {
            throw new ResourceNotFoundException("书籍不存在或已下架");
        }
        if (!hasBookSourceConfig(book)) {
            throw new BusinessException("该书未配置书源，无法实时解析");
        }
        return book;
    }

    private static boolean hasBookSourceConfig(BookstoreBook b) {
        return StringUtils.hasText(b.getSourceUrl());
    }
}
