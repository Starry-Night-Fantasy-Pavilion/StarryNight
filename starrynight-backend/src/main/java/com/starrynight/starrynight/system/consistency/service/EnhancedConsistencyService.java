package com.starrynight.starrynight.system.consistency.service;

import com.alibaba.fastjson2.JSON;
import com.alibaba.fastjson2.JSONArray;
import com.alibaba.fastjson2.JSONObject;
import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.services.ai.AiBillingService;
import com.starrynight.starrynight.services.ai.model.ChatCompletionRequest;
import com.starrynight.starrynight.system.character.entity.NovelCharacter;
import com.starrynight.starrynight.system.character.mapper.NovelCharacterMapper;
import com.starrynight.starrynight.system.consistency.entity.*;
import com.starrynight.starrynight.system.consistency.mapper.*;
import com.starrynight.starrynight.system.novel.entity.Novel;
import com.starrynight.starrynight.system.novel.entity.NovelChapter;
import com.starrynight.starrynight.system.novel.mapper.NovelChapterMapper;
import com.starrynight.starrynight.system.novel.mapper.NovelMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.time.LocalDateTime;
import java.util.*;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

@Slf4j
@Service
@RequiredArgsConstructor
public class EnhancedConsistencyService {

    private final AiConsistencyCheckMapper checkMapper;
    private final ForeshadowingRecordMapper foreshadowingMapper;
    private final RhythmAnalysisMapper rhythmMapper;
    private final CharacterConsistencyMapper characterConsistencyMapper;
    private final NovelMapper novelMapper;
    private final NovelChapterMapper chapterMapper;
    private final NovelCharacterMapper characterMapper;
    private final AiBillingService aiBillingService;

    private static final String CHECK_TYPE_RULE = "rule";
    private static final String CHECK_TYPE_TIMELINE = "timeline";
    private static final String CHECK_TYPE_PERSONALITY = "personality";
    private static final String CHECK_TYPE_FORESHADOWING = "foreshadowing";
    private static final String CHECK_TYPE_RHYTHM = "rhythm";

    @Transactional(rollbackFor = Exception.class)
    public ConsistencyCheckResult checkContentWithAI(Long userId, Long novelId, Long chapterId,
                                                     String contentType, String content,
                                                     List<NovelCharacter> characters,
                                                     List<String> worldRules) {
        long startTime = System.currentTimeMillis();

        ConsistencyCheckResult result = new ConsistencyCheckResult();
        result.setPassed(true);
        result.setIssues(new ArrayList<>());

        if (content == null || content.isBlank()) {
            addIssue(result, "content", "high", "生成内容为空", "请补充写作意图或提高生成长度");
            result.setPassed(false);
            return result;
        }

        String novelPrompt = buildNovelContextPrompt(novelId, characters, worldRules);

        List<String> checkTypes = Arrays.asList(CHECK_TYPE_RULE, CHECK_TYPE_TIMELINE, CHECK_TYPE_PERSONALITY);

        for (String checkType : checkTypes) {
            ConsistencyCheckResult typeResult = performAICheck(content, checkType, novelPrompt, content);
            if (typeResult != null && typeResult.getIssues() != null) {
                result.getIssues().addAll(typeResult.getIssues());
            }
        }

        if (!result.getIssues().isEmpty()) {
            boolean hasHighSeverity = result.getIssues().stream()
                    .anyMatch(i -> "high".equalsIgnoreCase(i.getSeverity()));
            result.setPassed(!hasHighSeverity);
        }

        long processingTime = System.currentTimeMillis() - startTime;
        saveCheckRecord(userId, novelId, chapterId, contentType, "combined", result, processingTime);

        return result;
    }

