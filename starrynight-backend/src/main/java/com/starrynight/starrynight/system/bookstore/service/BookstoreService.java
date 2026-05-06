package com.starrynight.starrynight.system.bookstore.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreBookDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreBookPublicDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreConfigDTO;
import com.starrynight.starrynight.system.bookstore.dto.BookstoreHomeDTO;
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
import lombok.extern.slf4j.Slf4j;
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

@Slf4j
@Service
@RequiredArgsConstructor
public class BookstoreService {

    private static final TypeReference<List<Map<String, Object>>> LIST_MAP = new TypeReference<>() {};

    private final BookstoreBookMapper bookstoreBookMapper;
    private final BookstoreChapterMapper bookstoreChapterMapper;
    private final NovelCategoryMapper novelCategoryMapper;
    private final RuntimeConfigService runtimeConfigService;
    private final SystemConfigRepository systemConfigRepository;
    private final ObjectMapper objectMapper;
    private final CacheManager cacheManager;

    public BookstoreHomeDTO home() {
        boolean enabled = runtimeConfigService.getBoolean("bookstore.enabled", true);
        String title = runtimeConfigService.getString("bookstore.site_title", "星夜书城");
        List<Map<String, Object>> banners = parseJsonList(runtimeConfigService.getProperty("bookstore.banners_json"));
        List<Map<String, Object>> readers = parseJsonList(runtimeConfigService.getProperty("bookstore.sidebar_readers_json"));
        List<Map<String, Object>> updates = parseJsonList(runtimeConfigService.getProperty("bookstore.latest_updates_json"));

        if (!enabled) {
            return BookstoreHomeDTO.builder()
                    .enabled(false)
                    .siteTitle(title)
                    .banners(List.of())
                    .hotBooks(List.of())
                    .newBooks(List.of())
                    .rankingBooks(List.of())
                    .categories(List.of())
                    .sidebarReaders(readers)
                    .latestUpdates(updates)
                    .build();
        }

        return BookstoreHomeDTO.builder()
                .enabled(true)
                .siteTitle(title)
                .banners(banners)
                .hotBooks(listBooksCards(queryHot()))
                .newBooks(listBooksCards(queryNew()))
                .rankingBooks(listBooksCards(queryRanking()))
                .categories(buildCategoryBrowse())
                .sidebarReaders(readers)
                .latestUpdates(updates)
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
        dto.setSiteTitle(runtimeConfigService.getString("bookstore.site_title", "星夜书城"));
        dto.setBannersJson(runtimeConfigService.getString("bookstore.banners_json", "[]"));
        dto.setSidebarReadersJson(runtimeConfigService.getString("bookstore.sidebar_readers_json", "[]"));
        dto.setLatestUpdatesJson(runtimeConfigService.getString("bookstore.latest_updates_json", "[]"));
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
        if (patch.getBannersJson() != null) {
            validateJsonArray(patch.getBannersJson());
            updateConfigValue("bookstore.banners_json", patch.getBannersJson());
        }
        if (patch.getSidebarReadersJson() != null) {
            validateJsonArray(patch.getSidebarReadersJson());
            updateConfigValue("bookstore.sidebar_readers_json", patch.getSidebarReadersJson());
        }
        if (patch.getLatestUpdatesJson() != null) {
            validateJsonArray(patch.getLatestUpdatesJson());
            updateConfigValue("bookstore.latest_updates_json", patch.getLatestUpdatesJson());
        }
        runtimeConfigService.reloadFromDatabase();
        evictSystemConfigCache(
                "bookstore.enabled",
                "bookstore.site_title",
                "bookstore.banners_json",
                "bookstore.sidebar_readers_json",
                "bookstore.latest_updates_json");
    }

