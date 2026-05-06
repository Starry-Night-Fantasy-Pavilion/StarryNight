package com.starrynight.engine.rhythm;

import lombok.Data;
import org.springframework.stereotype.Component;

import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

@Component
public class RhythmAnalyzer {

    private static final Map<String, Float> POSITIVE_WORDS = new HashMap<>();
    private static final Map<String, Float> NEGATIVE_WORDS = new HashMap<>();
    private static final Map<String, Float> TENSION_WORDS = new HashMap<>();
    private static final Map<String, Float> WARMTH_WORDS = new HashMap<>();

    static {
        POSITIVE_WORDS.put("开心", 0.8f);
        POSITIVE_WORDS.put("高兴", 0.7f);
        POSITIVE_WORDS.put("快乐", 0.8f);
        POSITIVE_WORDS.put("幸福", 0.9f);
        POSITIVE_WORDS.put("温暖", 0.7f);
        POSITIVE_WORDS.put("感动", 0.7f);
        POSITIVE_WORDS.put("希望", 0.6f);

        NEGATIVE_WORDS.put("悲伤", 0.8f);
        NEGATIVE_WORDS.put("痛苦", 0.7f);
        NEGATIVE_WORDS.put("难过", 0.7f);
        NEGATIVE_WORDS.put("伤心", 0.8f);
        NEGATIVE_WORDS.put("绝望", 0.9f);
        NEGATIVE_WORDS.put("失落", 0.6f);

        TENSION_WORDS.put("紧张", 0.8f);
        TENSION_WORDS.put("危机", 0.9f);
        TENSION_WORDS.put("冲突", 0.8f);
        TENSION_WORDS.put("危险", 0.9f);
        TENSION_WORDS.put("战斗", 0.8f);
        TENSION_WORDS.put("对抗", 0.7f);
        TENSION_WORDS.put("威胁", 0.8f);

        WARMTH_WORDS.put("爱", 0.8f);
        WARMTH_WORDS.put("关怀", 0.7f);
        WARMTH_WORDS.put("温柔", 0.7f);
        WARMTH_WORDS.put("友情", 0.8f);
        WARMTH_WORDS.put("亲情", 0.8f);
        WARMTH_WORDS.put("陪伴", 0.6f);
    }

    private static final List<Pattern> CONFLICT_PATTERNS = Arrays.asList(
            Pattern.compile("(争吵|打架|战斗|搏斗|对决)"),
            Pattern.compile("(威胁|恐吓|逼迫)"),
            Pattern.compile("(愤怒|生气|恼火)地"),
            Pattern.compile("(秘密|阴谋|陷阱)"),
            Pattern.compile("(背叛|出卖|欺骗)"),
            Pattern.compile("(受伤|死亡|危机)"),
            Pattern.compile("(矛盾|冲突|对立)"),
            Pattern.compile("!(.+){5,20}"),  // 感叹句
            Pattern.compile("\\?(.+){5,20}\\?")  // 疑问句
    );

    public RhythmResult analyze(Long novelId, List<ChapterContent> chapters) {
        RhythmResult result = new RhythmResult();
        result.setNovelId(novelId);

        List<RhythmResult.ChapterRhythm> chapterRhythms = new ArrayList<>();
        List<EmotionCurve> allEmotions = new ArrayList<>();
        float totalConflicts = 0;

        for (ChapterContent chapter : chapters) {
            RhythmResult.ChapterRhythm chapterRhythm = new RhythmResult.ChapterRhythm();
            chapterRhythm.setChapterNo(chapter.getChapterNo());

            EmotionCurve emotions = analyzeEmotions(chapter.getContent(), chapter.getChapterNo());
            chapterRhythm.setEmotions(emotions);
            allEmotions.add(emotions);

            ConflictDensity conflictDensity = calculateConflictDensity(chapter.getContent(), chapter.getChapterNo());
            chapterRhythm.setConflictDensity(conflictDensity);
            totalConflicts += conflictDensity.getConflictCount();

            RhythmResult.RetentionPrediction retention = predictRetention(chapter, emotions, conflictDensity);
            chapterRhythm.setRetentionPrediction(retention);

            chapterRhythms.add(chapterRhythm);
        }

        result.setChapters(chapterRhythms);
        result.setSuggestions(generateSuggestions(chapterRhythms));
        result.setOverallScore(calculateOverallScore(chapterRhythms));
        result.setSummary(generateSummary(allEmotions, totalConflicts, chapterRhythms.size()));

        return result;
    }

    public EmotionCurve analyzeEmotions(String content, Integer chapterNo) {
        EmotionCurve emotion = new EmotionCurve();
        emotion.setChapterNo(chapterNo);

        float anticipation = calculateWordScore(content, POSITIVE_WORDS) * 0.5f + 0.3f;
        float tension = calculateWordScore(content, TENSION_WORDS);
        float warmth = calculateWordScore(content, WARMTH_WORDS);
        float sadness = calculateWordScore(content, NEGATIVE_WORDS);

        float total = anticipation + tension + warmth + sadness;
        if (total > 1.5f) {
            float scale = 1.5f / total;
            anticipation *= scale;
            tension *= scale;
            warmth *= scale;
            sadness *= scale;
        }

        emotion.setAnticipation(Math.min(1f, anticipation));
        emotion.setTension(Math.min(1f, tension));
        emotion.setWarmth(Math.min(1f, warmth));
        emotion.setSadness(Math.min(1f, sadness));

        emotion.setCurve(generateEmotionCurve(content));
        emotion.setOverallTrend(determineTrend(emotion));

        return emotion;
    }