    public ForeshadowingAnalysisResult analyzeForeshadowing(Long userId, Long novelId, Long chapterId, String content) {
        ForeshadowingAnalysisResult result = new ForeshadowingAnalysisResult();
        result.setDetectedForeshadowings(new ArrayList<>());

        List<String> hintKeywords = Arrays.asList(
                "注意", "发现", "奇怪", "异常", "预示", "征兆", "暗示",
                "不由得", "突然", "忽然", "隐约", "似乎", "好像"
        );

        String[] sentences = content.split("[。！？.!?]");

        for (String sentence : sentences) {
            if (sentence.length() < 20) continue;

            int hintLevel = 0;
            String matchedKeyword = null;

            for (String keyword : hintKeywords) {
                if (sentence.contains(keyword)) {
                    hintLevel++;
                    matchedKeyword = keyword;
                }
            }

            if (hintLevel > 0) {
                ForeshadowingItem item = new ForeshadowingItem();
                item.setContent(sentence.trim());
                item.setHintLevel(Math.min(hintLevel, 3));
                item.setHintKeyword(matchedKeyword);
                item.setType(detectForeshadowingType(sentence));
                item.setPosition(sentence.indexOf(matchedKeyword));
                result.getDetectedForeshadowings().add(item);
            }
        }

        if (!result.getDetectedForeshadowings().isEmpty()) {
            saveForeshadowings(userId, novelId, chapterId, result.getDetectedForeshadowings());
        }

        List<ForeshadowingRecord> pending = foreshadowingMapper.findPendingByNovelId(novelId);
        result.setPendingCount(pending.size());
        result.setWarnings(new ArrayList<>());

        Integer currentChapterOrder = null;
        if (chapterId != null) {
            NovelChapter current = chapterMapper.selectById(chapterId);
            if (current != null) {
                currentChapterOrder = current.getChapterOrder();
            }
        }

        for (ForeshadowingRecord record : pending) {
            if (record.getChapterNo() != null && currentChapterOrder != null) {
                int distance = Math.abs(currentChapterOrder - record.getChapterNo());
                if (distance > 10) {
                    ForeshadowingWarning warning = new ForeshadowingWarning();
                    warning.setForeshadowingId(record.getId());
                    warning.setContent(record.getSetupContent());
                    warning.setWarningType("EXPIRY_SOON");
                    warning.setMessage("伏笔已设置" + distance + "章尚未回收，建议尽快处理");
                    result.getWarnings().add(warning);
                }
            }
        }

        return result;
    }

    public RhythmAnalysisResult analyzeRhythm(Long userId, Long novelId, Long chapterId, String content) {
        RhythmAnalysisResult result = new RhythmAnalysisResult();
        int chapterNoVal = 1;
        if (chapterId != null) {
            NovelChapter ch = chapterMapper.selectById(chapterId);
            if (ch != null && ch.getChapterOrder() != null) {
                chapterNoVal = ch.getChapterOrder();
            }
        }
        result.setChapterNo(chapterNoVal);

        int wordCount = content.length();
        result.setWordCount(wordCount);

        String[] paragraphs = content.split("\n");

        List<Float> tensionCurve = new ArrayList<>();
        List<Float> warmthCurve = new ArrayList<>();

        for (String paragraph : paragraphs) {
            if (paragraph.trim().isEmpty()) continue;

            float tension = calculateTension(paragraph);
            float warmth = calculateWarmth(paragraph);

            tensionCurve.add(tension);
            warmthCurve.add(warmth);
        }

        result.setTensionCurve(tensionCurve);
        result.setWarmthCurve(warmthCurve);

        float avgTension = tensionCurve.isEmpty() ? 0
                : (float) (tensionCurve.stream().mapToDouble(Float::doubleValue).sum() / tensionCurve.size());
        float avgWarmth = warmthCurve.isEmpty() ? 0
                : (float) (warmthCurve.stream().mapToDouble(Float::doubleValue).sum() / warmthCurve.size());

        result.setAverageTension(avgTension);
        result.setAverageWarmth(avgWarmth);
        result.setAnticipationScore(calculateAnticipation(tensionCurve));
        result.setTensionScore(avgTension);
        result.setWarmthScore(avgWarmth);
        result.setSadnessScore(calculateSadness(content));
        result.setConflictCount(countConflicts(content));
        result.setConflictDensity(wordCount > 0 ? (float) result.getConflictCount() / wordCount * 1000 : 0);
        result.setRetentionScore(calculateRetentionScore(result));

        result.setSuggestions(generateRhythmSuggestions(result));

        saveRhythmAnalysis(userId, novelId, chapterId, result);

        return result;
    }

