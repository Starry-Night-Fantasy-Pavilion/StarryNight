package com.starrynight.starrynight.system.novel.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.bookstore.entity.BookstoreBook;
import com.starrynight.starrynight.system.bookstore.mapper.BookstoreBookMapper;
import com.starrynight.starrynight.system.novel.dto.NovelCategoryMutateDTO;
import com.starrynight.starrynight.system.novel.dto.NovelCategoryRowDTO;
import com.starrynight.starrynight.system.novel.dto.NovelCategoryTreeNodeDTO;
import com.starrynight.starrynight.system.novel.entity.Novel;
import com.starrynight.starrynight.system.novel.entity.NovelCategory;
import com.starrynight.starrynight.system.novel.mapper.NovelCategoryMapper;
import com.starrynight.starrynight.system.novel.mapper.NovelMapper;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.util.ArrayList;
import java.util.Comparator;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@Service
@RequiredArgsConstructor
public class NovelCategoryService {

    private final NovelCategoryMapper novelCategoryMapper;
    private final NovelMapper novelMapper;
    private final BookstoreBookMapper bookstoreBookMapper;

    public List<NovelCategoryRowDTO> listRows() {
        List<NovelCategory> all = novelCategoryMapper.selectList(
                new LambdaQueryWrapper<NovelCategory>().orderByAsc(NovelCategory::getSort).orderByAsc(NovelCategory::getId));
        Map<Long, NovelCategory> byId = all.stream().collect(Collectors.toMap(NovelCategory::getId, c -> c));
        List<NovelCategoryRowDTO> rows = new ArrayList<>();
        for (NovelCategory c : all) {
            if (c.getParentId() == null) {
                rows.add(NovelCategoryRowDTO.builder()
                        .id(c.getId())
                        .parentId(null)
                        .level1Name(c.getName())
                        .level2Name("—")
                        .sort(c.getSort())
                        .status(c.getStatus())
                        .novelCount(countNovelsForCategory(c.getId(), all))
                        .bookCount(countBooksForCategory(c.getId(), all))
                        .build());
            } else {
                NovelCategory p = byId.get(c.getParentId());
                rows.add(NovelCategoryRowDTO.builder()
                        .id(c.getId())
                        .parentId(c.getParentId())
                        .level1Name(p != null ? p.getName() : "?")
                        .level2Name(c.getName())
                        .sort(c.getSort())
                        .status(c.getStatus())
                        .novelCount(countNovelsExact(c.getId()))
                        .bookCount(countBooksExact(c.getId()))
                        .build());
            }
        }
        rows.sort(Comparator.comparing(NovelCategoryRowDTO::getSort, Comparator.nullsLast(Integer::compareTo))
                .thenComparing(NovelCategoryRowDTO::getId));
        return rows;
    }

    public List<NovelCategoryTreeNodeDTO> tree() {
        List<NovelCategory> enabled = novelCategoryMapper.selectList(
                new LambdaQueryWrapper<NovelCategory>()
                        .eq(NovelCategory::getStatus, 1)
                        .orderByAsc(NovelCategory::getSort)
                        .orderByAsc(NovelCategory::getId));
        List<NovelCategory> roots = enabled.stream().filter(c -> c.getParentId() == null).collect(Collectors.toList());
        Map<Long, List<NovelCategory>> byParent = enabled.stream()
                .filter(c -> c.getParentId() != null)
                .collect(Collectors.groupingBy(NovelCategory::getParentId));
        List<NovelCategoryTreeNodeDTO> out = new ArrayList<>();
        for (NovelCategory r : roots) {
            NovelCategoryTreeNodeDTO n = new NovelCategoryTreeNodeDTO();
            n.setId(r.getId());
            n.setName(r.getName());
            for (NovelCategory ch : byParent.getOrDefault(r.getId(), List.of())) {
                NovelCategoryTreeNodeDTO cn = new NovelCategoryTreeNodeDTO();
                cn.setId(ch.getId());
                cn.setName(ch.getName());
                n.getChildren().add(cn);
            }
            out.add(n);
        }
        return out;
    }

