package com.starrynight.starrynight.system.bookstore.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreBookDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreBookPublicDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreConfigDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreHomeDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreSearchBookDTO;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreBook;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreChapter;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreBookMapper;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreChapterMapper;
import com.starrynight.starrynight.system.novel.entity.NovelCategory;
import com.starrynight.starrynight.system.novel.mapper.NovelCategoryMapper;
import com.starrynight.starrynight.system.system.entity.SystemConfig;
import com.starrynight.starrynight.system.system.repository.SystemConfigRepository;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import lombok.RequiredArgsConstructor;
import org.springframework.cache.CacheManager;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@Service
@RequiredArgsConstructor
public class BookstoreService {

    private final BookstoreBookMapper bookstoreBookMapper;
    private final BookstoreChapterMapper bookstoreChapterMapper;
    private final NovelCategoryMapper novelCategoryMapper;
    private final RuntimeConfigService runtimeConfigService;
    private final SystemConfigRepository systemConfigRepository;
    private final CacheManager cacheManager;

    public BookstoreHomeDTO home() {
        boolean enabled = runtimeConfigService.getBoolean("bookstore.enabled", true);
        String title = runtimeConfigService.getString("bookstore.site_title", "星夜书库");
        List<Map<String, Object>> empty = List.of();

        if (!enabled) {
            return BookstoreHomeDTO.builder()
                    .enabled(false)
                    .siteTitle(title)
                    .banners(empty)
                    .hotBooks(List.of())
                    .newBooks(List.of())
                    .rankingBooks(List.of())
                    .categories(List.of())
                    .sidebarReaders(empty)
                    .latestUpdates(empty)
                    .build();
        }

        return BookstoreHomeDTO.builder()
                .enabled(true)
                .siteTitle(title)
                .banners(empty)
                .hotBooks(listBooksCards(queryHot()))
                .newBooks(listBooksCards(queryNew()))
                .rankingBooks(listBooksCards(queryRanking()))
                .categories(buildCategoryBrowse())
                .sidebarReaders(empty)
                .latestUpdates(empty)
                .build();
    }

    public BookstoreBookPublicDTO getPublicBook(Long id) {
        BookstoreBook b = bookstoreBookMapper.selectById(id);
        if (b == null || b.getStatus() == null || b.getStatus() != 1) {
            throw new ResourceNotFoundException("书籍不存在或已下架");
        }
        return toPublic(b);
    }

    public BookstoreConfigDTO getConfigForAdmin() {
        BookstoreConfigDTO dto = new BookstoreConfigDTO();
        dto.setEnabled(Boolean.valueOf(runtimeConfigService.getBoolean("bookstore.enabled", true)));
        dto.setSiteTitle(runtimeConfigService.getString("bookstore.site_title", "星夜书库"));
        return dto;
    }

    @Transactional(rollbackFor = Exception.class)
    public void saveConfig(BookstoreConfigDTO patch) {
        if (patch.getEnabled() != null) {
            updateConfigValue("bookstore.enabled", Boolean.toString(patch.getEnabled()));
        }
        if (patch.getSiteTitle() != null) {
            updateConfigValue("bookstore.site_title", patch.getSiteTitle());
        }
        runtimeConfigService.reloadFromDatabase();
        evictSystemConfigCache("bookstore.enabled", "bookstore.site_title");
    }

    public PageVO<BookstoreBookDTO> pageBooks(String keyword, int page, int size) {
        LambdaQueryWrapper<BookstoreBook> w = new LambdaQueryWrapper<>();
        if (StringUtils.hasText(keyword)) {
            String k = keyword.trim();
            w.and(q -> q.like(BookstoreBook::getTitle, k)
                    .or()
                    .like(BookstoreBook::getAuthor, k)
                    .or()
                    .like(BookstoreBook::getSourceUrl, k));
        }
        w.orderByDesc(BookstoreBook::getId);
        com.baomidou.mybatisplus.extension.plugins.pagination.Page<BookstoreBook> p =
                bookstoreBookMapper.selectPage(new com.baomidou.mybatisplus.extension.plugins.pagination.Page<>(page, size), w);
        List<BookstoreBookDTO> records = p.getRecords().stream().map(this::toAdminDto).collect(Collectors.toList());
        return PageVO.of(p.getTotal(), records, p.getCurrent(), p.getSize());
    }

