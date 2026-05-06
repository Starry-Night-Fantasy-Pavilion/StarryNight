package com.starrynight.starrynight.system.consistency.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.engine.rhythm.RhythmAnalyzer;
import com.starrynight.engine.rhythm.RhythmResult;
import com.starrynight.starrynight.system.consistency.entity.RhythmAnalysis;
import com.starrynight.starrynight.system.consistency.mapper.RhythmAnalysisMapper;
import com.starrynight.starrynight.system.novel.entity.NovelChapter;
import com.starrynight.starrynight.system.novel.repository.NovelChapterRepository;
import com.starrynight.starrynight.system.novel.repository.NovelRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.math.BigDecimal;
import java.time.LocalDateTime;
import java.util.List;
import java.util.stream.Collectors;

@Service
public class RhythmAnalysisService {

    @Autowired
    private RhythmAnalyzer rhythmAnalyzer;

    @Autowired
    private RhythmAnalysisMapper rhythmAnalysisMapper;

    @Autowired
    private NovelChapterRepository chapterRepository;

    @Autowired
    private NovelRepository novelRepository;

    public RhythmResult analyzeNovel(Long novelId) {
        return analyzeChapters(novelId, null, null);
    }

    public RhythmResult analyzeChapters(Long novelId, Integer startChapter, Integer endChapter) {
        LambdaQueryWrapper<NovelChapter> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(NovelChapter::getNovelId, novelId);

        if (startChapter != null) {
            wrapper.ge(NovelChapter::getChapterOrder, startChapter);
        }
        if (endChapter != null) {
            wrapper.le(NovelChapter::getChapterOrder, endChapter);
        }

        wrapper.orderByAsc(NovelChapter::getChapterOrder);

        List<NovelChapter> chapters = chapterRepository.selectList(wrapper);

        List<RhythmAnalyzer.ChapterContent> chapterContents = chapters.stream()
                .map(ch -> {
                    RhythmAnalyzer.ChapterContent content = new RhythmAnalyzer.ChapterContent();
                    content.setChapterNo(ch.getChapterOrder() != null ? ch.getChapterOrder() : 0);
                    content.setContent(ch.getContent());
                    return content;
                })
                .collect(Collectors.toList());

        RhythmResult result = rhythmAnalyzer.analyze(novelId, chapterContents);

        saveAnalysisResult(novelId, result);

        return result;
    }

    public RhythmResult analyzeSingleChapter(Long novelId, Integer chapterNo, String content) {
        RhythmAnalyzer.ChapterContent chapterContent = new RhythmAnalyzer.ChapterContent();
        chapterContent.setChapterNo(chapterNo);
        chapterContent.setContent(content);

        return rhythmAnalyzer.analyze(novelId, List.of(chapterContent));
    }

    private void saveAnalysisResult(Long novelId, RhythmResult result) {
        RhythmAnalysis analysis = new RhythmAnalysis();
        analysis.setNovelId(novelId);
        analysis.setAnalysisType("novel_aggregate");
        analysis.setEmotionCurve(serializeResult(result));
        float overall = result.getOverallScore() != null ? result.getOverallScore() : 0f;
        analysis.setRetentionScore(BigDecimal.valueOf(overall));
        analysis.setCreateTime(LocalDateTime.now());

        rhythmAnalysisMapper.insert(analysis);
    }

    private String serializeResult(RhythmResult result) {
        StringBuilder sb = new StringBuilder();
        sb.append("{\"chapters\":[");
        for (int i = 0; i < result.getChapters().size(); i++) {
            if (i > 0) sb.append(",");
            RhythmResult.ChapterRhythm ch = result.getChapters().get(i);
            sb.append("{\"chapterNo\":").append(ch.getChapterNo());
            sb.append(",\"tension\":").append(ch.getEmotions().getTension());
            sb.append(",\"warmth\":").append(ch.getEmotions().getWarmth());
            sb.append(",\"retentionScore\":").append(ch.getRetentionPrediction().getRetentionScore());
            sb.append("}");
        }
        sb.append("],\"overallScore\":").append(result.getOverallScore()).append("}");
        return sb.toString();
    }

    public List<RhythmAnalysis> getAnalysisHistory(Long novelId) {
        LambdaQueryWrapper<RhythmAnalysis> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(RhythmAnalysis::getNovelId, novelId)
                .orderByDesc(RhythmAnalysis::getCreateTime);
        return rhythmAnalysisMapper.selectList(wrapper);
    }
}