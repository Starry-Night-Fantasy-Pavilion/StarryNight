package com.starrynight.starrynight.system.bookstore.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreBookPublicDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterReadDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterTocItem;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreHomeDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLiveBookApiResponseDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLiveChapterApiResponseDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreLiveSourceItemDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreSearchBookDTO;
import com.starrynight.starrynight.system.bookstore.service.BookstoreChapterService;
import com.starrynight.starrynight.system.bookstore.service.BookstoreLiveBookService;
import com.starrynight.starrynight.system.bookstore.service.BookstoreService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.util.StringUtils;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;
import java.util.stream.Collectors;

@RestController
@RequestMapping("/api/bookstore")
@RequiredArgsConstructor
public class BookstorePublicController {

    private final BookstoreService bookstoreService;
    private final BookstoreChapterService bookstoreChapterService;
    private final BookstoreLiveBookService bookstoreLiveBookService;

    @GetMapping("/home")
    public ResponseVO<BookstoreHomeDTO> home() {
        return ResponseVO.success(bookstoreService.home());
    }

    @GetMapping("/books/search")
    public ResponseVO<PageVO<BookstoreSearchBookDTO>> searchBooks(
            @RequestParam(required = false) String keyword,
            /** 逗号分隔分类 id，兼容前端查询串序列化 */
            @RequestParam(required = false) String categoryIds,
            @RequestParam(required = false, defaultValue = "relevance") String sort,
            @RequestParam(required = false) String membership,
            @RequestParam(required = false) String wordCountRange,
            @RequestParam(required = false) String tags,
            @RequestParam(required = false) String completionStatus,
            @RequestParam(required = false, defaultValue = "1") int page,
            @RequestParam(required = false, defaultValue = "20") int size) {
        int p = Math.max(1, page);
        int s = Math.min(50, Math.max(1, size));
        return ResponseVO.success(
                bookstoreService.pagePublicSearch(
                        keyword,
                        parseLongCsv(categoryIds),
                        sort,
                        membership,
                        wordCountRange,
                        parseStringCsv(tags),
                        completionStatus,
                        p,
                        s));
    }

    private static List<Long> parseLongCsv(String raw) {
        if (!StringUtils.hasText(raw)) {
            return null;
        }
        List<Long> out = new ArrayList<>();
        for (String p : raw.split(",")) {
            String t = p.trim();
            if (!StringUtils.hasText(t)) {
                continue;
            }
            try {
                out.add(Long.parseLong(t));
            } catch (NumberFormatException ignored) {
                /* 忽略非法片段 */
            }
        }
        return out.isEmpty() ? null : out;
    }

    private static List<String> parseStringCsv(String raw) {
        if (!StringUtils.hasText(raw)) {
            return null;
        }
        return Arrays.stream(raw.split(","))
                .map(String::trim)
                .filter(StringUtils::hasText)
                .collect(Collectors.toList());
    }

    @GetMapping("/books/{id}")
    public ResponseVO<BookstoreBookPublicDTO> book(@PathVariable Long id) {
        return ResponseVO.success(bookstoreService.getPublicBook(id));
    }

    @GetMapping("/books/{bookId}/chapters")
    public ResponseVO<List<BookstoreChapterTocItem>> chapters(@PathVariable Long bookId) {
        return ResponseVO.success(bookstoreChapterService.listTocPublic(bookId));
    }

    @GetMapping("/books/{bookId}/read/{chapterNo}")
    public ResponseVO<BookstoreChapterReadDTO> readChapter(
            @PathVariable Long bookId,
            @PathVariable int chapterNo) {
        return ResponseVO.success(bookstoreChapterService.readPublic(bookId, chapterNo));
    }

    /** 书源列表（文档 getBookSources）；id 即 sourceId，对应已配置书源的书目 */
    @GetMapping("/sources")
    public ResponseVO<List<BookstoreLiveSourceItemDTO>> liveSources() {
        return ResponseVO.success(bookstoreLiveBookService.listSourcesForPublic());
    }

    /** 实时拉取目录与元信息：/api/bookstore/book?sourceId=&url= */
    @GetMapping("/book")
    public ResponseVO<BookstoreLiveBookApiResponseDTO> liveBook(
            @RequestParam("sourceId") Long sourceId, @RequestParam(value = "url", required = false) String url) {
        return ResponseVO.success(bookstoreLiveBookService.fetchBook(sourceId, url));
    }

    /** 实时拉取单章正文与上下章链接：/api/bookstore/chapter?sourceId=&url= */
    @GetMapping("/chapter")
    public ResponseVO<BookstoreLiveChapterApiResponseDTO> liveChapter(
            @RequestParam("sourceId") Long sourceId, @RequestParam("url") String url) {
        return ResponseVO.success(bookstoreLiveBookService.fetchChapter(sourceId, url));
    }

    /** 图片代理，避免阅读页跨域 */
    @GetMapping("/proxy/image")
    public ResponseEntity<byte[]> bookstoreImageProxy(@RequestParam("url") String url) {
        return bookstoreLiveBookService.proxyImageEntity(url);
    }
}