    /**
     * 前台书城搜索/列表：仅上架（status=1），支持关键词、分类、排序、VIP、字数（万字）、标签子串筛选。
     */
    public PageVO<BookstoreSearchBookDTO> pagePublicSearch(
            String keyword,
            List<Long> categoryIds,
            String sort,
            String vipFilter,
            String wordCountRange,
            List<String> tagFilters,
            String completionStatus,
            int page,
            int size) {
        LambdaQueryWrapper<BookstoreBook> w = new LambdaQueryWrapper<>();
        w.eq(BookstoreBook::getStatus, 1);
        if ("serial".equalsIgnoreCase(completionStatus)) {
            w.apply("EXISTS (SELECT 1 FROM bookstore_chapter c WHERE c.book_id = bookstore_book.id)");
        } else if ("finished".equalsIgnoreCase(completionStatus)) {
            w.apply("NOT EXISTS (SELECT 1 FROM bookstore_chapter c WHERE c.book_id = bookstore_book.id)");
        }
        if (StringUtils.hasText(keyword)) {
            String k = keyword.trim();
            w.and(q -> q.like(BookstoreBook::getTitle, k).or().like(BookstoreBook::getAuthor, k));
        }
        if (categoryIds != null && !categoryIds.isEmpty()) {
            w.in(BookstoreBook::getCategoryId, categoryIds);
        }
        if ("vip".equalsIgnoreCase(vipFilter)) {
            w.eq(BookstoreBook::getIsVip, 1);
        } else if ("free".equalsIgnoreCase(vipFilter)) {
            w.and(q -> q.isNull(BookstoreBook::getIsVip).or().eq(BookstoreBook::getIsVip, 0));
        }
        applyWordCountRange(w, wordCountRange);
        if (tagFilters != null) {
            for (String t : tagFilters) {
                if (StringUtils.hasText(t)) {
                    w.like(BookstoreBook::getTags, t.trim());
                }
            }
        }
        applyPublicSearchSort(w, sort == null ? "" : sort.trim());

        com.baomidou.mybatisplus.extension.plugins.pagination.Page<BookstoreBook> p =
                bookstoreBookMapper.selectPage(new com.baomidou.mybatisplus.extension.plugins.pagination.Page<>(page, size), w);
        List<BookstoreSearchBookDTO> records = p.getRecords().stream().map(this::toSearchDto).collect(Collectors.toList());
        return PageVO.of(p.getTotal(), records, p.getCurrent(), p.getSize());
    }

    private static void applyWordCountRange(LambdaQueryWrapper<BookstoreBook> w, String range) {
        if (!StringUtils.hasText(range)) {
            return;
        }
        switch (range.trim()) {
            case "0-50":
                w.and(q -> q.isNull(BookstoreBook::getWordCount).or().le(BookstoreBook::getWordCount, 50));
                break;
            case "50-100":
                w.ge(BookstoreBook::getWordCount, 50).le(BookstoreBook::getWordCount, 100);
                break;
            case "100-200":
                w.ge(BookstoreBook::getWordCount, 100).le(BookstoreBook::getWordCount, 200);
                break;
            case "200+":
                w.ge(BookstoreBook::getWordCount, 200);
                break;
            default:
                break;
        }
    }

    private static void applyPublicSearchSort(LambdaQueryWrapper<BookstoreBook> w, String sort) {
        switch (sort) {
            case "hot":
                w.orderByDesc(BookstoreBook::getReadCount).orderByDesc(BookstoreBook::getId);
                break;
            case "new":
                w.orderByDesc(BookstoreBook::getCreateTime).orderByDesc(BookstoreBook::getId);
                break;
            case "update":
                w.orderByDesc(BookstoreBook::getUpdateTime).orderByDesc(BookstoreBook::getId);
                break;
            case "rating":
                w.orderByDesc(BookstoreBook::getRating).orderByDesc(BookstoreBook::getId);
                break;
            case "wordcount":
                w.orderByDesc(BookstoreBook::getWordCount).orderByDesc(BookstoreBook::getId);
                break;
            case "relevance":
            default:
                w.orderByDesc(BookstoreBook::getUpdateTime).orderByDesc(BookstoreBook::getReadCount).orderByDesc(BookstoreBook::getId);
                break;
        }
    }

