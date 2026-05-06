package com.starrynight.starrynight.system.consistency.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.character.entity.NovelCharacter;
import com.starrynight.starrynight.system.character.mapper.NovelCharacterMapper;
import com.starrynight.starrynight.system.consistency.entity.ForeshadowingRecord;
import com.starrynight.starrynight.system.consistency.entity.RhythmAnalysis;
import com.starrynight.starrynight.system.consistency.service.EnhancedConsistencyService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@Slf4j
@RestController
@RequestMapping("/api/consistency")
@RequiredArgsConstructor
public class ConsistencyController {

    private final EnhancedConsistencyService consistencyService;
    private final NovelCharacterMapper characterMapper;

    @PostMapping("/check")
    public ResponseVO<Map<String, Object>> checkContent(
            @RequestParam Long userId,
            @RequestParam Long novelId,
            @RequestParam(required = false) Long chapterId,
            @RequestParam String contentType,
            @RequestParam String content) {

        List<NovelCharacter> characters = characterMapper.selectList(
                new com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper<NovelCharacter>()
                        .eq(NovelCharacter::getNovelId, novelId)
        );

        EnhancedConsistencyService.ConsistencyCheckResult result = consistencyService.checkContentWithAI(
                userId, novelId, chapterId, contentType, content, characters, null
        );

        return ResponseVO.success(Map.of(
                "passed", result.getPassed(),
                "issues", result.getIssues()
        ));
    }

    @PostMapping("/foreshadowing/analyze")
    public ResponseVO<EnhancedConsistencyService.ForeshadowingAnalysisResult> analyzeForeshadowing(
            @RequestParam Long userId,
            @RequestParam Long novelId,
            @RequestParam(required = false) Long chapterId,
            @RequestParam String content) {

        EnhancedConsistencyService.ForeshadowingAnalysisResult result =
                consistencyService.analyzeForeshadowing(userId, novelId, chapterId, content);

        return ResponseVO.success(result);
    }

    @GetMapping("/foreshadowing/pending")
    public ResponseVO<List<ForeshadowingRecord>> getPendingForeshadowings(@RequestParam Long novelId) {
        List<ForeshadowingRecord> records = consistencyService.getPendingForeshadowings(novelId);
        return ResponseVO.success(records);
    }

    @PostMapping("/foreshadowing/{id}/resolve")
    public ResponseVO<Void> resolveForeshadowing(
            @PathVariable String id,
            @RequestParam(required = false) Long resolutionChapterId,
            @RequestParam(defaultValue = "3") Integer quality) {
        consistencyService.resolveForeshadowing(id, resolutionChapterId, quality);
        return ResponseVO.success(null);
    }

    @PostMapping("/foreshadowing/{id}/abandon")
    public ResponseVO<Void> abandonForeshadowing(@PathVariable String id) {
        consistencyService.abandonForeshadowing(id, "用户手动废弃");
        return ResponseVO.success(null);
    }

    @PostMapping("/rhythm/analyze")
    public ResponseVO<EnhancedConsistencyService.RhythmAnalysisResult> analyzeRhythm(
            @RequestParam Long userId,
            @RequestParam Long novelId,
            @RequestParam(required = false) Long chapterId,
            @RequestParam String content) {

        EnhancedConsistencyService.RhythmAnalysisResult result =
                consistencyService.analyzeRhythm(userId, novelId, chapterId, content);

        return ResponseVO.success(result);
    }

    @GetMapping("/rhythm/history")
    public ResponseVO<List<RhythmAnalysis>> getRhythmHistory(@RequestParam Long novelId) {
        List<RhythmAnalysis> history = consistencyService.getRhythmHistory(novelId);
        return ResponseVO.success(history);
    }

    @PostMapping("/character/check")
    public ResponseVO<EnhancedConsistencyService.CharacterConsistencyResult> checkCharacterConsistency(
            @RequestParam Long userId,
            @RequestParam Long novelId,
            @RequestParam(required = false) Long chapterId,
            @RequestParam String content) {

        List<NovelCharacter> characters = characterMapper.selectList(
                new com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper<NovelCharacter>()
                        .eq(NovelCharacter::getNovelId, novelId)
        );

        EnhancedConsistencyService.CharacterConsistencyResult result =
                consistencyService.checkCharacterConsistency(userId, novelId, chapterId, content, characters);

        return ResponseVO.success(result);
    }
}