    public CharacterConsistencyResult checkCharacterConsistency(Long userId, Long novelId, Long chapterId,
                                                               String content, List<NovelCharacter> characters) {
        CharacterConsistencyResult result = new CharacterConsistencyResult();
        result.setIssues(new ArrayList<>());

        for (NovelCharacter character : characters) {
            String personalityTraits = character.getPersonality();
            if (personalityTraits == null || personalityTraits.isEmpty()) continue;

            List<String> traits = Arrays.asList(personalityTraits.split("[,，;；]"));

            for (String trait : traits) {
                trait = trait.trim();
                if (trait.isEmpty()) continue;

                if (!traitMatchContent(trait, content)) {
                    ConsistencyIssue issue = new ConsistencyIssue();
                    issue.setCategory("personality");
                    issue.setSeverity("medium");
                    issue.setCharacterId(character.getId());
                    issue.setCharacterName(character.getName());
                    issue.setMessage("角色 '" + character.getName() + "' 的 '" + trait + "' 特征在当前内容中未体现");
                    issue.setSuggestion("建议在描写中体现角色的核心性格特征");
                    result.getIssues().add(issue);
                }
            }
        }

        saveCharacterConsistencyRecords(userId, novelId, result.getIssues());

        return result;
    }

    public List<ForeshadowingRecord> getPendingForeshadowings(Long novelId) {
        return foreshadowingMapper.findPendingByNovelId(novelId);
    }

    @Transactional(rollbackFor = Exception.class)
    public void resolveForeshadowing(String foreshadowingId, Long resolutionChapterId, Integer quality) {
        ForeshadowingRecord record = foreshadowingMapper.selectById(foreshadowingId);
        if (record != null) {
            if (resolutionChapterId != null) {
                record.setPaidOffChapterNo(resolutionChapterId.intValue());
            }
            record.setStatus("paid_off");
            record.setPaidOffAt(LocalDateTime.now());
            if (quality != null) {
                record.setPayoffMethod("manual");
                record.setPayoffContent("quality:" + quality);
            }
            foreshadowingMapper.updateById(record);
        }
    }

    @Transactional(rollbackFor = Exception.class)
    public void abandonForeshadowing(String foreshadowingId, String reason) {
        ForeshadowingRecord record = foreshadowingMapper.selectById(foreshadowingId);
        if (record != null) {
            record.setStatus("cancelled");
            record.setPayoffContent(reason);
            foreshadowingMapper.updateById(record);
        }
    }

    public List<RhythmAnalysis> getRhythmHistory(Long novelId) {
        return rhythmMapper.findByNovelId(novelId);
    }

    private ConsistencyCheckResult performAICheck(String content, String checkType, String context, String originalContent) {
        try {
            String prompt = buildCheckPrompt(checkType, context, originalContent);

            List<ChatCompletionRequest.Message> messages = Arrays.asList(
                    ChatCompletionRequest.Message.builder().role("system").content("你是一个小说一致性检查专家。请分析文本并输出JSON格式的检查结果。").build(),
                    ChatCompletionRequest.Message.builder().role("user").content(prompt).build()
            );

            ChatCompletionRequest request = ChatCompletionRequest.builder()
                    .messages(messages)
                    .temperature(0.3)
                    .maxTokens(1000)
                    .build();

            AiBillingService.AiGenerationRequest genRequest = AiBillingService.AiGenerationRequest.builder()
                    .userId(1L)
                    .contentType("consistency_check")
                    .messages(messages)
                    .temperature(0.3)
                    .maxTokens(1000)
                    .inputTokens(prompt.length() / 4)
                    .outputTokens(250)
                    .build();

            AiBillingService.AiGenerationResult aiResult = aiBillingService.generateWithBilling(genRequest);

            if (aiResult.getSuccess() && aiResult.getContent() != null) {
                return parseAIResult(aiResult.getContent());
            }
        } catch (Exception e) {
            log.error("AI consistency check failed: {}", e.getMessage());
        }

        return fallbackKeywordCheck(content, checkType);
    }

