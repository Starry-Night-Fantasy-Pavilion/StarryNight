package com.starrynight.starrynight.system.recommendation.controller;

import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.recommendation.entity.Recommendation;
import com.starrynight.starrynight.system.recommendation.service.RecommendationService;
import lombok.RequiredArgsConstructor;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/recommendations")
@RequiredArgsConstructor
public class RecommendationController {

    private final RecommendationService recommendationService;

    @GetMapping
    public ResponseVO<PageVO<Map<String, Object>>> list(
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size) {
        return ResponseVO.success(recommendationService.listRecommendations(page, size));
    }

    @GetMapping("/{id}")
    public ResponseVO<Map<String, Object>> get(@PathVariable Long id) {
        return ResponseVO.success(recommendationService.getRecommendation(id));
    }

    @PostMapping
    public ResponseVO<Map<String, Object>> create(@RequestBody Recommendation recommendation) {
        return ResponseVO.success(recommendationService.createRecommendation(recommendation));
    }

    @PutMapping("/{id}")
    public ResponseVO<Map<String, Object>> update(@PathVariable Long id, @RequestBody Recommendation recommendation) {
        return ResponseVO.success(recommendationService.updateRecommendation(id, recommendation));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        recommendationService.deleteRecommendation(id);
        return ResponseVO.success(null);
    }

    @GetMapping("/novels/search")
    public ResponseVO<List<Map<String, Object>>> searchNovels(@RequestParam String keyword) {
        return ResponseVO.success(recommendationService.searchNovels(keyword));
    }
}