    public PageVO<BookstoreBookDTO> pageBooks(String keyword, int page, int size) {
        LambdaQueryWrapper<BookstoreBook> w = new LambdaQueryWrapper<>();
        if (StringUtils.hasText(keyword)) {
            String k = keyword.trim();
            w.and(q -> q.like(BookstoreBook::getTitle, k).or().like(BookstoreBook::getAuthor, k));
        }
        w.orderByDesc(BookstoreBook::getId);
        com.baomidou.mybatisplus.extension.plugins.pagination.Page<BookstoreBook> p =
                bookstoreBookMapper.selectPage(new com.baomidou.mybatisplus.extension.plugins.pagination.Page<>(page, size), w);
        List<BookstoreBookDTO> records = p.getRecords().stream().map(this::toAdminDto).collect(Collectors.toList());
        return PageVO.of(p.getTotal(), records, p.getCurrent(), p.getSize());
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

    private void validateJsonArray(String json) {
        try {
            var node = objectMapper.readTree(json);
            if (!node.isArray()) {
                throw new com.starrynight.starrynight.framework.common.exception.BusinessException("JSON 须为数组");
            }
        } catch (com.starrynight.starrynight.framework.common.exception.BusinessException e) {
            throw e;
        } catch (Exception e) {
            throw new com.starrynight.starrynight.framework.common.exception.BusinessException("JSON 格式无效");
        }
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

    private List<Map<String, Object>> parseJsonList(String raw) {
        if (!StringUtils.hasText(raw)) {
            return List.of();
        }
        try {
            List<Map<String, Object>> list = objectMapper.readValue(raw, LIST_MAP);
            return list != null ? list : List.of();
        } catch (Exception e) {
            log.warn("parse bookstore json failed: {}", e.toString());
            return List.of();
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
        String catLabel = "";
        if (b.getCategoryId() != null) {
            NovelCategory cat = novelCategoryMapper.selectById(b.getCategoryId());
            if (cat != null) {
                if (cat.getParentId() != null) {
                    NovelCategory p = novelCategoryMapper.selectById(cat.getParentId());
                    catLabel = (p != null ? p.getName() + " / " : "") + cat.getName();
                } else {
                    catLabel = cat.getName();
                }
            }
        }
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
        d.setSourceJson(b.getSourceJson());
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
        b.setSourceJson(trimToNull(dto.getSourceJson()));
    }

    private void validateSourceJsonField(String raw) {
        if (!StringUtils.hasText(raw)) {
            return;
        }
        try {
            objectMapper.readTree(raw.trim());
        } catch (Exception e) {
            throw new com.starrynight.starrynight.framework.common.exception.BusinessException("书源 JSON 格式无效，请检查是否为合法 JSON");
        }
    }

    /**
     * 书源 URL 与书源 JSON 二选一；标题空则自动生成便于列表展示。
     */
    private void normalizeBookSourcePayload(BookstoreBookDTO dto) {
        String url = trimToNull(dto.getSourceUrl());
        String json = trimToNull(dto.getSourceJson());
        boolean hasUrl = url != null;
        boolean hasJson = json != null;
        if (hasUrl && hasJson) {
            throw new com.starrynight.starrynight.framework.common.exception.BusinessException("书源 URL 与书源 JSON 只能填写其中一种");
        }
        if (!hasUrl && !hasJson) {
            throw new com.starrynight.starrynight.framework.common.exception.BusinessException("请填写书源 URL 或书源 JSON 其中一种");
        }
        if (hasJson) {
            validateSourceJsonField(json);
            dto.setSourceJson(json);
            dto.setSourceUrl(null);
        } else {
            dto.setSourceUrl(url);
            dto.setSourceJson(null);
        }
        if (!StringUtils.hasText(trimToNull(dto.getTitle()))) {
            dto.setTitle(hasUrl ? abbreviateUrlForTitle(url) : abbreviateJsonForTitle(json));
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

    private static String abbreviateJsonForTitle(String json) {
        String flat = json.replaceAll("\\s+", " ").trim();
        if (flat.length() <= 48) {
            return "书源JSON · " + flat;
        }
        return "书源JSON · " + flat.substring(0, 45) + "...";
    }

    private static String trimToNull(String s) {
        if (!StringUtils.hasText(s)) {
            return null;
        }
        String t = s.trim();
        return t.isEmpty() ? null : t;
    }
}