    private BookstoreSearchBookDTO toSearchDto(BookstoreBook b) {
        List<String> tagList = StringUtils.hasText(b.getTags())
                ? Arrays.stream(b.getTags().split(",")).map(String::trim).filter(StringUtils::hasText).collect(Collectors.toList())
                : List.of();
        Long n = bookstoreChapterMapper.selectCount(
                new LambdaQueryWrapper<BookstoreChapter>().eq(BookstoreChapter::getBookId, b.getId()));
        int chapterCount = n == null ? 0 : n.intValue();
        String st = "";
        if (chapterCount > 0) {
            st = "连载中";
        }
        return BookstoreSearchBookDTO.builder()
                .id(b.getId())
                .title(b.getTitle())
                .author(b.getAuthor())
                .cover(b.getCoverUrl())
                .description(b.getIntro())
                .category(buildCategoryLabel(b.getCategoryId()))
                .wordCount(b.getWordCount())
                .views(b.getReadCount())
                .rating(b.getRating())
                .chapterCount(chapterCount)
                .isVip(b.getIsVip() != null && b.getIsVip() == 1)
                .tags(tagList)
                .status(st)
                .build();
    }

    private String buildCategoryLabel(Long categoryId) {
        if (categoryId == null) {
            return "";
        }
        NovelCategory cat = novelCategoryMapper.selectById(categoryId);
        if (cat == null) {
            return "";
        }
        if (cat.getParentId() != null) {
            NovelCategory p = novelCategoryMapper.selectById(cat.getParentId());
            return (p != null ? p.getName() + " / " : "") + cat.getName();
        }
        return cat.getName();
    }

    public BookstoreBookDTO getBookAdmin(Long id) {
        BookstoreBook b = bookstoreBookMapper.selectById(id);
        if (b == null) {
            throw new ResourceNotFoundException("书籍不存在");
        }
        return toAdminDto(b);
    }

    @Transactional(rollbackFor = Exception.class)
    public BookstoreBookDTO createBook(BookstoreBookDTO dto) {
        normalizeBookSourcePayload(dto);
        BookstoreBook b = toEntity(dto);
        b.setId(null);
        bookstoreBookMapper.insert(b);
        return toAdminDto(b);
    }

    @Transactional(rollbackFor = Exception.class)
    public BookstoreBookDTO updateBook(Long id, BookstoreBookDTO dto) {
        normalizeBookSourcePayload(dto);
        BookstoreBook b = bookstoreBookMapper.selectById(id);
        if (b == null) {
            throw new ResourceNotFoundException("书籍不存在");
        }
        applyEntity(b, dto);
        bookstoreBookMapper.updateById(b);
        return toAdminDto(b);
    }

    @Transactional(rollbackFor = Exception.class)
    public void deleteBook(Long id) {
        bookstoreBookMapper.deleteById(id);
    }

    private void updateConfigValue(String key, String value) {
        SystemConfig c = systemConfigRepository.selectOne(
                new LambdaQueryWrapper<SystemConfig>().eq(SystemConfig::getConfigKey, key));
        if (c == null) {
            throw new com.starrynight.starrynight.framework.common.exception.BusinessException("配置键未初始化: " + key);
        }
        if (c.getEditable() != null && c.getEditable() == 0) {
            throw new com.starrynight.starrynight.framework.common.exception.BusinessException("配置不可编辑: " + key);
        }
        c.setConfigValue(value);
        systemConfigRepository.updateById(c);
    }

    private void evictSystemConfigCache(String... keys) {
        var cache = cacheManager.getCache("systemConfig");
        if (cache != null) {
            for (String k : keys) {
                cache.evict(k);
            }
        }
    }