    private float calculateWordScore(String content, Map<String, Float> wordMap) {
        float score = 0f;
        for (Map.Entry<String, Float> entry : wordMap.entrySet()) {
            Pattern pattern = Pattern.compile(entry.getKey());
            Matcher matcher = pattern.matcher(content);
            while (matcher.find()) {
                score += entry.getValue();
            }
        }
        return score;
    }

    private Float[] generateEmotionCurve(String content) {
        int segmentCount = 10;
        Float[] curve = new Float[segmentCount];
        int segmentLength = content.length() / segmentCount;

        for (int i = 0; i < segmentCount; i++) {
            int start = i * segmentLength;
            int end = Math.min(start + segmentLength, content.length());
            String segment = content.substring(start, end);

            float segmentScore = 0f;
            segmentScore += calculateWordScore(segment, TENSION_WORDS) * 0.4f;
            segmentScore += calculateWordScore(segment, POSITIVE_WORDS) * 0.3f;
            segmentScore += calculateWordScore(segment, NEGATIVE_WORDS) * 0.3f;

            curve[i] = Math.min(1f, segmentScore);
        }

        return curve;
    }

    private String determineTrend(EmotionCurve emotion) {
        float intensity = emotion.getOverallIntensity();
        if (intensity < 0.3f) return "平缓";
        if (intensity < 0.6f) return "起伏";
        return "高潮";
    }

    public ConflictDensity calculateConflictDensity(String content, Integer chapterNo) {
        ConflictDensity density = new ConflictDensity();
        density.setChapterNo(chapterNo);

        List<ConflictDensity.Conflict> conflicts = new ArrayList<>();
        int wordCount = content.length() / 2;

        for (Pattern pattern : CONFLICT_PATTERNS) {
            Matcher matcher = pattern.matcher(content);
            while (matcher.find()) {
                ConflictDensity.Conflict conflict = new ConflictDensity.Conflict();
                conflict.setType(classifyConflict(matcher.group()));
                conflict.setIntensity(calculateConflictIntensity(matcher.group()));
                conflict.setPosition((float) matcher.start() / content.length());
                conflict.setDescription(matcher.group());
                conflicts.add(conflict);
            }
        }

        int conflictCount = conflicts.size();
        float intensitySum = conflicts.stream()
                .mapToInt(ConflictDensity.Conflict::getIntensity)
                .sum();

        density.setConflictCount(conflictCount);
        density.setIntensitySum(intensitySum);
        density.setDensityPerThousandWords((conflictCount / (float) wordCount) * 1000);
        density.setConflicts(conflicts);
        density.setIntensityLevel(density.getIntensityLevel());

        return density;
    }

    private ConflictDensity.Conflict.ConflictType classifyConflict(String text) {
        if (text.contains("战斗") || text.contains("打架") || text.contains("搏斗")) {
            return ConflictDensity.Conflict.ConflictType.PHYSICAL;
        }
        if (text.contains("争吵") || text.contains("质问") || text.contains("威胁")) {
            return ConflictDensity.Conflict.ConflictType.VERBAL;
        }
        if (text.contains("秘密") || text.contains("阴谋") || text.contains("心理")) {
            return ConflictDensity.Conflict.ConflictType.PSYCHOLOGICAL;
        }
        return ConflictDensity.Conflict.ConflictType.SITUATIONAL;
    }

    private int calculateConflictIntensity(String text) {
        int intensity = 2;
        if (text.contains("激烈") || text.contains("严重")) intensity = 4;
        else if (text.contains("轻微") || text.contains("小")) intensity = 1;
        if (text.contains("死亡") || text.contains("毁灭")) intensity = 5;
        return intensity;
    }

    private RhythmResult.RetentionPrediction predictRetention(ChapterContent chapter, EmotionCurve emotions, ConflictDensity conflicts) {
        RhythmResult.RetentionPrediction prediction = new RhythmResult.RetentionPrediction();
        prediction.setChapterNo(chapter.getChapterNo());

        float baseScore = 0.7f;
        float tensionBonus = emotions.getTension() * 0.15f;
        float conflictBonus = Math.min(0.1f, conflicts.getDensityPerThousandWords() * 0.01f);
        float warmthBonus = emotions.getWarmth() * 0.05f;

        float retentionScore = Math.min(1f, baseScore + tensionBonus + conflictBonus + warmthBonus);
        float churnRate = 1f - retentionScore;

        prediction.setRetentionScore(retentionScore);
        prediction.setPredictedChurnRate(churnRate);
        prediction.setRating(scoreToStars(retentionScore));
        prediction.setSuggestions(generateRetentionSuggestions(retentionScore, emotions, conflicts));

        return prediction;
    }