    private ConsistencyCheckResult parseAIResult(String aiContent) {
        ConsistencyCheckResult result = new ConsistencyCheckResult();
        result.setIssues(new ArrayList<>());

        try {
            if (aiContent.contains("```json")) {
                aiContent = aiContent.substring(aiContent.indexOf("```json") + 7);
                aiContent = aiContent.substring(0, aiContent.indexOf("```"));
            }

            JSONObject json = JSON.parseObject(aiContent);

            if (json.containsKey("issues")) {
                JSONArray issues = json.getJSONArray("issues");
                for (int i = 0; i < issues.size(); i++) {
                    JSONObject issue = issues.getJSONObject(i);
                    ConsistencyIssue item = new ConsistencyIssue();
                    item.setCategory(issue.getString("category"));
                    item.setSeverity(issue.getString("severity"));
                    item.setMessage(issue.getString("message"));
                    item.setSuggestion(issue.getString("suggestion"));
                    result.getIssues().add(item);
                }
            }

            Boolean passed = json.getBoolean("passed");
            result.setPassed(passed == null || passed);
        } catch (Exception e) {
            log.warn("Failed to parse AI result: {}", e.getMessage());
        }

        return result;
    }

    private ConsistencyCheckResult fallbackKeywordCheck(String content, String checkType) {
        ConsistencyCheckResult result = new ConsistencyCheckResult();
        result.setIssues(new ArrayList<>());

        switch (checkType) {
            case CHECK_TYPE_RULE:
                if (containsAny(content, "违反设定", "违背设定", "无视规则", "打破规则")) {
                    addIssue(result, "rule", "medium", "检测到可能的世界规则冲突", "请核对世界观设定、能力边界和禁忌规则");
                }
                break;
            case CHECK_TYPE_TIMELINE:
                boolean hasMorning = containsAny(content, "清晨", "黎明", "天亮");
                boolean hasNight = containsAny(content, "深夜", "半夜", "子时");
                if (hasMorning && hasNight) {
                    addIssue(result, "timeline", "medium", "检测到可能的时间线冲突", "同一章节内同时存在早晨和夜晚的描写");
                }
                break;
            case CHECK_TYPE_PERSONALITY:
                if (containsAny(content, "判若两人", "性格突变", "一反常态且无铺垫")) {
                    addIssue(result, "personality", "low", "检测到可能的人物性格偏移", "请补充角色动机或过渡情节");
                }
                break;
        }

        result.setPassed(result.getIssues().stream().noneMatch(i -> "high".equals(i.getSeverity())));
        return result;
    }

    private String buildCheckPrompt(String checkType, String context, String content) {
        String checkDescription = switch (checkType) {
            case CHECK_TYPE_RULE -> "检查内容是否违反了预设的世界观设定、能力边界或禁忌规则";
            case CHECK_TYPE_TIMELINE -> "检查内容中的时间线描述是否一致，是否存在时间矛盾";
            case CHECK_TYPE_PERSONALITY -> "检查角色的行为和语言是否符合其设定的人物性格";
            default -> "检查内容的一致性";
        };

        return String.format("""
            【一致性检查任务】
            检查类型: %s

            【世界观/角色背景】
            %s

            【待检查内容】
            %s

            请以JSON格式输出检查结果，格式如下：
            {
              "passed": true/false,
              "issues": [
                {
                  "category": "规则冲突/时间线/性格",
                  "severity": "high/medium/low",
                  "message": "问题描述",
                  "suggestion": "修改建议"
                }
              ]
            }
            """, checkDescription, context, content);
    }