    private List<BookstoreBook> queryHot() {
        return bookstoreBookMapper.selectList(
                new LambdaQueryWrapper<BookstoreBook>()
                        .eq(BookstoreBook::getStatus, 1)
                        .orderByDesc(BookstoreBook::getReadCount)
                        .orderByDesc(BookstoreBook::getId)
                        .last("LIMIT 8"));
    }

    private List<BookstoreBook> queryNew() {
        return bookstoreBookMapper.selectList(
                new LambdaQueryWrapper<BookstoreBook>()
                        .eq(BookstoreBook::getStatus, 1)
                        .orderByDesc(BookstoreBook::getCreateTime)
                        .last("LIMIT 8"));
    }

    private List<BookstoreBook> queryRanking() {
        return bookstoreBookMapper.selectList(
                new LambdaQueryWrapper<BookstoreBook>()
                        .eq(BookstoreBook::getStatus, 1)
                        .orderByDesc(BookstoreBook::getReadCount)
                        .last("LIMIT 8"));
    }

    private List<Map<String, Object>> listBooksCards(List<BookstoreBook> books) {
        List<Map<String, Object>> out = new ArrayList<>();
        for (BookstoreBook b : books) {
            Map<String, Object> m = new HashMap<>();
            m.put("id", b.getId());
            m.put("title", b.getTitle());
            m.put("author", b.getAuthor());
            m.put("description", b.getIntro());
            m.put("cover", b.getCoverUrl());
            m.put("views", b.getReadCount());
            m.put("rating", b.getRating());
            m.put("isVip", b.getIsVip() != null && b.getIsVip() == 1);
            out.add(m);
        }
        return out;
    }

    private List<Map<String, Object>> buildCategoryBrowse() {
        List<NovelCategory> all = novelCategoryMapper.selectList(
                new LambdaQueryWrapper<NovelCategory>().eq(NovelCategory::getStatus, 1));
        Map<Long, NovelCategory> byId = all.stream().collect(Collectors.toMap(NovelCategory::getId, c -> c));
        List<Map<String, Object>> out = new ArrayList<>();
        String[] icons = {"📚", "📖", "🗡️", "⚔️", "🔮", "💕", "🚀", "👻"};
        int iconI = 0;
        for (NovelCategory c : all) {
            if (c.getParentId() == null) {
                continue;
            }
            NovelCategory p = byId.get(c.getParentId());
            long cnt = countBooksInCategory(c.getId());
            Map<String, Object> m = new HashMap<>();
            m.put("id", c.getId());
            m.put("name", (p != null ? p.getName() + " · " : "") + c.getName());
            m.put("icon", icons[iconI % icons.length]);
            iconI++;
            m.put("count", cnt);
            out.add(m);
        }
        for (NovelCategory c : all) {
            if (c.getParentId() != null) {
                continue;
            }
            boolean hasChild = all.stream().anyMatch(x -> c.getId().equals(x.getParentId()));
            if (hasChild) {
                continue;
            }
            long cnt = countBooksInCategory(c.getId());
            Map<String, Object> m = new HashMap<>();
            m.put("id", c.getId());
            m.put("name", c.getName());
            m.put("icon", icons[iconI % icons.length]);
            iconI++;
            m.put("count", cnt);
            out.add(m);
        }
        return out;
    }

    private long countBooksInCategory(Long categoryId) {
        Long n = bookstoreBookMapper.selectCount(
                new LambdaQueryWrapper<BookstoreBook>()
                        .eq(BookstoreBook::getCategoryId, categoryId)
                        .eq(BookstoreBook::getStatus, 1));
        return n == null ? 0L : n;
    }

