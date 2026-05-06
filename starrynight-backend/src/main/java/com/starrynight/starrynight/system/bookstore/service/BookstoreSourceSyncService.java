package com.starrynight.starrynight.system.bookstore.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreSyncRequestDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreSyncResultDTO;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreBook;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreChapter;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreBookMapper;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreChapterMapper;
import com.starrynight.starrynight.system.bookstore.support.BookstoreHttpParseSupport;
import com.starrynight.starrynight.system.bookstore.support.BookstoreParseRules;
import com.starrynight.starrynight.system.bookstore.support.BookstoreTocLink;
import com.starrynight.starrynight.system.bookstore.support.BookstoreUrlGuards;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.util.ArrayList;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Set;

/**
 * 书源自动解析：按设计文档「HTTP → HTML → 解析目录 / 正文 → 入库」。
 * <ul>
 *   <li>{@code bookstore_book.source_url}：书源 URL（与公开接口 {@code ?url=} 同义）</li>
 *   <li>{@code bookstore_book.source_json}：列保留但运营端不再写入；非空时仍可作为解析规则（兼容旧数据）</li>
 * </ul>
 */
@Slf4j
@Service
@RequiredArgsConstructor
public class BookstoreSourceSyncService {

    private final BookstoreBookMapper bookstoreBookMapper;
    private final BookstoreChapterMapper bookstoreChapterMapper;
    private final BookstoreChapterService bookstoreChapterService;
    private final BookstoreHttpParseSupport httpParse;

    @Transactional(rollbackFor = Exception.class)
    public BookstoreSyncResultDTO syncBook(Long bookId, BookstoreSyncRequestDTO req) {
        if (req == null) {
            req = new BookstoreSyncRequestDTO();
        }
        int max = req.getMaxChapters() != null && req.getMaxChapters() > 0 ? Math.min(req.getMaxChapters(), 500) : 80;
        boolean overwrite = Boolean.TRUE.equals(req.getOverwrite());
        int delayMs = req.getRequestDelayMs() != null && req.getRequestDelayMs() >= 0 ? Math.min(req.getRequestDelayMs(), 5000) : 150;

        BookstoreBook book = bookstoreBookMapper.selectById(bookId);
        if (book == null) {
            throw new ResourceNotFoundException("书籍不存在");
        }
        String tocUrl = httpParse.resolveTocUrl(book);
        BookstoreUrlGuards.assertSafeHttpUrl(tocUrl);

        BookstoreParseRules rules = BookstoreParseRules.from(book.getSourceJson(), httpParse.getObjectMapper());

        BookstoreSyncResultDTO.BookstoreSyncResultDTOBuilder out = BookstoreSyncResultDTO.builder();
        List<String> logs = new ArrayList<>();

        byte[] tocBytes = httpParse.httpGetBytes(tocUrl, rules.getUserAgent());
        String tocHtml = httpParse.decodeHtml(tocBytes, rules.getEncoding(), tocUrl);
        Document tocDoc = Jsoup.parse(tocHtml, tocUrl);

        List<BookstoreTocLink> links = httpParse.extractTocLinks(tocDoc, rules.getTocSelectors());
        logs.add("目录页解析得到链接数: " + links.size());
        out.tocLinksFound(links.size());
        if (links.isEmpty()) {
            logs.add(
                    "提示: 未解析到章节链接。请确认目录页 URL 是否正确；或在书源 JSON 中配置 tocSelectors / tocSelector"
                            + "（纯 Legado 且 ruleToc 为对象时，chapterList 须为不含 @ 的 CSS 选择器，否则请改成本站 CSS 规则）。");
        }

        int imported = 0;
        int updated = 0;
        int skipped = 0;
        int chapterNo = 0;
        Set<String> seenHref = new LinkedHashSet<>();

        for (BookstoreTocLink link : links) {
            String abs = BookstoreHttpParseSupport.absolutize(tocUrl, link.href());
            if (!seenHref.add(abs)) {
                continue;
            }
            chapterNo++;
            if (chapterNo > max) {
                break;
            }
            BookstoreUrlGuards.assertSafeHttpUrl(abs);

            BookstoreChapter existing = bookstoreChapterMapper.selectOne(
                    new LambdaQueryWrapper<BookstoreChapter>()
                            .eq(BookstoreChapter::getBookId, bookId)
                            .eq(BookstoreChapter::getChapterNo, chapterNo));

            if (existing != null && !overwrite) {
                skipped++;
                continue;
            }

            try {
                if (delayMs > 0) {
                    Thread.sleep(delayMs);
                }
            } catch (InterruptedException e) {
                Thread.currentThread().interrupt();
                throw new BusinessException("同步被中断");
            }

            byte[] chBytes = httpParse.httpGetBytes(abs, rules.getUserAgent());
            String chHtml = httpParse.decodeHtml(chBytes, rules.getEncoding(), abs);
            Document chDoc = Jsoup.parse(chHtml, abs);
            String bodyHtml = httpParse.extractContentHtml(chDoc, rules.getContentSelectors());
            if (!StringUtils.hasText(bodyHtml)) {
                logs.add("第" + chapterNo + "章正文为空，跳过: " + abs);
                skipped++;
                continue;
            }
            String safeHtml = httpParse.sanitizeChapterHtml(bodyHtml);
            int wc = BookstoreHttpParseSupport.countPlainChars(safeHtml);

            String title = StringUtils.hasText(link.text()) ? link.text().trim() : ("第" + chapterNo + "章");

            if (existing == null) {
                BookstoreChapter c = new BookstoreChapter();
                c.setBookId(bookId);
                c.setChapterNo(chapterNo);
                c.setTitle(title.length() > 200 ? title.substring(0, 197) + "..." : title);
                c.setContent(safeHtml);
                c.setWordCount(wc);
                bookstoreChapterMapper.insert(c);
                imported++;
            } else {
                existing.setTitle(title.length() > 200 ? title.substring(0, 197) + "..." : title);
                existing.setContent(safeHtml);
                existing.setWordCount(wc);
                bookstoreChapterMapper.updateById(existing);
                updated++;
            }
        }

        bookstoreChapterService.recomputeBookWordCount(bookId);

        if (!links.isEmpty() && imported == 0 && updated == 0 && !overwrite && skipped > 0) {
            logs.add(
                    "提示: 本次无导入/更新。若同序号章节已存在，需开启「覆盖已有章节」才会重写；若日志中有「正文为空」，请调整 contentSelectors / contentSelector。");
        }

        logs.add("导入: " + imported + ", 更新: " + updated + ", 跳过: " + skipped);
        return out
                .chaptersImported(imported)
                .chaptersUpdated(updated)
                .chaptersSkipped(skipped)
                .logs(logs)
                .build();
    }
}
