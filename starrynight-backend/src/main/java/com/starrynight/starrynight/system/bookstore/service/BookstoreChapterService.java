package com.starrynight.starrynight.system.bookstore.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterReadDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterTocItem;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreBook;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreChapter;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreBookMapper;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreChapterMapper;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;

import java.util.List;
import java.util.stream.Collectors;

@Service
@RequiredArgsConstructor
public class BookstoreChapterService {

    private final BookstoreChapterMapper bookstoreChapterMapper;
    private final BookstoreBookMapper bookstoreBookMapper;

    public List<BookstoreChapterTocItem> listTocPublic(Long bookId) {
        assertPublicBook(bookId);
        return listChapters(bookId).stream()
                .map(c -> BookstoreChapterTocItem.builder()
                        .id(c.getId())
                        .chapterNo(c.getChapterNo())
                        .title(c.getTitle())
                        .wordCount(c.getWordCount())
                        .build())
                .collect(Collectors.toList());
    }

    public BookstoreChapterReadDTO readPublic(Long bookId, int chapterNo) {
        assertPublicBook(bookId);
        if (chapterNo < 1) {
            throw new ResourceNotFoundException("章节不存在");
        }
        List<BookstoreChapter> all = listChapters(bookId);
        if (all.isEmpty()) {
            throw new ResourceNotFoundException("暂无章节");
        }
        BookstoreChapter cur = all.stream()
                .filter(c -> c.getChapterNo() != null && c.getChapterNo() == chapterNo)
                .findFirst()
                .orElseThrow(() -> new ResourceNotFoundException("章节不存在"));
        Integer prev = null;
        Integer next = null;
        for (int i = 0; i < all.size(); i++) {
            if (all.get(i).getId().equals(cur.getId())) {
                if (i > 0) {
                    prev = all.get(i - 1).getChapterNo();
                }
                if (i < all.size() - 1) {
                    next = all.get(i + 1).getChapterNo();
                }
                break;
            }
        }
        String html = StringUtils.hasText(cur.getContent()) ? cur.getContent() : "<p></p>";
        return BookstoreChapterReadDTO.builder()
                .bookId(bookId)
                .chapterNo(cur.getChapterNo())
                .title(cur.getTitle())
                .contentHtml(html)
                .prevChapterNo(prev)
                .nextChapterNo(next)
                .totalChapters(all.size())
                .build();
    }

    private List<BookstoreChapter> listChapters(Long bookId) {
        return bookstoreChapterMapper.selectList(
                new LambdaQueryWrapper<BookstoreChapter>()
                        .eq(BookstoreChapter::getBookId, bookId)
                        .orderByAsc(BookstoreChapter::getChapterNo)
                        .orderByAsc(BookstoreChapter::getId));
    }

    private void assertPublicBook(Long bookId) {
        BookstoreBook b = bookstoreBookMapper.selectById(bookId);
        if (b == null || b.getStatus() == null || b.getStatus() != 1) {
            throw new ResourceNotFoundException("书籍不存在或已下架");
        }
    }

    private void refreshBookWordCount(Long bookId) {
        List<BookstoreChapter> all = bookstoreChapterMapper.selectList(
                new LambdaQueryWrapper<BookstoreChapter>().eq(BookstoreChapter::getBookId, bookId));
        int sum = all.stream().mapToInt(c -> c.getWordCount() != null ? c.getWordCount() : 0).sum();
        BookstoreBook b = bookstoreBookMapper.selectById(bookId);
        if (b != null) {
            b.setWordCount(sum);
            bookstoreBookMapper.updateById(b);
        }
    }

    /** 书源同步等批量写入后刷新书目总字数 */
    public void recomputeBookWordCount(Long bookId) {
        refreshBookWordCount(bookId);
    }
}