    @Transactional(rollbackFor = Exception.class)
    public NovelCategoryRowDTO create(NovelCategoryMutateDTO dto) {
        String l1 = dto.getLevel1Name().trim();
        String l2 = StringUtils.hasText(dto.getLevel2Name()) ? dto.getLevel2Name().trim() : null;
        int sort = dto.getSort() != null ? dto.getSort() : 0;
        int status = dto.getStatus() != null ? dto.getStatus() : 1;

        if (!StringUtils.hasText(l2)) {
            NovelCategory cat = new NovelCategory();
            cat.setParentId(null);
            cat.setName(l1);
            cat.setCode(ensureUniqueCode(slugPart(l1)));
            cat.setSort(sort);
            cat.setStatus(status);
            novelCategoryMapper.insert(cat);
            return toRow(cat, null);
        }

        NovelCategory parent = novelCategoryMapper.selectOne(
                new LambdaQueryWrapper<NovelCategory>()
                        .isNull(NovelCategory::getParentId)
                        .eq(NovelCategory::getName, l1)
                        .last("LIMIT 1"));
        if (parent == null) {
            parent = new NovelCategory();
            parent.setParentId(null);
            parent.setName(l1);
            parent.setCode(ensureUniqueCode(slugPart(l1)));
            parent.setSort(0);
            parent.setStatus(1);
            novelCategoryMapper.insert(parent);
        }

        NovelCategory child = new NovelCategory();
        child.setParentId(parent.getId());
        child.setName(l2);
        child.setCode(ensureUniqueCode(slugPart(parent.getCode() + "_" + l2)));
        child.setSort(sort);
        child.setStatus(status);
        novelCategoryMapper.insert(child);
        return toRow(child, parent);
    }

    @Transactional(rollbackFor = Exception.class)
    public NovelCategoryRowDTO update(Long id, NovelCategoryMutateDTO dto) {
        NovelCategory cat = novelCategoryMapper.selectById(id);
        if (cat == null) {
            throw new ResourceNotFoundException("分类不存在");
        }
        String l1 = dto.getLevel1Name().trim();
        String l2 = StringUtils.hasText(dto.getLevel2Name()) ? dto.getLevel2Name().trim() : null;
        int sort = dto.getSort() != null ? dto.getSort() : cat.getSort();
        int status = dto.getStatus() != null ? dto.getStatus() : cat.getStatus();

        if (cat.getParentId() == null) {
            if (StringUtils.hasText(l2)) {
                throw new BusinessException("一级分类请勿填写二级名称；请删除后重新添加二级分类");
            }
            cat.setName(l1);
            cat.setSort(sort);
            cat.setStatus(status);
            novelCategoryMapper.updateById(cat);
            return toRow(cat, null);
        }

        NovelCategory parent = novelCategoryMapper.selectById(cat.getParentId());
        if (parent == null) {
            throw new BusinessException("父级分类不存在");
        }
        if (!parent.getName().equals(l1)) {
            parent.setName(l1);
            novelCategoryMapper.updateById(parent);
        }
        cat.setName(l2 != null ? l2 : cat.getName());
        cat.setSort(sort);
        cat.setStatus(status);
        novelCategoryMapper.updateById(cat);
        parent = novelCategoryMapper.selectById(cat.getParentId());
        return toRow(cat, parent);
    }

    @Transactional(rollbackFor = Exception.class)
    public void delete(Long id) {
        NovelCategory cat = novelCategoryMapper.selectById(id);
        if (cat == null) {
            throw new ResourceNotFoundException("分类不存在");
        }
        Long childCnt = novelCategoryMapper.selectCount(
                new LambdaQueryWrapper<NovelCategory>().eq(NovelCategory::getParentId, id));
        if (childCnt != null && childCnt > 0) {
            throw new BusinessException("请先删除其下的二级分类");
        }
        if (referencesExist(id)) {
            throw new BusinessException("分类仍被作品或书城书籍引用，无法删除");
        }
        novelCategoryMapper.deleteById(id);
    }