    private String buildNovelContextPrompt(Long novelId, List<NovelCharacter> characters, List<String> worldRules) {
        StringBuilder sb = new StringBuilder();

        Novel novel = novelMapper.selectById(novelId);
        if (novel != null) {
            sb.append("作品名称: ").append(novel.getTitle()).append("\n");
            sb.append("作品设定: ").append(novel.getSynopsis() != null ? novel.getSynopsis() : "").append("\n");
        }

        if (worldRules != null && !worldRules.isEmpty()) {
            sb.append("世界观规则:\n");
            worldRules.forEach(rule -> sb.append("- ").append(rule).append("\n"));
        }

        if (characters != null && !characters.isEmpty()) {
            sb.append("角色设定:\n");
            characters.forEach(character -> {
                sb.append("- ").append(character.getName()).append(": ");
                sb.append(character.getPersonality() != null ? character.getPersonality() : "").append("\n");
            });
        }

        return sb.toString();
    }

    private String detectForeshadowingType(String sentence) {
        if (sentence.contains("说") || sentence.contains("道") || sentence.contains("问")) {
            return "dialogue";
        } else if (sentence.contains("突然") || sentence.contains("忽然") || sentence.contains("猛地")) {
            return "action";
        } else if (sentence.contains("似乎") || sentence.contains("好像") || sentence.contains("隐约")) {
            return "description";
        } else {
            return "event";
        }
    }

    private float calculateTension(String paragraph) {
        float tension = 0.3f;

        String[] tensionKeywords = {"紧张", "心跳", "屏住呼吸", "危险", "恐惧", "惊慌", "激烈", "对峙", "冲突"};
        for (String keyword : tensionKeywords) {
            if (paragraph.contains(keyword)) {
                tension += 0.15f;
            }
        }

        String[] tensionPunctuation = {"！", "？", "!!", "??"};
        for (String p : tensionPunctuation) {
            int count = countOccurrences(paragraph, p);
            tension += count * 0.05f;
        }

        return Math.min(tension, 1.0f);
    }

    private float calculateWarmth(String paragraph) {
        float warmth = 0.3f;

        String[] warmthKeywords = {"温暖", "微笑", "幸福", "开心", "欢乐", "甜蜜", "关怀", "爱", "温馨"};
        for (String keyword : warmthKeywords) {
            if (paragraph.contains(keyword)) {
                warmth += 0.15f;
            }
        }

        return Math.min(warmth, 1.0f);
    }

    private float calculateSadness(String content) {
        float sadness = 0.2f;

        String[] sadnessKeywords = {"悲伤", "痛苦", "流泪", "哭泣", "绝望", "失落", "难过", "伤心", "哀伤"};
        for (String keyword : sadnessKeywords) {
            if (content.contains(keyword)) {
                sadness += 0.1f;
            }
        }

        return Math.min(sadness, 1.0f);
    }

    private float calculateAnticipation(List<Float> tensionCurve) {
        if (tensionCurve.size() < 3) return 0.5f;

        int third = Math.max(1, tensionCurve.size() / 3);
        int tailCount = Math.max(1, tensionCurve.size() - tensionCurve.size() * 2 / 3);
        float first = (float) (tensionCurve.stream().limit(third).mapToDouble(Float::doubleValue).sum() / third);
        float last = (float) (tensionCurve.stream().skip(tensionCurve.size() * 2L / 3).mapToDouble(Float::doubleValue).sum() / tailCount);

        return (first + last) / 2;
    }

    private int countConflicts(String content) {
        int count = 0;
        String[] conflictKeywords = {"争吵", "打架", "冲突", "对立", "争论", "对峙", "搏斗", "矛盾"};
        for (String keyword : conflictKeywords) {
            count += countOccurrences(content, keyword);
        }
        return count;
    }