    private String scoreToStars(float score) {
        int stars = (int) (score * 5);
        return "★".repeat(stars) + "☆".repeat(5 - stars);
    }

    private List<String> generateRetentionSuggestions(float score, EmotionCurve emotions, ConflictDensity conflicts) {
        List<String> suggestions = new ArrayList<>();

        if (emotions.getTension() < 0.3f) {
            suggestions.add("增加紧张场景或冲突事件");
        }
        if (emotions.getWarmth() > 0.8f) {
            suggestions.add("适当增加温馨场景但不要过度");
        }
        if (conflicts.getDensityPerThousandWords() < 2f) {
            suggestions.add("增加更多冲突情节");
        }
        if (conflicts.getDensityPerThousandWords() > 8f) {
            suggestions.add("适当减少冲突密度，避免过于密集");
        }

        return suggestions;
    }

    private List<RhythmResult.RhythmSuggestion> generateSuggestions(List<RhythmResult.ChapterRhythm> chapters) {
        List<RhythmResult.RhythmSuggestion> suggestions = new ArrayList<>();

        for (int i = 1; i < chapters.size(); i++) {
            RhythmResult.ChapterRhythm prev = chapters.get(i - 1);
            RhythmResult.ChapterRhythm curr = chapters.get(i);

            float tensionDrop = prev.getEmotions().getTension() - curr.getEmotions().getTension();
            if (tensionDrop > 0.3f) {
                RhythmResult.RhythmSuggestion suggestion = new RhythmResult.RhythmSuggestion();
                suggestion.setType("emotion");
                suggestion.setPriority("high");
                suggestion.setTargetChapterNo(curr.getChapterNo());
                suggestion.setDescription("紧张感从第" + prev.getChapterNo() + "章的" +
                        String.format("%.1f", prev.getEmotions().getTension()) + "骤降至" +
                        String.format("%.1f", curr.getEmotions().getTension()));
                suggestion.setAction("add_conflict");
                suggestion.setEstimatedImpact("预计可提升留存率5-10%");
                suggestions.add(suggestion);
            }

            if (prev.getRetentionPrediction().getRetentionScore() < 0.6f) {
                RhythmResult.RhythmSuggestion suggestion = new RhythmResult.RhythmSuggestion();
                suggestion.setType("retention");
                suggestion.setPriority("medium");
                suggestion.setTargetChapterNo(prev.getChapterNo());
                suggestion.setDescription("第" + prev.getChapterNo() + "章预测留存率偏低");
                suggestion.setAction("add_hook");
                suggestion.setEstimatedImpact("添加悬念钩子可提升留存");
                suggestions.add(suggestion);
            }
        }

        return suggestions;
    }

    private Float calculateOverallScore(List<RhythmResult.ChapterRhythm> chapters) {
        if (chapters.isEmpty()) return 0f;

        float total = 0f;
        for (RhythmResult.ChapterRhythm chapter : chapters) {
            total += chapter.getRetentionPrediction().getRetentionScore();
        }
        return total / chapters.size();
    }

    private RhythmResult.RhythmSummary generateSummary(List<EmotionCurve> emotions, float totalConflicts, int chapterCount) {
        RhythmResult.RhythmSummary summary = new RhythmResult.RhythmSummary();

        float avgTension = (float) (emotions.stream().mapToDouble(EmotionCurve::getTension).sum() / emotions.size());
        float avgWarmth = (float) (emotions.stream().mapToDouble(EmotionCurve::getWarmth).sum() / emotions.size());

        summary.setAverageTension(avgTension);
        summary.setAverageWarmth(avgWarmth);
        summary.setTotalConflicts(totalConflicts);

        if (avgTension > 0.5f && avgWarmth > 0.3f) {
            summary.setPacingAssessment("节奏良好，张弛有度");
        } else if (avgTension > 0.7f) {
            summary.setPacingAssessment("节奏偏紧张，建议适当放缓");
        } else if (avgTension < 0.3f) {
            summary.setPacingAssessment("节奏偏平缓，建议增加冲突");
        } else {
            summary.setPacingAssessment("节奏基本合理");
        }

        summary.setStrengths(new ArrayList<>());
        summary.setWeaknesses(new ArrayList<>());

        if (avgWarmth > 0.4f) {
            summary.getStrengths().add("情感描写细腻，温馨场景动人");
        }
        if (avgTension > 0.5f) {
            summary.getStrengths().add("情节紧凑，悬念感强");
        }
        if (avgTension < 0.3f) {
            summary.getWeaknesses().add("紧张感不足，建议增加冲突");
        }
        if (avgWarmth < 0.2f) {
            summary.getWeaknesses().add("情感描写偏少，建议增加人物互动");
        }

        return summary;
    }

    @Data
    public static class ChapterContent {
        private Integer chapterNo;
        private String content;
        private Integer wordCount;

        public Integer getWordCount() {
            return wordCount != null ? wordCount : (content != null ? content.length() / 2 : 0);
        }
    }
}