    private boolean referencesExist(Long categoryId) {
        Long n = novelMapper.selectCount(new LambdaQueryWrapper<Novel>().eq(Novel::getCategoryId, categoryId));
        if (n != null && n > 0) {
            return true;
        }
        Long b = bookstoreBookMapper.selectCount(new LambdaQueryWrapper<BookstoreBook>().eq(BookstoreBook::getCategoryId, categoryId));
        return b != null && b > 0;
    }

    private NovelCategoryRowDTO toRow(NovelCategory c, NovelCategory parent) {
        if (c.getParentId() == null) {
            List<NovelCategory> all = novelCategoryMapper.selectList(new LambdaQueryWrapper<>());
            return NovelCategoryRowDTO.builder()
                    .id(c.getId())
                    .parentId(null)
                    .level1Name(c.getName())
                    .level2Name("—")
                    .sort(c.getSort())
                    .status(c.getStatus())
                    .novelCount(countNovelsForCategory(c.getId(), all))
                    .bookCount(countBooksForCategory(c.getId(), all))
                    .build();
        }
        NovelCategory p = parent != null ? parent : novelCategoryMapper.selectById(c.getParentId());
        return NovelCategoryRowDTO.builder()
                .id(c.getId())
                .parentId(c.getParentId())
                .level1Name(p != null ? p.getName() : "?")
                .level2Name(c.getName())
                .sort(c.getSort())
                .status(c.getStatus())
                .novelCount(countNovelsExact(c.getId()))
                .bookCount(countBooksExact(c.getId()))
                .build();
    }

    private long countNovelsExact(Long categoryId) {
        Long n = novelMapper.selectCount(new LambdaQueryWrapper<Novel>().eq(Novel::getCategoryId, categoryId));
        return n == null ? 0L : n;
    }

    private long countBooksExact(Long categoryId) {
        Long n = bookstoreBookMapper.selectCount(new LambdaQueryWrapper<BookstoreBook>().eq(BookstoreBook::getCategoryId, categoryId));
        return n == null ? 0L : n;
    }

    private long countNovelsForCategory(Long rootId, List<NovelCategory> all) {
        List<Long> childIds = all.stream()
                .filter(c -> rootId.equals(c.getParentId()))
                .map(NovelCategory::getId)
                .collect(Collectors.toList());
        long n = countNovelsExact(rootId);
        for (Long cid : childIds) {
            n += countNovelsExact(cid);
        }
        return n;
    }

    private long countBooksForCategory(Long rootId, List<NovelCategory> all) {
        List<Long> childIds = all.stream()
                .filter(c -> rootId.equals(c.getParentId()))
                .map(NovelCategory::getId)
                .collect(Collectors.toList());
        long n = countBooksExact(rootId);
        for (Long cid : childIds) {
            n += countBooksExact(cid);
        }
        return n;
    }

    private static String slugPart(String raw) {
        if (raw == null) {
            return "x";
        }
        String s = raw.trim().toLowerCase().replaceAll("\\s+", "_");
        s = s.replaceAll("[^a-z0-9_]", "");
        if (s.isEmpty()) {
            return "c" + Integer.toHexString(raw.trim().hashCode());
        }
        if (s.length() > 32) {
            s = s.substring(0, 32);
        }
        return s;
    }

    private String ensureUniqueCode(String base) {
        String c = base;
        int n = 0;
        while (true) {
            Long cnt = novelCategoryMapper.selectCount(new LambdaQueryWrapper<NovelCategory>().eq(NovelCategory::getCode, c));
            if (cnt == null || cnt == 0) {
                return c;
            }
            n++;
            c = base + "_" + n;
        }
    }
}