    private float calculateRetentionScore(RhythmAnalysisResult result) {
        float score = 0.5f;

        if (result.getConflictDensity() > 2 && result.getConflictDensity() < 5) {
            score += 0.2f;
        } else if (result.getConflictDensity() >= 5) {
            score -= 0.1f;
        }

        float tensionVariation = calculateVariation(result.getTensionCurve());
        if (tensionVariation > 0.2 && tensionVariation < 0.5) {
            score += 0.15f;
        }

        if (result.getAverageTension() > 0.3 && result.getAverageTension() < 0.7) {
            score += 0.1f;
        }

        return Math.min(Math.max(score, 0f), 1f);
    }

    private float calculateVariation(List<Float> values) {
        if (values.size() < 2) return 0f;
        float mean = (float) (values.stream().mapToDouble(Float::doubleValue).sum() / values.size());
        float variance = (float) (values.stream().mapToDouble(v -> {
            float vf = v;
            double d = vf - mean;
            return d * d;
        }).sum() / values.size());
        return (float) Math.sqrt(variance);
    }

    private List<String> generateRhythmSuggestions(RhythmAnalysisResult result) {
        List<String> suggestions = new ArrayList<>();

        if (result.getConflictDensity() < 2) {
            suggestions.add("冲突密度偏低，建议增加一些矛盾冲突来提升故事张力");
        } else if (result.getConflictDensity() > 6) {
            suggestions.add("冲突密度过高，可能导致读者疲劳，建议适当减少");
        }

        if (result.getAverageTension() < 0.3) {
            suggestions.add("整体紧张感偏低，可以在关键情节处增加悬念");
        } else if (result.getAverageTension() > 0.8) {
            suggestions.add("紧张感持续过高，建议适当穿插缓和的情节");
        }

        float tensionVar = calculateVariation(result.getTensionCurve());
        if (tensionVar < 0.1) {
            suggestions.add("节奏过于平稳，建议增加情节起伏");
        }

        if (result.getRetentionScore() < 0.4) {
            suggestions.add("追读预测分数较低，建议加强章节结尾的钩子");
        }

        return suggestions;
    }

    private boolean traitMatchContent(String trait, String content) {
        String[] traitWords = trait.split("\\s+");
        for (String word : traitWords) {
            if (word.length() > 2 && content.contains(word)) {
                return true;
            }
        }
        return false;
    }

    private boolean containsAny(String text, String... tokens) {
        for (String token : tokens) {
            if (text.contains(token)) {
                return true;
            }
        }
        return false;
    }

    private int countOccurrences(String text, String search) {
        int count = 0;
        int index = 0;
        while ((index = text.indexOf(search, index)) != -1) {
            count++;
            index += search.length();
        }
        return count;
    }

    private void addIssue(ConsistencyCheckResult result, String category, String severity, String message, String suggestion) {
        ConsistencyIssue issue = new ConsistencyIssue();
        issue.setCategory(category);
        issue.setSeverity(severity);
        issue.setMessage(message);
        issue.setSuggestion(suggestion);
        result.getIssues().add(issue);
    }

    private void saveCheckRecord(Long userId, Long novelId, Long chapterId, String contentType,
                                 String checkType, ConsistencyCheckResult result, long processingTime) {
        AiConsistencyCheck record = new AiConsistencyCheck();
        record.setUserId(userId);
        record.setNovelId(novelId);
        record.setChapterId(chapterId);
        record.setContentType(contentType);
        record.setCheckType(checkType);
        record.setCheckResult(result.getPassed() ? "pass" : (result.getIssues().isEmpty() ? "pass" : "warning"));
        record.setIssueCount(result.getIssues().size());
        record.setIssuesDetail(JSON.toJSONString(result.getIssues()));
        record.setProcessingTimeMs((int) processingTime);
        checkMapper.insert(record);
    }

