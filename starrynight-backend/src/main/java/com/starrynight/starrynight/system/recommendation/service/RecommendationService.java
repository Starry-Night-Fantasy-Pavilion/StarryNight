package com.starrynight.starrynight.system.recommendation.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.metadata.IPage;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.novel.entity.Novel;
import com.starrynight.starrynight.system.novel.mapper.NovelMapper;
import com.starrynight.starrynight.system.recommendation.entity.Recommendation;
import com.starrynight.starrynight.system.recommendation.mapper.RecommendationMapper;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@Service
@RequiredArgsConstructor
public class RecommendationService {

    private final RecommendationMapper recommendationMapper;
    private final NovelMapper novelMapper;

    public PageVO<Map<String, Object>> listRecommendations(int page, int size) {
        LambdaQueryWrapper<Recommendation> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(Recommendation::getDeleted, 0)
               .orderByDesc(Recommendation::getSort)
               .orderByDesc(Recommendation::getCreateTime);

        IPage<Recommendation> pageResult = recommendationMapper.selectPage(new Page<>(page, size), wrapper);
        List<Map<String, Object>> records = pageResult.getRecords().stream().map(this::toMap).collect(Collectors.toList());
        return PageVO.of(pageResult.getTotal(), records, (long) page, (long) size);
    }

    public Map<String, Object> getRecommendation(Long id) {
        Recommendation recommendation = recommendationMapper.selectById(id);
        return toMap(recommendation);
    }

    @Transactional
    public Map<String, Object> createRecommendation(Recommendation recommendation) {
        if (recommendation.getSort() == null) {
            recommendation.setSort(0);
        }
        if (recommendation.getStatus() == null) {
            recommendation.setStatus(1);
        }

        Novel novel = novelMapper.selectById(recommendation.getNovelId());
        if (novel != null) {
            recommendation.setNovelTitle(novel.getTitle());
            recommendation.setCover(novel.getCover());
        }

        recommendation.setDeleted(0);
        recommendation.setCreateTime(LocalDateTime.now());
        recommendation.setUpdateTime(LocalDateTime.now());
        recommendationMapper.insert(recommendation);

        return toMap(recommendation);
    }

    @Transactional
    public Map<String, Object> updateRecommendation(Long id, Recommendation recommendation) {
        Recommendation existing = recommendationMapper.selectById(id);
        if (existing == null) {
            throw new RuntimeException("推荐不存在");
        }

        if (recommendation.getTitle() != null) {
            existing.setTitle(recommendation.getTitle());
        }
        if (recommendation.getType() != null) {
            existing.setType(recommendation.getType());
        }
        if (recommendation.getNovelId() != null) {
            existing.setNovelId(recommendation.getNovelId());
            Novel novel = novelMapper.selectById(recommendation.getNovelId());
            if (novel != null) {
                existing.setNovelTitle(novel.getTitle());
                existing.setCover(novel.getCover());
            }
        }
        if (recommendation.getPosition() != null) {
            existing.setPosition(recommendation.getPosition());
        }
        if (recommendation.getSort() != null) {
            existing.setSort(recommendation.getSort());
        }
        if (recommendation.getStartTime() != null) {
            existing.setStartTime(recommendation.getStartTime());
        }
        if (recommendation.getEndTime() != null) {
            existing.setEndTime(recommendation.getEndTime());
        }
        if (recommendation.getStatus() != null) {
            existing.setStatus(recommendation.getStatus());
        }

        existing.setUpdateTime(LocalDateTime.now());
        recommendationMapper.updateById(existing);

        return toMap(existing);
    }

    @Transactional
    public void deleteRecommendation(Long id) {
        Recommendation recommendation = recommendationMapper.selectById(id);
        if (recommendation != null) {
            recommendation.setDeleted(1);
            recommendationMapper.updateById(recommendation);
        }
    }

    public List<Map<String, Object>> searchNovels(String keyword) {
        LambdaQueryWrapper<Novel> wrapper = new LambdaQueryWrapper<>();
        wrapper.like(Novel::getTitle, keyword)
               .orderByDesc(Novel::getCreateTime)
               .last("LIMIT 10");

        return novelMapper.selectList(wrapper).stream()
                .map(novel -> {
                    Map<String, Object> m = new java.util.HashMap<>();
                    m.put("id", novel.getId());
                    m.put("title", novel.getTitle() != null ? novel.getTitle() : "");
                    m.put("author", "");
                    return m;
                })
                .collect(Collectors.toList());
    }

    private Map<String, Object> toMap(Recommendation r) {
        if (r == null) return Map.of();
        return Map.ofEntries(
                Map.entry("id", r.getId()),
                Map.entry("title", r.getTitle() != null ? r.getTitle() : ""),
                Map.entry("type", r.getType() != null ? r.getType() : ""),
                Map.entry("novelId", r.getNovelId() != null ? r.getNovelId() : 0),
                Map.entry("novelTitle", r.getNovelTitle() != null ? r.getNovelTitle() : ""),
                Map.entry("cover", r.getCover() != null ? r.getCover() : ""),
                Map.entry("position", r.getPosition() != null ? r.getPosition() : ""),
                Map.entry("sort", r.getSort() != null ? r.getSort() : 0),
                Map.entry("startTime", r.getStartTime() != null ? r.getStartTime().toString() : ""),
                Map.entry("endTime", r.getEndTime() != null ? r.getEndTime().toString() : ""),
                Map.entry("status", r.getStatus() != null ? r.getStatus() : 0),
                Map.entry("createTime", r.getCreateTime() != null ? r.getCreateTime().toString() : ""),
                Map.entry("updateTime", r.getUpdateTime() != null ? r.getUpdateTime().toString() : "")
        );
    }
}