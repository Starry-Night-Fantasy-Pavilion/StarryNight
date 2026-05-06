package com.starrynight.starrynight.system.bookstore.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterAdminRowDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterMutateDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterReadDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreChapterTocItem;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreBook;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreChapter;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreBookMapper;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreChapterMapper;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;
import org.springframework.web.util.HtmlUtils;

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

    public List<BookstoreChapterAdminRowDTO> listAdminRows(Long bookId) {
        assertBookExists(bookId);
        return listChapters(bookId).stream()
                .map(c -> BookstoreChapterAdminRowDTO.builder()
                        .id(c.getId())
                        .chapterNo(c.getChapterNo())
                        .title(c.getTitle())
                        .wordCount(c.getWordCount())
                        .build())
                .collect(Collectors.toList());
    }

    public BookstoreChapter getAdminDetail(Long bookId, Long chapterId) {
        assertBookExists(bookId);
        BookstoreChapter c = bookstoreChapterMapper.selectById(chapterId);
        if (c == null || !bookId.equals(c.getBookId())) {
            throw new ResourceNotFoundException("章节不存在");
        }
        return c;
    }

    @Transactional(rollbackFor = Exception.class)
    public BookstoreChapterAdminRowDTO create(Long bookId, BookstoreChapterMutateDTO dto) {
        assertBookExists(bookId);
        if (dto.getChapterNo() == null || dto.getChapterNo() < 1) {
            throw new BusinessException("章节序号须 ≥ 1");
        }
        Long dup = bookstoreChapterMapper.selectCount(
                new LambdaQueryWrapper<BookstoreChapter>()
                        .eq(BookstoreChapter::getBookId, bookId)
                        .eq(BookstoreChapter::getChapterNo, dto.getChapterNo()));
        if (dup != null && dup > 0) {
            throw new BusinessException("该序号已存在");
        }
        String html = normalizeToHtml(dto.getContent());
        int wc = countWords(html);
        BookstoreChapter c = new BookstoreChapter();
        c.setBookId(bookId);
        c.setChapterNo(dto.getChapterNo());
        c.setTitle(dto.getTitle().trim());
        c.setContent(html);
        c.setWordCount(wc);
        bookstoreChapterMapper.insert(c);
        refreshBookWordCount(bookId);
        return BookstoreChapterAdminRowDTO.builder()
                .id(c.getId())
                .chapterNo(c.getChapterNo())
                .title(c.getTitle())
                .wordCount(c.getWordCount())
                .build();
    }

    @Transactional(rollbackFor = Exception.class)
    public void update(Long bookId, Long chapterId, BookstoreChapterMutateDTO dto) {
        BookstoreChapter c = getAdminDetail(bookId, chapterId);
        if (dto.getChapterNo() == null || dto.getChapterNo() < 1) {
            throw new BusinessException("章节序号须 ≥ 1");
        }
        if (!dto.getChapterNo().equals(c.getChapterNo())) {
            Long dup = bookstoreChapterMapper.selectCount(
                    new LambdaQueryWrapper<BookstoreChapter>()
                            .eq(BookstoreChapter::getBookId, bookId)
                            .eq(BookstoreChapter::getChapterNo, dto.getChapterNo())
                            .ne(BookstoreChapter::getId, chapterId));
            if (dup != null && dup > 0) {
                throw new BusinessException("该序号已存在");
            }
        }
        c.setChapterNo(dto.getChapterNo());
        c.setTitle(dto.getTitle().trim());
        if (dto.getContent() != null) {
            String html = normalizeToHtml(dto.getContent());
            c.setContent(html);
            c.setWordCount(countWords(html));
        }
        bookstoreChapterMapper.updateById(c);
        refreshBookWordCount(bookId);
    }

    @Transactional(rollbackFor = Exception.class)
    public void delete(Long bookId, Long chapterId) {
        getAdminDetail(bookId, chapterId);
        bookstoreChapterMapper.deleteById(chapterId);
        refreshBookWordCount(bookId);
    }

    private List<BookstoreChapter> listChapters(Long bookId) {
        return bookstoreChapterMapper.selectList(
                new LambdaQueryWrapper<BookstoreChapter>()
                        .eq(BookstoreChapter::getBookId, bookId)
                        .orderByAsc(BookstoreChapter::getChapterNo)
                        .orderByAsc(BookstoreChapter::getId));
    }

    private void assertBookExists(Long bookId) {
        BookstoreBook b = bookstoreBookMapper.selectById(bookId);
        if (b == null) {
            throw new ResourceNotFoundException("书籍不存在");
        }
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

    /**
     * 无尖括号时按纯文本段落转安全 HTML；否则视为运营录入的 HTML（需运营端可信）。
     */
    private String normalizeToHtml(String raw) {
        if (!StringUtils.hasText(raw)) {
            return "<p style=\"text-indent:2em;\"></p>";
        }
        String t = raw.trim();
        if (t.contains("<") && t.contains(">")) {
            return t;
        }
        String[] paras = t.split("\\r?\\n\\r?\\n");
        StringBuilder sb = new StringBuilder();
        for (String p : paras) {
            String line = p.trim();
            if (line.isEmpty()) {
                continue;
            }
            sb.append("<p style=\"text-indent:2em;margin-bottom:1em;\">")
                    .append(HtmlUtils.htmlEscape(line).replace("\n", "<br/>"))
                    .append("</p>");
        }
        if (sb.length() == 0) {
            sb.append("<p style=\"text-indent:2em;margin-bottom:1em;\">")
                    .append(HtmlUtils.htmlEscape(t).replace("\n", "<br/>"))
                    .append("</p>");
        }
        return sb.toString();
    }

    private static int countWords(String html) {
        if (!StringUtils.hasText(html)) {
            return 0;
        }
        String plain = html.replaceAll("<[^>]+>", "").replaceAll("\\s+", "");
        return plain.length();
    }
}