    private BookstoreBookPublicDTO toPublic(BookstoreBook b) {
        List<String> tagList = StringUtils.hasText(b.getTags())
                ? Arrays.stream(b.getTags().split(",")).map(String::trim).filter(StringUtils::hasText).collect(Collectors.toList())
                : List.of();
        String catLabel = buildCategoryLabel(b.getCategoryId());
        boolean live = StringUtils.hasText(b.getSourceUrl());
        return BookstoreBookPublicDTO.builder()
                .id(b.getId())
                .title(b.getTitle())
                .author(b.getAuthor())
                .cover(b.getCoverUrl())
                .description(b.getIntro())
                .views(b.getReadCount())
                .rating(b.getRating())
                .isVip(b.getIsVip() != null && b.getIsVip() == 1)
                .wordCount(b.getWordCount())
                .category(catLabel)
                .tags(tagList)
                .liveParseAvailable(live)
                .build();
    }

    private BookstoreBookDTO toAdminDto(BookstoreBook b) {
        BookstoreBookDTO d = new BookstoreBookDTO();
        d.setId(b.getId());
        d.setTitle(b.getTitle());
        d.setAuthor(b.getAuthor());
        d.setCoverUrl(b.getCoverUrl());
        d.setIntro(b.getIntro());
        d.setCategoryId(b.getCategoryId());
        d.setIsVip(b.getIsVip());
        d.setRating(b.getRating());
        d.setWordCount(b.getWordCount());
        d.setReadCount(b.getReadCount());
        d.setSortOrder(b.getSortOrder());
        d.setStatus(b.getStatus());
        d.setTags(b.getTags());
        d.setSourceUrl(b.getSourceUrl());
        if (b.getId() != null) {
            Long n = bookstoreChapterMapper.selectCount(
                    new LambdaQueryWrapper<BookstoreChapter>().eq(BookstoreChapter::getBookId, b.getId()));
            d.setChapterCount(n == null ? 0 : n.intValue());
        } else {
            d.setChapterCount(0);
        }
        return d;
    }

    private BookstoreBook toEntity(BookstoreBookDTO dto) {
        BookstoreBook b = new BookstoreBook();
        applyEntity(b, dto);
        return b;
    }

    private void applyEntity(BookstoreBook b, BookstoreBookDTO dto) {
        b.setTitle(dto.getTitle());
        b.setAuthor(dto.getAuthor());
        b.setCoverUrl(dto.getCoverUrl());
        b.setIntro(dto.getIntro());
        b.setCategoryId(dto.getCategoryId());
        b.setIsVip(dto.getIsVip());
        b.setRating(dto.getRating());
        b.setWordCount(dto.getWordCount());
        b.setReadCount(dto.getReadCount());
        b.setSortOrder(dto.getSortOrder());
        b.setStatus(dto.getStatus());
        b.setTags(dto.getTags());
        b.setSourceUrl(trimToNull(dto.getSourceUrl()));
        /* 运营端不再维护书源 JSON，每次保存清空历史列 */
        b.setSourceJson(null);
    }

    /**
     * 运营端仅书源 URL；标题空则按 URL 生成列表展示用标题。
     */
    private void normalizeBookSourcePayload(BookstoreBookDTO dto) {
        String url = trimToNull(dto.getSourceUrl());
        if (url == null) {
            throw new com.starrynight.starrynight.framework.common.exception.BusinessException("请填写书源 URL（文档 /api/bookstore/book?url=）");
        }
        dto.setSourceUrl(url);
        if (!StringUtils.hasText(trimToNull(dto.getTitle()))) {
            dto.setTitle(abbreviateUrlForTitle(url));
        }
    }

    private static String abbreviateUrlForTitle(String url) {
        try {
            java.net.URI u = java.net.URI.create(url.trim());
            String host = u.getHost() != null ? u.getHost() : "";
            String path = u.getPath() != null ? u.getPath() : "";
            String s = host + path;
            if (!StringUtils.hasText(s)) {
                s = url.trim();
            }
            return s.length() > 120 ? s.substring(0, 117) + "..." : s;
        } catch (Exception e) {
            String t = url.trim();
            return t.length() > 120 ? t.substring(0, 117) + "..." : t;
        }
    }

    private static String trimToNull(String s) {
        if (!StringUtils.hasText(s)) {
            return null;
        }
        String t = s.trim();
        return t.isEmpty() ? null : t;
    }
}