    private void saveForeshadowings(Long userId, Long novelId, Long chapterId, List<ForeshadowingItem> items) {
        Integer chapterNo = null;
        if (chapterId != null) {
            NovelChapter ch = chapterMapper.selectById(chapterId);
            if (ch != null) {
                chapterNo = ch.getChapterOrder();
            }
        }
        for (ForeshadowingItem item : items) {
            ForeshadowingRecord record = new ForeshadowingRecord();
            record.setNovelId(novelId);
            record.setChapterNo(chapterNo != null ? chapterNo : 0);
            record.setSetupContent(item.getContent());
            if (item.getPosition() >= 0) {
                record.setSetupLocation((float) item.getPosition());
            }
            record.setType(item.getType());
            record.setStatus("pending");
            record.setDetectedAt(LocalDateTime.now());
            foreshadowingMapper.insert(record);
        }
    }

    private void saveRhythmAnalysis(Long userId, Long novelId, Long chapterId, RhythmAnalysisResult result) {
        RhythmAnalysis analysis = new RhythmAnalysis();
        analysis.setUserId(userId);
        analysis.setNovelId(novelId);
        analysis.setChapterId(chapterId);
        analysis.setChapterNo(result.getChapterNo());
        analysis.setAnalysisType("full");
        analysis.setAnticipationScore(BigDecimal.valueOf(result.getAnticipationScore()));
        analysis.setTensionScore(BigDecimal.valueOf(result.getTensionScore()));
        analysis.setWarmthScore(BigDecimal.valueOf(result.getWarmthScore()));
        analysis.setSadnessScore(BigDecimal.valueOf(result.getSadnessScore()));
        analysis.setConflictCount(result.getConflictCount());
        analysis.setConflictDensity(BigDecimal.valueOf(result.getConflictDensity()));
        analysis.setRetentionScore(BigDecimal.valueOf(result.getRetentionScore()));
        analysis.setEmotionCurve(JSON.toJSONString(result.getTensionCurve()));
        analysis.setSuggestions(JSON.toJSONString(result.getSuggestions()));
        analysis.setWordCount(result.getWordCount());
        rhythmMapper.insert(analysis);
    }

    private void saveCharacterConsistencyRecords(Long userId, Long novelId, List<ConsistencyIssue> issues) {
        for (ConsistencyIssue issue : issues) {
            if (issue.getCharacterId() == null) continue;

            CharacterConsistency record = new CharacterConsistency();
            record.setUserId(userId);
            record.setNovelId(novelId);
            record.setCharacterId(issue.getCharacterId());
            record.setConsistencyType("personality");
            record.setCheckResult("warning");
            record.setIssueDescription(issue.getMessage());
            record.setSuggestion(issue.getSuggestion());
            record.setSeverity(issue.getSeverity());
            characterConsistencyMapper.insert(record);
        }
    }

    @lombok.Data
    public static class ConsistencyCheckResult {
        private Boolean passed;
        private List<ConsistencyIssue> issues;
    }

    @lombok.Data
    public static class ConsistencyIssue {
        private String category;
        private String severity;
        private String message;
        private String suggestion;
        private Long characterId;
        private String characterName;
    }

    @lombok.Data
    public static class ForeshadowingAnalysisResult {
        private List<ForeshadowingItem> detectedForeshadowings;
        private int pendingCount;
        private List<ForeshadowingWarning> warnings;
    }

    @lombok.Data
    public static class ForeshadowingItem {
        private String content;
        private String type;
        private int hintLevel;
        private String hintKeyword;
        private int position;
    }

    @lombok.Data
    public static class ForeshadowingWarning {
        private String foreshadowingId;
        private String content;
        private String warningType;
        private String message;
    }

    @lombok.Data
    public static class RhythmAnalysisResult {
        private int chapterNo;
        private int wordCount;
        private float anticipationScore;
        private float tensionScore;
        private float warmthScore;
        private float sadnessScore;
        private int conflictCount;
        private float conflictDensity;
        private float retentionScore;
        private float averageTension;
        private float averageWarmth;
        private List<Float> tensionCurve;
        private List<Float> warmthCurve;
        private List<String> suggestions;
    }

    @lombok.Data
    public static class CharacterConsistencyResult {
        private List<ConsistencyIssue> issues;
    }
}
